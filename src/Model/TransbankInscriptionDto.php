<?php

namespace Transbank\Plugin\Model;

class TransbankInscriptionDto extends TbkDtoBase
{
    public $token;
    public $username;
    public $email;
    public $userId;
    public $ecommerceTokenId;
    public $tbkUser;
    public $orderId;
    public $payAfterInscription;
    public $responseCode;
    public $authorizationCode;
    public $cardType;
    public $cardNumber;
    public $from;
    public $status;
    public $transbankResponse;
    public $updatedAt;

    /**
     * @param array $data
     */
    public function __construct($data = null) {
        if (!is_null($data)){
            $this->setId($data['id']);
            $this->setStoreId($data['store_id']);
            $this->setToken($data['token']);
            $this->setUsername($data['username']);
            $this->setEmail($data['email']);
            $this->setUserId($data['user_id']);
            $this->setEcommerceTokenId($data['ecommerce_token_id']);
            $this->setTbkUser($data['tbk_user']);
            $this->setOrderId($data['order_id']);
            $this->setPayAfterInscription($data['pay_after_inscription']);
            $this->setResponseCode($data['response_code']);
            $this->setAuthorizationCode($data['authorization_code']);
            $this->setCardType($data['card_type']);
            $this->setCardNumber($data['card_number']);
            $this->setFrom($data['from']);
            $this->setStatus($data['status']);
            $this->setEnvironment($data['environment']);
            $this->setCommerceCode($data['commerce_code']);
            $this->setTransbankResponse($data['transbank_response']);
            $this->setCreatedAt($data['created_at']);
            $this->setUpdatedAt($data['updated_at']);
            $this->setError($data['error']);
            $this->setOriginalError($data['original_error']);
            $this->setCustomError($data['custom_error']);
        }
    }

    public function getToken() {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function getEcommerceTokenId() {
        return $this->ecommerceTokenId;
    }

    public function setEcommerceTokenId($ecommerceTokenId) {
        $this->ecommerceTokenId = $ecommerceTokenId;
    }

    public function getTbkUser() {
        return $this->tbkUser;
    }

    public function setTbkUser($tbkUser) {
        $this->tbkUser = $tbkUser;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function getPayAfterInscription() {
        return $this->payAfterInscription;
    }

    public function setPayAfterInscription($payAfterInscription) {
        $this->payAfterInscription = $payAfterInscription;
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

    public function getCardType() {
        return $this->cardType;
    }

    public function setCardType($cardType) {
        $this->cardType = $cardType;
    }

    public function getCardNumber() {
        return $this->cardNumber;
    }

    public function setCardNumber($cardNumber) {
        $this->cardNumber = $cardNumber;
    }

    public function getFrom() {
        return $this->from;
    }

    public function setFrom($from) {
        $this->from = $from;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getTransbankResponse() {
        return $this->transbankResponse;
    }

    public function setTransbankResponse($transbankResponse) {
        $this->transbankResponse = $transbankResponse;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;
    }
}
