<?php

namespace Transbank\Plugin\Model;

use DateTime;
use DateTimeZone;
use Transbank\Plugin\Helpers\ArrayUtils;
use Transbank\Plugin\Helpers\ObjectUtil;
use Transbank\Plugin\Helpers\TbkConstants;

class TransbankTransactionDetail extends TransbankTransactionDto
{
    public $cardNumber;
    public $installmentsAmount;
    public $installmentsNumber;
    public $responseCode;
    public $authorizationCode;
    public $paymentTypeCode;
    public $transactionDate;
    public $vci;
    public $accountingDate;
    public $balance;

    public $cardNumberLast4;
    public $cardNumberFull;

    public $amountFormat;
    public $installmentsAmountFormat;
    public $transactionDateFormat;
    public $transactionHourFormat;
    public $transactionDateHourFormat;
    public $transactionDateTzFormat;
    public $transactionHourTzFormat;
    public $transactionDateHourTzFormat;

    public $transbankStatusDes;
    public $installmentType;
    public $paymentTypeCodeDes;
    public $paymentType;
    public $paymentTypeDes;

    public function __construct(TransbankTransactionDto $data = null, $tz = null) {
        if (is_null($data)){
            return;
        }
        ObjectUtil::copyPropertiesFromTo($data, $this);
        if (!is_null($this->getTransbankResponse())){
            $resp = json_decode($this->getTransbankResponse(), true);
            $this->setCardNumber(ArrayUtils::getValue($resp, 'cardNumber'));
            if (!is_null($this->getCardNumber()) && strlen($this->getCardNumber()) >=4){
                $this->setCardNumberLast4(substr($this->getCardNumber(), -4));
                $this->setCardNumberFull('**** **** **** ' . $this->getCardNumberLast4());
            }
            $respTemp = $resp;
            if ($this->isOneclick()){
                $respTemp = $resp['details'][0];
            }
            $this->setInstallmentsAmount(ArrayUtils::getValue($respTemp, 'installmentsAmount'));
            $this->setInstallmentsNumber(ArrayUtils::getValue($respTemp, 'installmentsNumber'));
            $this->setResponseCode(ArrayUtils::getValue($respTemp, 'responseCode'));
            $this->setAuthorizationCode(ArrayUtils::getValue($respTemp, 'authorizationCode'));
            $this->setPaymentTypeCode(ArrayUtils::getValue($respTemp, 'paymentTypeCode'));
            $this->setTransactionDate(ArrayUtils::getValue($respTemp, 'transactionDate'));
            $this->setVci(ArrayUtils::getValue($respTemp, 'vci'));
            $this->setAccountingDate(ArrayUtils::getValue($respTemp, 'accountingDate'));
            $this->setBalance(ArrayUtils::getValue($respTemp, 'balance'));

            /* atributos de presentacion */
            $this->setTransbankStatusDes('Transacción Rechazada');
            if ($this->isAuthorized()){
                $this->setTransbankStatusDes('Transacción Aprobada');
            }
            $this->setPaymentTypeCodeDes("");
            $this->setPaymentType("");
            $this->setPaymentTypeDes("");
            $this->setInstallmentType("Sin cuotas");
            if (array_key_exists($this->getPaymentTypeCode(), TbkConstants::PAYMENT_TYPE)) {
                $this->setPaymentType(TbkConstants::PAYMENT_TYPE[$this->getPaymentTypeCode()]);
            }
            if (array_key_exists($this->getPaymentTypeCode(), TbkConstants::PAYMENT_TYPE_CODE)) {
                $this->setPaymentTypeDes(
                    TbkConstants::PAYMENT_TYPE_CODE[$this->getPaymentTypeCode()]);
            }
            if (array_key_exists($this->getPaymentTypeCode(), TbkConstants::INSTALLMENT_TYPE)) {
                $this->setInstallmentType(
                    TbkConstants::INSTALLMENT_TYPE[$this->getPaymentTypeCode()]);
            }
            $this->setInstallmentsAmountFormat("0");
            if (!is_null($this->getInstallmentsAmount())){
                $this->setInstallmentsAmountFormat(
                    number_format($this->getInstallmentsAmount(), 0, ',', '.'));
            }
            $transactionDate = strtotime($this->getTransactionDate());
            $this->setTransactionDateFormat(date("d-m-Y", $transactionDate));
            $this->setTransactionHourFormat(date("H:i:s", $transactionDate));
            $this->setTransactionDateHourFormat(date("d-m-Y H:i:s", $transactionDate));
            if (!is_null($tz)){
                $txDateTz = new DateTime($this->getTransactionDate(), new DateTimeZone('UTC'));
                $txDateTz->setTimeZone(new DateTimeZone($tz));
                $this->setTransactionDateTzFormat($txDateTz->format('d-m-Y'));
                $this->setTransactionHourTzFormat($txDateTz->format('H:i:s'));
                $this->setTransactionDateHourTzFormat($txDateTz->format('d-m-Y H:i:s'));
            }
        }
        $this->setAmountFormat(number_format($this->getAmount(), 0, ',', '.'));
    }

    public function hasInstallments(){
        if (!is_null($this->getInstallmentsNumber()) && $this->getInstallmentsNumber() > 0){
            return true;
        }
        return false;
    }

    public function getCardNumber() {
        return $this->cardNumber;
    }

    public function setCardNumber($cardNumber) {
        $this->cardNumber = $cardNumber;
    }

    public function getInstallmentsAmount() {
        return $this->installmentsAmount;
    }

    public function setInstallmentsAmount($installmentsAmount) {
        $this->installmentsAmount = $installmentsAmount;
    }

    public function getInstallmentsNumber() {
        return $this->installmentsNumber;
    }

    public function setInstallmentsNumber($installmentsNumber) {
        $this->installmentsNumber = $installmentsNumber;
    }

    public function getResponseCode() {
        return $this->responseCode;
    }

    public function setResponseCode($responseCode) {
        $this->responseCode = $responseCode;
    }

    public function getAuthorizationCode() {
        return $this->authorizationCode;
    }

    public function setAuthorizationCode($authorizationCode) {
        $this->authorizationCode = $authorizationCode;
    }

    public function getPaymentTypeCode() {
        return $this->paymentTypeCode;
    }

    public function setPaymentTypeCode($paymentTypeCode) {
        $this->paymentTypeCode = $paymentTypeCode;
    }

    public function getTransactionDate() {
        return $this->transactionDate;
    }

    public function setTransactionDate($transactionDate) {
        $this->transactionDate = $transactionDate;
    }

    public function getVci() {
        return $this->vci;
    }

    public function setVci($vci) {
        $this->vci = $vci;
    }

    public function getAccountingDate() {
        return $this->accountingDate;
    }

    public function setAccountingDate($accountingDate) {
        $this->accountingDate = $accountingDate;
    }

    public function getBalance() {
        return $this->balance;
    }

    public function setBalance($balance) {
        $this->balance = $balance;
    }

    public function getAmountFormat() {
        return $this->amountFormat;
    }

    public function setAmountFormat($amountFormat) {
        $this->amountFormat = $amountFormat;
    }

    public function getInstallmentsAmountFormat() {
        return $this->installmentsAmountFormat;
    }

    public function setInstallmentsAmountFormat($installmentsAmountFormat) {
        $this->installmentsAmountFormat = $installmentsAmountFormat;
    }

    public function getTransactionDateFormat() {
        return $this->transactionDateFormat;
    }

    public function setTransactionDateFormat($transactionDateFormat) {
        $this->transactionDateFormat = $transactionDateFormat;
    }

    public function getTransactionHourFormat() {
        return $this->transactionHourFormat;
    }

    public function setTransactionHourFormat($transactionHourFormat) {
        $this->transactionHourFormat = $transactionHourFormat;
    }

    public function getTransactionDateHourFormat() {
        return $this->transactionDateHourFormat;
    }

    public function setTransactionDateHourFormat($transactionDateHourFormat) {
        $this->transactionDateHourFormat = $transactionDateHourFormat;
    }

    public function getTransactionDateTzFormat() {
        return $this->transactionDateTzFormat;
    }

    public function setTransactionDateTzFormat($transactionDateTzFormat) {
        $this->transactionDateTzFormat = $transactionDateTzFormat;
    }

    public function getTransactionHourTzFormat() {
        return $this->transactionHourTzFormat;
    }

    public function setTransactionHourTzFormat($transactionHourTzFormat) {
        $this->transactionHourTzFormat = $transactionHourTzFormat;
    }

    public function getTransactionDateHourTzFormat() {
        return $this->transactionDateHourTzFormat;
    }

    public function setTransactionDateHourTzFormat($transactionDateHourTzFormat) {
        $this->transactionDateHourTzFormat = $transactionDateHourTzFormat;
    }

    public function getTransbankStatusDes() {
        return $this->transbankStatusDes;
    }

    public function setTransbankStatusDes($transbankStatusDes) {
        $this->transbankStatusDes = $transbankStatusDes;
    }

    public function getInstallmentType() {
        return $this->installmentType;
    }

    public function setInstallmentType($installmentType) {
        $this->installmentType = $installmentType;
    }

    public function getPaymentTypeCodeDes() {
        return $this->paymentTypeCodeDes;
    }

    public function setPaymentTypeCodeDes($paymentTypeCodeDes) {
        $this->paymentTypeCodeDes = $paymentTypeCodeDes;
    }

    public function getPaymentType() {
        return $this->paymentType;
    }

    public function setPaymentType($paymentType) {
        $this->paymentType = $paymentType;
    }

    public function getPaymentTypeDes() {
        return $this->paymentTypeDes;
    }

    public function setPaymentTypeDes($paymentTypeDes) {
        $this->paymentTypeDes = $paymentTypeDes;
    }

    public function getCardNumberLast4() {
        return $this->cardNumberLast4;
    }

    public function setCardNumberLast4($cardNumberLast4) {
        $this->cardNumberLast4 = $cardNumberLast4;
    }

    public function getCardNumberFull() {
        return $this->cardNumberFull;
    }

    public function setCardNumberFull($cardNumberFull) {
        $this->cardNumberFull = $cardNumberFull;
    }

}
