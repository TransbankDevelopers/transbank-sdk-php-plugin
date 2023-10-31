<?php

namespace Transbank\Plugin\Service;

use DateTime;
use Exception;
use Transbank\Plugin\Exceptions\CreateInscriptionDbException;
use Transbank\Plugin\Exceptions\GetInscriptionDbException;
use Transbank\Plugin\Exceptions\Oneclick\NotFoundInscriptionDbException;
use Transbank\Plugin\Exceptions\UpdateInscriptionDbException;
use Transbank\Plugin\Helpers\DateUtils;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\IRepository\IInscriptionRepository;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Model\ExecutionErrorLogDto;
use Transbank\Plugin\Model\TransbankInscriptionDto;

class InscriptionService extends BaseTableService {
    
    private $inscriptionRepository;
    private $executionErrorLogService;

    public function __construct(ILogger $logger,
        IInscriptionRepository $inscriptionRepository,
        ExecutionErrorLogService $executionErrorLogService
    ) {
        parent::__construct($logger, $inscriptionRepository);
        $this->executionErrorLogService = $executionErrorLogService;
        $this->inscriptionRepository = $inscriptionRepository;
    }

    protected function createTableDto($data){
        return new TransbankInscriptionDto($data);
    }

    /**
     * Este método retorna una inscripción única por Token.
     *
     * @param string $token
     * @throws GetInscriptionDbException Cuando algo falla al ejecutar la consulta que obtiene la inscripción.
     * @throws NotFoundInscriptionDbException Cuando no se encuentra la inscripción.
     * @return TransbankInscriptionDto
     */
    public function getByToken($token){
        $tx = null;
        try {
            $tx = $this->inscriptionRepository->getByToken($token);
        } catch (Exception $e) {
            $errorMessage = "Ocurrió un error al tratar de obtener la inscripción, TOKEN: {$token}, ERROR: {$e->getMessage()}";
            throw new GetInscriptionDbException($errorMessage);
        }
        if (!$tx) {
            $errorMessage = "No se encontró una inscripción, TOKEN: {$token}";
            throw new NotFoundInscriptionDbException($errorMessage);
        }
        return $tx;
    }

    /**
     * Este método retorna una inscripción única por username.
     *
     * @param string $username
     * @throws GetInscriptionDbException Cuando algo falla al ejecutar la consulta que obtiene la inscripción.
     * @throws NotFoundInscriptionDbException Cuando no se encuentra la inscripción.
     * @return TransbankInscriptionDto
     */
    public function getByUsername($username){
        $tx = null;
        try {
            $tx = $this->inscriptionRepository->getByUsername($username);
        } catch (Exception $e) {
            $errorMessage = "Ocurrió un error al tratar de obtener la inscripción, USERNAME: {$username}, ERROR: {$e->getMessage()}";
            throw new GetInscriptionDbException($errorMessage);
        }
        if (!$tx) {
            $errorMessage = "No se encontró una inscripción, USERNAME: {$username}";
            throw new NotFoundInscriptionDbException($errorMessage);
        }
        return $tx;
    }

     /**
     * Este método retorna una inscripción única por username preparada para autorizar transacciones.
     *
     * @param string $username
     * @throws NotFoundInscriptionDbException Cuando la inscripción no se encuentra en estado 'completed'.
     * @return TransbankInscriptionDto
     */
    public function getByUsernameForAuthorize($username, ExecutionErrorLogDto $eel){

        $ins = $this->getByUsername($username);
        if ($ins->getStatus() !== TbkConstants::INSCRIPTIONS_STATUS_COMPLETED) {
            $errorMessage = "La inscripción no se encuentra en estado 'completed', USERNAME: {$username} ";
            $eel->setError('NotFoundInscriptionDbException');
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new NotFoundInscriptionDbException($errorMessage);
        }
        return $ins;
    }

    /**
     * Este método crea una inscripción.
     *
     * @param TransbankInscriptionDto $data
     * @param ExecutionErrorLogDto $eel
     * @throws CreateInscriptionDbException Cuando no puede crear la inscripción.
     * @return TransbankInscriptionDto
     */
    public function create(TransbankInscriptionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setStatus(TbkConstants::INSCRIPTIONS_STATUS_INITIALIZED);
            $this->inscriptionRepository->create($data);
            $this->logInfoWithBuyOrder($eel->getBuyOrder(), TbkConstants::ONECLICK_START, 
                "Inscripción creada en la base de datos con estado initialized", $data);
        } catch (Exception $e) {
            $errorMessage = "La inscripción no se pudo crear en la tabla: '{$this->inscriptionRepository->getTableName()}', error: {$e->getMessage()}";
            $eel->setError('CreateInscriptionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new CreateInscriptionDbException($errorMessage, $data);
        }
        return $this->inscriptionRepository->getByToken($data->getToken());
    }

    /**
     * Este método actualiza el registro de la inscripción luego de un finish exitoso en Transbank.
     *
     * @param TransbankInscriptionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updatePostFinishTbk(TransbankInscriptionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setStatus(TbkConstants::INSCRIPTIONS_STATUS_COMPLETED);
            $data->setUpdatedAt(DateUtils::getNow());
            $this->inscriptionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getOrderId(), $eel->getService(),
                "inscripción actualizada en la base de datos con estado approved", $data);
        } catch (Exception $e) {
            $errorMessage = "La inscripción no se pudo actualizar, error: {$e->getMessage()}";
            $eel->setError('UpdateInscriptionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateInscriptionDbException($errorMessage, $data);
        }
    }

    /**
     * Este método retorna la cantidad de inscripciones por userId preparados para autorizar 
     * transacciones.
     *
     * @param string $userId
     * @return int
     */
    public function getCountByUserId($userId){
        return $this->inscriptionRepository->getCountByUserId($userId);
    }
    
    /**
     * Este método retorna una lista de inscripciones por userId preparados para autorizar 
     * transacciones.
     *
     * @param string $userId
     * @return array
     */
    public function getListByUserId($userId){
        return $this->inscriptionRepository->getListByUserId($userId);
    }

    /**
     * Este método actualiza la inscripción al estado 'failed' por un error producido durante la inscripción
     * Valida que la inscripción se encuentre en estado 'initialized', porque podria existir otro proceso paralelo tratando de hacer lo mismo
     *
     * @param TransbankInscriptionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updateInitializedWithError(TransbankInscriptionDto $data, ExecutionErrorLogDto $eel)
    {
        if ($data->getStatus() !== TbkConstants::INSCRIPTIONS_STATUS_INITIALIZED) {
            $errorMessage = "Se quiso guardar la excepción: '{$eel->getError()}' en la tabla y el registro no tiene 'status'='initialized'";
            $eel->setError('GenericException');
            $eel->setOriginalError(null);
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            return;
        }
        $data->setError($eel->getError());
        $data->setOriginalError($eel->getOriginalError());
        $data->setCustomError($eel->getCustomError());
        $data->setStatus(TbkConstants::INSCRIPTIONS_STATUS_FAILED);
        $data->setUpdatedAt(DateUtils::getNow());
        $this->inscriptionRepository->update($data);
        $this->executionErrorLogService->create($eel);
    }

    /**
     * Este método actualiza el registro de la inscripción con el id de la tarjeta tokenizada
     * que el ecommerce maneja.
     *
     * @param TransbankInscriptionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updateEcommerceToken(TransbankInscriptionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setUpdatedAt(DateUtils::getNow());
            $this->inscriptionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getOrderId(), $eel->getService(), "inscripción actualizada en la base de datos con estado approved", $data);
        } catch (Exception $e) {
            $errorMessage = "La inscripción no se pudo actualizar con la tarjeta tokenizada, error: {$e->getMessage()}";
            $eel->setError('UpdateInscriptionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateInscriptionDbException($errorMessage, $data);
        }
    }
}
