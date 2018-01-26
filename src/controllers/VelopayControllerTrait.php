<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 21.01.18
 * Time: 20:03
 */

namespace cronfy\yii2Velopay\controllers;


use cronfy\velopay\gateways\AbstractGateway;
use cronfy\yii2Velopay\models\Invoice;
use Yii;

trait VelopayControllerTrait
{

    /**
     * @deprecated
     * @param $invoice Invoice
     * @param $logUid
     * @param bool $realProcess
     * @return bool
     * @throws \Exception
     */
    protected function processTransactionByOrderPaymentData($invoice, $logUid, $realProcess = true) {
        $order = $invoice->order;
        $invoice->invoiceData['currency'] = 'RUB';
        $invoice->invoiceData['gatewaySid'] = $invoice->invoiceData['gate'];

        if ($order->created_at < strtotime('2018-01-11 16:00')) {
            // у старых заказов некорректно регистрировались payments, что может привести
            // к дублированию платежа
            Yii::info("$logUid Skipping old order (data format not compatible)" . $invoice->id . ' order ' . $order->id, 'app/velopay');
            return false;
        }

        Yii::info("$logUid Processing invoice " . $invoice->id . ' order ' . $order->id, 'app/velopay');

        $gateway = $this->getGateway($invoice->getGatewaySid());
        /** @var $gateway AbstractGateway */
        $gateway->setInvoice($invoice);

        Yii::info("$logUid Info: gateway " . $gateway->getSid(), 'app/velopay');


        if ($realProcess) {
            $gateway->process();

            return $this->afterGatewayResponse($gateway, $logUid);
        } else {
            Yii::info("$logUid Fake process", 'app/velopay');
            return false;
        }
    }

    /**
     * @param $invoice Invoice
     * @param $logUid
     * @param bool $realProcess
     * @return bool
     */
    protected function processTransactionByInvoice($invoice, $logUid, $realProcess = true) {

        Yii::info("$logUid Processing invoice " . $invoice->id . ' order ' . $invoice->order->id, 'app/velopay');

        $gateway = $this->getGateway($invoice->getGatewaySid());
        /** @var $gateway AbstractGateway */
        $gateway->setInvoice($invoice);

        Yii::info("$logUid Info: gateway " . $gateway->getSid(), 'app/velopay');

        if ($realProcess) {
            $gateway->process();

            return $this->afterGatewayResponse($gateway, $logUid);
        } else {
            Yii::info("$logUid Fake process", 'app/velopay');
            return false;
        }
    }

}