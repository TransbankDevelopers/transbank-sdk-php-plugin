<?php

namespace Transbank\Plugin\Model;

use Transbank\Plugin\Helpers\TbkConstants;

class TransbankTransactionDto extends TbkDtoBase
{
    public $orderId;
    public $buyOrder;
    public $childBuyOrder;
    public $childCommerceCode;
    public $amount;
    public $refundAmount;
    public $token;
    public $transbankStatus;
    public $sessionId;
    public $status;
    public $transbankResponse;
    public $lastRefundType;
    public $lastRefundResponse;
    public $allRefundResponse;
    public $oneclickUsername;
    public $product;
    public $updatedAt;

    /**
     * @param array $data
     */
    public function __construct($data = null) {
        if (!is_null($data)){
            $this->setId($data['id']);
            $this->setStoreId($data['store_id']);
            $this->setProduct($data['product']);
            $this->setEnvironment($data['environment']);
            $this->setCommerceCode($data['commerce_code']);
            $this->setChildCommerceCode($data['child_commerce_code']);
            $this->setOneclickUsername($data['oneclick_username']);
            $this->setBuyOrder($data['buy_order']);
            $this->setChildBuyOrder($data['child_buy_order']);
            $this->setOrderId($data['order_id']);
            $this->setToken($data['token']);
            $this->setAmount($data['amount']);
            $this->setRefundAmount($data['refund_amount']);
            $this->setSessionId($data['session_id']);
            $this->setStatus($data['status']);
            $this->setTransbankStatus($data['transbank_status']);
            $this->setTransbankResponse($data['transbank_response']);
            $this->setLastRefundType($data['last_refund_type']);
            $this->setLastRefundResponse($data['last_refund_response']);
            $this->setAllRefundResponse($data['all_refund_response']);
            $this->setError($data['error']);
            $this->setOriginalError($data['original_error']);
            $this->setCustomError($data['custom_error']);
            $this->setCreatedAt($data['created_at']);
            $this->setUpdatedAt($data['updated_at']);
        }
    }

    public function isWebpayplus(){
        return $this->getProduct() === TbkConstants::TRANSACTION_WEBPAY_PLUS;
    }

    public function isWebpayplusMall(){
        return $this->getProduct() === TbkConstants::TRANSACTION_WEBPAY_PLUS_MALL;
    }

    public function isOneclick(){
        return $this->getProduct() === TbkConstants::TRANSACTION_WEBPAY_ONECLICK;
    }

    public function isStatusEcommerceApproved(){
        return $this->getStatus() === TbkConstants::TRANSACTION_STATUS_ECOMMERCE_APPROVED;
    }

    public function isAuthorized(){
        return $this->getTransbankStatus() === TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function getBuyOrder() {
        return $this->buyOrder;
    }

    public function setBuyOrder($buyOrder) {
        $this->buyOrder = $buyOrder;
    }

    public function getChildBuyOrder() {
        return $this->childBuyOrder;
    }

    public function setChildBuyOrder($childBuyOrder) {
        $this->childBuyOrder = $childBuyOrder;
    }

    public function getChildCommerceCode() {
        return $this->childCommerceCode;
    }

    public function setChildCommerceCode($childCommerceCode) {
        $this->childCommerceCode = $childCommerceCode;
    }

    public function getRefundAmount() {
        return $this->refundAmount;
    }

    public function setRefundAmount($refundAmount) {
        $this->refundAmount = $refundAmount;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
    }

    public function getToken() {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function getTransbankStatus() {
        return $this->transbankStatus;
    }

    public function setTransbankStatus($transbankStatus) {
        $this->transbankStatus = $transbankStatus;
    }

    public function getSessionId() {
        return $this->sessionId;
    }

    public function setSessionId($sessionId) {
        $this->sessionId = $sessionId;
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

    public function getLastRefundType() {
        return $this->lastRefundType;
    }

    public function setLastRefundType($lastRefundType) {
        $this->lastRefundType = $lastRefundType;
    }

    public function getLastRefundResponse() {
        return $this->lastRefundResponse;
    }

    public function setLastRefundResponse($lastRefundResponse) {
        $this->lastRefundResponse = $lastRefundResponse;
    }

    public function getProduct() {
        return $this->product;
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;
    }

    public function getAllRefundResponse() {
        return $this->allRefundResponse;
    }

    public function setAllRefundResponse($allRefundResponse) {
        $this->allRefundResponse = $allRefundResponse;
    }

    public function getOneclickUsername() {
        return $this->oneclickUsername;
    }

    public function setOneclickUsername($oneclickUsername) {
        $this->oneclickUsername = $oneclickUsername;
    }
}
