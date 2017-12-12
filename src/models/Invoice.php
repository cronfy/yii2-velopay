<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 07.11.17
 * Time: 13:38
 */

namespace cronfy\yii2Velopay\models;

use cronfy\velopay\InvoiceInterface;
use cronfy\velopay\OrderPaymentDataInterface;
use Money\Currency;
use Money\Money;
use yii\base\BaseObject;

class Invoice extends BaseObject implements InvoiceInterface
{
    /**
     * @var Order
     */
    public $order;

    /**
     * @var OrderPaymentDataInterface
     */
    protected $_storage;

    /**
     * @var Money
     */
    protected $_amount;
    /**
     * @return Money
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    public function setAmount(Money $value)
    {
        $this->_amount = $value;
    }

    public function getAmountCurrency() {
        $amount = $this->getAmount();
        return (string) $amount->getCurrency();
    }

    public function getAmountValue() {
        $amount = $this->getAmount();
        return $amount->getAmount() / 100;
    }

    public function setStorage(OrderPaymentDataInterface $value) {
        $this->_storage = $value;
    }

    /**
     * @return OrderPaymentDataInterface
     */
    public function getStorage() {
        return $this->_storage;
    }

    public function isAmountEqualsTo($value, $currency) {
        $money = new Money($value * 100, new Currency($currency));
        return $this->getAmount()->equals($money);
    }

}