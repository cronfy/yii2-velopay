<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 01.11.17
 * Time: 11:29
 */

namespace cronfy\yii2Velopay\controllers;

use cronfy\yii2Velopay\models\OrderPaymentData;
use cronfy\velopay\gateways\AbstractGateway;
use cronfy\velopay\Helper;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

abstract class VelopayController extends Controller
{
    abstract protected function getOrderById($order_id);
    abstract protected function getOrderUrl($order);
    
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

        $gateway = $this->getGatewayByPaymentMethod($method);
        $invoice = $this->getInvoiceByOrder($order, $gateway);
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
        return $this->process($method, $order_id, 'start');
    }

    public function actionProcess($method, $order_id) {
        return $this->process($method, $order_id, 'process');
    }

    /**
     * @param $gateway AbstractGateway
     * @param $order Order
     * @return string
     * @throws \Exception
     */
    protected function afterGatewayResponse($gateway, $order) {
        /** @var Invoice $invoice */
        $invoice = $gateway->getInvoice();

        $storage = $invoice->getStorage();
        if (!$storage->getIsDeleted() && $storage->requiresSave()) {
            $invoice->getStorage()->ensureSave();
        }

        switch ($gateway->status) {
            case $gateway::STATUS_CANCELED:
                return $this->render('canceled.html.twig');
            case $gateway::STATUS_SUGGEST_USER_REDIRECT:
                Helper::redirect($gateway->statusDetails);
                break;
            case $gateway::STATUS_PAID:
                $this->addPaymentToOrder($order, [
                    'sum' => $invoice->getAmountValue(),
                    'paymentFqid' => $gateway->statusDetails['paymentFqid'],
                ]);
                return $this->render('thankyou.html.twig');
            case $gateway::STATUS_PENDING:
                return $this->render('pending.html.twig');
            default:
                throw new \Exception("Unexpected status: " . $gateway->status);
        }
    }

    abstract protected function addPaymentToOrder($order, $data);
    
    abstract protected function getInvoiceByOrder($order, $gateway);

    abstract protected function getGatewayByPaymentMethod($method);

    abstract protected function getGateway($name);

}