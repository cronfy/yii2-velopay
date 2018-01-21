<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 21.01.18
 * Time: 20:03
 */

namespace cronfy\yii2Velopay\controllers;


use Yii;

trait VelopayControllerTrait
{

    protected function processTransactionByOrderPaymentData($orderPaymentData, $logUid, $realProcess = true) {
        $invoice = $this->getInvoiceByOrderPaymentData($orderPaymentData);
        $order = $invoice->order;

        if ($order->created_at < strtotime('2018-01-11 16:00')) {
            // у старых заказов некорректно регистрировались payments, что может привести
            // к дублированию платежа
            Yii::info("$logUid Skipping old order (data format not compatible)" . $orderPaymentData->id . ' order ' . $order->id, 'app/velopay');
            return false;
        }

        Yii::info("$logUid Processing payment " . $orderPaymentData->id . ' order ' . $order->id, 'app/velopay');

        $gateway = $this->getGatewayByOrderPaymentData($orderPaymentData);
        $gateway->setStorage($orderPaymentData);

        $gateway->setInvoice($invoice);

        Yii::info("$logUid Info: gateway " . $gateway->getSid(), 'app/velopay');

        if ($realProcess) {
            $gateway->process();

            return $this->afterGatewayResponse($gateway, $order, $logUid);
        } else {
            Yii::info("$logUid Fake process", 'app/velopay');
            return false;
        }
    }

}