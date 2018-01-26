<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 01.11.17
 * Time: 11:29
 */

namespace cronfy\yii2Velopay\controllers;

use cronfy\yii2Velopay\models\Invoice;
use cronfy\velopay\gateways\AbstractGateway;
use cronfy\velopay\Helper;
use Yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

abstract class VelopayController extends Controller
{
    use VelopayControllerTrait;

    abstract protected function getOrderById($order_id);
    abstract protected function getOrderUrl($order);

    /**
     * @inheritdoc
     * @param Action $action the action to be executed.
     */
    public function beforeAction($action)
    {
        if ($action->id == 'notification') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    protected function process($method, $order_id, $gatewayMethod) {
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

        if ($order->paid_status === $order::PAID_STATUS_YES) {
            return $this->redirect($this->getOrderUrl($order));
        }

        $invoice = $this->getInvoice($order);

        $gateway = $this->getGatewayByPaymentMethod($method);
        $storageSid = $this->getStorageSid($invoice, $gateway);
        $gateway->setStorage(OrderPaymentData::findOne(['sid' => $storageSid]) ?: new OrderPaymentData(['sid' => $storageSid]));
        $gateway->setInvoice($invoice);
        $gateway->returnUrl = Url::toRoute(['velopay/process', 'method' => $method, 'order_id' => $order_id], true);

        switch ($gatewayMethod) {
            case 'start':
                $gateway->start();
                break;
            case 'process':
                $gateway->process();
                break;
            default:
                throw new \Exception("Unknown gateway method");
        }

        return $this->afterGatewayResponse($gateway, $order);
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

        if ($order->paid_status === $order::PAID_STATUS_YES) {
            return $this->redirect($this->getOrderUrl($order));
        }

        $gateway = $this->getGatewayByPaymentMethod($method);

        $invoice = $this->createInvoiceByOrder($order);
        $invoice->setGatewaySid($gateway->getSid());
        $invoice->ensureSave();
        $invoice->refresh(); // иначе срабатывает optimistic lock при последующем сохранении в afterGatewayResponse()

        $gateway->setInvoice($invoice);
        $gateway->setReturnUrl(Url::toRoute(['velopay/process', 'invoice_id' => $invoice->getExternalSid()], true));

        $gateway->start();

        return $this->afterGatewayResponse($gateway);
    }

    public function actionProcess() {
        if (!$invoiceSid = Yii::$app->request->get('invoice_id')) {
            throw new BadRequestHttpException();
        }

        try {
            $invoice = $this->getInvoiceByExternalSid($invoiceSid);
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

        $gateway = $this->getGateway($invoice->getGatewaySid());

        $gateway->setInvoice($invoice);

        $gateway->process();

        return $this->afterGatewayResponse($gateway);
    }

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
                $this->registerPayment($invoice, $gateway->statusDetails['paymentFqid']);
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

    abstract protected function registerPayment($invoice, $paymentFqid);

    abstract protected function addPaymentToOrder($order, $data);

    /**
     * @param $order
     * @return Invoice
     */
    abstract protected function getInvoiceByOrder($order);

    /**
     * @param $sid string
     * @return Invoice
     */
    abstract protected function getInvoiceByExternalSid($sid);

    /**
     * @param $order
     * @return Invoice
     */
    abstract protected function createInvoiceByOrder($order);

    protected function getStorageSid($invoice, $gateway) {
        $sidData = $this->getInvoiceOrderPaymentDataSidData($invoice);

        if (isset($sidData['gate'])) {
            throw new \Exception("Invoice should not return 'gate' in sid data");
        }

        $sidData['gate'] = $gateway->getSid();
        ksort($sidData); // для единообразия, иначе не сможем проверять уникальность
        $sid = Json::encode($sidData);

        return $sid;
    }

    /**
     * @param $method
     * @return AbstractGateway
     */
    abstract protected function getGatewayByPaymentMethod($method);

    /**
     * @param $name
     * @return AbstractGateway
     */
    abstract protected function getGateway($name);

    /**
     * @param $invoice Invoice
     * @return array
     */
    abstract protected function getInvoiceOrderPaymentDataSidData($invoice);

}