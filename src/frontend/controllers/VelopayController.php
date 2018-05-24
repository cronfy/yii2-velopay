<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 01.11.17
 * Time: 11:29
 */

namespace cronfy\yii2Velopay\frontend\controllers;

use cronfy\yii2Velopay\common\models\Invoice;
use cronfy\yii2Velopay\frontend\Module;
use cronfy\velopay\gateways\AbstractGateway;
use cronfy\velopay\Helper;
use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * @property Module $module
 */
class VelopayController extends Controller {

    public function beforeAction($action)
    {
        if ($action->id == 'notification') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    protected function getOrderById($orderId) {
        return $this->module->businessLogic->getOrderById($orderId);
    }

    protected function getIsOrderPayable($order) {
        return $this->module->businessLogic->getIsOrderPayable($order);
    }

    protected function getOrderRoute($order) {
        return $this->module->businessLogic->getOrderRoute($order);
    }

    protected function getInvoiceById($invoiceId) {
        return $this->module->businessLogic->getInvoiceById($invoiceId);
    }

    /**
     * @param string $methodSid
     * @return AbstractGateway
     */
    protected function getGatewayByPaymentMethod($methodSid) {
        return $this->module->businessLogic->getGatewayByPaymentMethod($methodSid);
    }

    /**
     * @param string $gatewaySid
     * @return AbstractGateway
     */
    protected function getGatewayBySid($gatewaySid) {
        return $this->module->businessLogic->getGatewayBySid($gatewaySid);
    }

    protected function getGatewaySid($gateway) {
        return $this->module->businessLogic->getGatewaySid($gateway);
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    protected function registerPayment($invoice) {
        return $this->module->businessLogic->registerPayment($invoice);
    }

    /**
     * @param $order
     * @return Invoice
     */
    protected function createInvoiceByOrder($order) {
        return $this->module->businessLogic->createInvoiceByOrder($order);
    }

    public function actionStart($method, $order_id) {
        try {
            $order = $this->getOrderById($order_id);
            if (!$order) {
                throw new \Exception('no order');
            }
        } catch (\Exception $e) {
            if (!YII_DEBUG) {
                sleep(5); // защита от перебора
                throw new NotFoundHttpException("Заказ не найден");
            }
            throw $e;
        }

        if (!$this->getIsOrderPayable($order)) {
            return $this->redirect($this->getOrderRoute($order));
        }

        $gateway = $this->getGatewayByPaymentMethod($method);

        $invoice = $this->createInvoiceByOrder($order);
        $invoice->gateway_sid = $this->getGatewaySid($gateway);
        $invoice->ensureSave();
        $invoice->refresh(); // иначе срабатывает optimistic lock при последующем сохранении в afterGatewayResponse()

        $gateway->setInvoice($invoice);
        $gateway->setReturnUrl(Url::toRoute(['/velopay/velopay/process', 'invoice_id' => $invoice->id], true));

        $gateway->start();

        return $this->afterGatewayResponse($gateway);
    }

    public function actionProcess() {
        if (!$invoiceId = Yii::$app->request->get('invoice_id')) {
            throw new BadRequestHttpException();
        }

        try {
            $invoice = $this->getInvoiceById($invoiceId);
            if (!$invoice) {
                throw new \Exception('no invoice');
            }
        } catch (\Exception $e) {
            if (!YII_DEBUG) {
                sleep(5); // защита от перебора
                throw new NotFoundHttpException("Счет не найден");
            }
            throw $e;
        }

        $gateway = $this->getGatewayBySid($invoice->gateway_sid);

        $gateway->setInvoice($invoice);

        $gateway->process();

        return $this->afterGatewayResponse($gateway);
    }

    /**
     * @param $gateway AbstractGateway
     * @return string
     * @throws \Exception
     */
    protected function afterGatewayResponse($gateway) {
        /** @var Invoice $invoice */
        $invoice = $gateway->getInvoice();
        $invoice->ensureSave();

        switch ($gateway->status) {
            case $gateway::STATUS_CANCELED:
                $invoice->ensureDelete();
                return $this->render('canceled.html.twig');
            case $gateway::STATUS_SUGGEST_USER_REDIRECT:
                Helper::redirect($gateway->statusDetails);
                break;
            case $gateway::STATUS_PAID:
                $this->registerPayment($invoice);
                $invoice->ensureDelete();
                return $this->render('thankyou.html.twig');
            case $gateway::STATUS_PENDING:
                return $this->render('pending.html.twig');
            case $gateway::STATUS_ERROR:
                throw new \Exception("Gateway error");
            default:
                throw new \Exception("Unexpected status: " . $gateway->status);
        }
    }

}

abstract class Wait {
//    use VelopayControllerTrait;


    public function actionNotification() {
        Yii::info('Notification GET ' . VarDumper::dumpAsString(Yii::$app->request->get()), 'app/velopay');
        Yii::info('Notification POST ' . VarDumper::dumpAsString(Yii::$app->request->post()), 'app/velopay');
        Yii::info('Notification BODY ' . Yii::$app->request->getRawBody(), 'app/velopay');

        $data = Json::decode(Yii::$app->request->getRawBody());

        if ($data['type'] !== 'notification') throw new \Exception("Not a notification");

        $uid = 'Notification process';

        switch ($data['event']) {
            case 'payment.waiting_for_capture':
                $payment_id = $data['object']['id'];
                if (!$orderPaymentData = OrderPaymentData::findOne(['gateway_sid' => $payment_id])) {
                    throw new \Exception("Got notification, but transaction not found: " . $payment_id);
                }
                $this->processTransactionByOrderPaymentData($orderPaymentData, $uid, false);
                break;
            default:
                throw new \Exception("Got notification, but event is unknown: " . $data['event']);
        }
        die();
    }

}