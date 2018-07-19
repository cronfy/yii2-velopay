<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 19.07.18
 * Time: 14:07
 */

namespace cronfy\yii2Velopay\console\controllers;


use cronfy\experience\php\debug\Debug;
use cronfy\velopay\gateways\AbstractGateway;
use cronfy\yii2Velopay\BaseModule;
use cronfy\yii2Velopay\common\models\Invoice;
use Yii;
use yii\console\Controller;

/**
 * @property BaseModule $module
 */
class VelopayController extends Controller
{
    public function actionGetPayment($invoiceId) {
        $invoice = $this->module->businessLogic->getInvoiceById($invoiceId);
        $gateway = $this->module->businessLogic->getGatewayBySid($invoice->gateway_sid);
        $gateway->setInvoice($invoice);

        $payment = $gateway->getPaymentInfo();
        print_r($payment);
    }

    public function actionProcessPayments() {
        $invoiceClass = $this->module->businessLogic->getInvoiceClass();

        $query = $invoiceClass::find();

        foreach ($query->batch(10) as $batch) {
            foreach ($batch as $invoice) {
                /** @var Invoice $invoice */
                $uid = uniqid('', true);

                try {
                    $gateway = $this->module->businessLogic->getGatewayBySid($invoice->gateway_sid);
                    $gateway->setInvoice($invoice);
                    $gateway->process();

                    $this->afterGatewayResponse($gateway, $uid);
                } catch (\Exception $e) {
                    Yii::error("$uid Gateway request failed, exception: " . Debug::stacktraceText($e), 'app/velopay');
                    continue;
                }
            }
        }
    }

    /**
     * @param $gateway AbstractGateway
     * @param $uid string
     * @return string
     * @throws \Exception
     */
    protected function afterGatewayResponse($gateway, $uid) {
        /** @var Invoice $invoice */
        $invoice = $gateway->getInvoice();
        $invoice->ensureSave();

        switch ($gateway->status) {
            case $gateway::STATUS_CANCELED:
                Yii::info("$uid Result: canceled, orig status {$gateway->status} ", 'app/velopay');
                $invoice->ensureDelete();
                return;
            case $gateway::STATUS_SUGGEST_USER_REDIRECT:
            case $gateway::STATUS_PENDING:
                Yii::info("$uid Result: pending, orig status {$gateway->status} ", 'app/velopay');
                return;
            case $gateway::STATUS_PAID:
                Yii::info("$uid Registering payment: amount " . $invoice->getAmountValue() . ' payment fqid ' . $gateway->statusDetails['paymentFqid'], 'app/velopay');
                $this->module->businessLogic->registerPayment($invoice);
                $invoice->ensureDelete();
                Yii::info("$uid Result: paid, orig status {$gateway->status} ", 'app/velopay');
                return;
            case $gateway::STATUS_ERROR:
                Yii::error("$uid Gateway error, orig status {$gateway->status} ", 'app/velopay');
                throw new \Exception("Gateway error");
            default:
                Yii::error("$uid Gateway status unknown, orig status {$gateway->status} ", 'app/velopay');
                throw new \Exception("Unexpected status: " . $gateway->status);
        }
    }

}