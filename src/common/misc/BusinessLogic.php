<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 23.05.18
 * Time: 18:05
 */

namespace cronfy\yii2Velopay\common\misc;

use cronfy\velopay\gateways\AbstractGateway;
use cronfy\yii2Velopay\common\models\Invoice;
use yii\base\BaseObject;

abstract class BusinessLogic extends BaseObject
{
    abstract public function getOrderById($orderId);
    abstract public function getIsOrderPayable($order);
    abstract public function getOrderRoute($order);
    abstract public function getGatewayByPaymentMethod($methodSid);

    /**
     * @param $gatewaySid
     * @return AbstractGateway
     */
    abstract public function getGatewayBySid($gatewaySid);
    abstract public function createInvoiceByOrder($order, $params = null);

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    abstract public function registerPayment($invoice);

    /**
     * @return Invoice
     */
    public function getInvoiceClass() {
        return Invoice::class;
    }

    /**
     * @param string $invoiceId
     * @return Invoice
     */
    public function getInvoiceById($invoiceId)
    {
        return $this->getInvoiceClass()::find()->where(['id' => $invoiceId])->one();
    }


    /**
     * @param AbstractGateway $gateway
     * @return string
     */
    abstract public function getGatewaySid($gateway);
}