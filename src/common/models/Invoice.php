<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 07.11.17
 * Time: 13:38
 */

namespace cronfy\yii2Velopay\common\models;

use cronfy\velopay\InvoiceInterface;
use Money\Currency;
use Money\Money;
use paulzi\jsonBehavior\JsonBehavior;
use paulzi\jsonBehavior\JsonField;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property $id integer
 * @property $gateway_invoice_sid string
 * @property $gateway_sid string
 * @property $invoiceData JsonField
 * @property $gatewayData JsonField
 */
class Invoice extends ActiveRecord implements InvoiceInterface
{
    public static function tableName()
    {
        return 'velopay_invoice';
    }

    public function behaviors() {
        return [
            [
                'class' => JsonBehavior::class,
                'attributes' => ['gatewayData'],
            ],
            [
                'class' => JsonBehavior::class,
                'attributes' => ['invoiceData'],
            ],
            [
                'class' => TimestampBehavior::class,
            ],
        ];
    }

    public function optimisticLock()
    {
        return 'version';
    }




    public function ensureSave() {
        if (!$this->save()) {
            throw new \Exception("Failed to save model");
        }
    }

    public function ensureDelete() {
        if (false === $this->delete()) {
            throw new \Exception();
        }
        $this->_deleted = true;
    }

    protected $_deleted = false;
    public function getIsDeleted()
    {
        return $this->_deleted;
    }

    public function getGatewayData() {
        return $this->gatewayData;
    }

    public function setGatewayData($value) {
        if (is_a($value, JsonField::class)) {
            $this->gatewayData = $value;
        } else {
            $this->gatewayData->set($value);
        }
    }

    public function setGatewayInvoiceSid($sid)
    {
        $this->gateway_invoice_sid = $sid;
    }

    //  https://github.com/paulzi/yii2-json-behavior#how-to
    /**
     * @inheritdoc
     */
    public function isAttributeChanged($name, $identical = true)
    {
        if ($this->$name instanceof JsonField) {
            return (string)$this->$name !== $this->getOldAttribute($name);
        } else {
            return parent::isAttributeChanged($name, $identical);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDirtyAttributes($names = null)
    {
        $result = [];
        $data = parent::getDirtyAttributes($names);
        foreach ($data as $name => $value) {
            if ($value instanceof JsonField) {
                if ((string)$value !== $this->getOldAttribute($name)) {
                    $result[$name] = $value;
                }
            } else {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public function setAmount(Money $value)
    {
        $this->invoiceData['amount'] = $value->getAmount() / 100;
        $this->invoiceData['currency'] = (string) $value->getCurrency();
    }

    public function getAmountCurrency() {
        return $this->invoiceData['currency'];
    }

    public function getAmountValue() {
        return $this->invoiceData['amount'];
    }

    public function isAmountEqualsTo($value, $currency) {
        $foreign = new Money($value * 100, new Currency($currency));
        $local = new Money($this->getAmountValue() * 100, new Currency($this->getAmountCurrency()));
        return $local->equals($foreign);
    }

}