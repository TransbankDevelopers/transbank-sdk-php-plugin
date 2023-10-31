<?php

namespace Transbank\Plugin\IRepository;

use Transbank\Plugin\Model\TransbankInscriptionDto;

interface IInscriptionRepository extends ITableRepository {
    function create(TransbankInscriptionDto $data);
    function update(TransbankInscriptionDto $data);
    function getByToken($token);
    function getByUsername($username);
    function getListByUserId($userId);
    function getCountByUserId($userId);
}
