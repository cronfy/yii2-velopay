<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 07.11.17
 * Time: 13:38
 */

namespace cronfy\yii2Velopay\models;

use cronfy\experience\yii2\exceptions\EnsureSaveException;
use cronfy\velopay\InvoiceInterface;
use Money\Currency;
use Money\Money;
use paulzi\jsonBehavior\JsonBehavior;
use paulzi\jsonBehavior\JsonField;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property $id integer
 * @property $gateway_transaction_sid string
 * @property $invoiceData JsonField
 */
abstract class Invoice extends ActiveRecord implements InvoiceInterface
{
    public static function tableName()
    {
        return 'velopay_invoice';
    }

    public function behaviors() {
        return [
            [
                'class' => JsonBehavior::className(),
                'attributes' => ['gatewayData'],
            ],
            [
                'class' => JsonBehavior::className(),
                'attributes' => ['invoiceData'],
            ],
            [
                'class' => TimestampBehavior::className(),
            ],
        ];
    }

    public function ensureSave() {
        if (!$this->save()) {
            throw new EnsureSaveException("Failed to save model");
        }
    }

    public function ensureDelete() {
        if (false === parent::delete()) {
            throw new EnsureSaveException();
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

    public function setGatewayTransactionSid($sid)
    {
        $this->gateway_transaction_sid = $sid;
    }

    public function getGatewayTransactionSid()
    {
        return $this->gateway_transaction_sid;
    }

    public function optimisticLock()
    {
        return 'version';
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

    public function setGatewaySid($value)
    {
        $this->invoiceData['gatewaySid'] = $value;
    }

    public function getGatewaySid()
    {
        return $this->invoiceData['gatewaySid'];
    }

    public function setAmount(Money $value)
    {
        $this->invoiceData['value'] = $value->getAmount() / 100;
        $this->invoiceData['currency'] = (string) $value->getCurrency();
    }

    public function getAmountCurrency() {
        return $this->invoiceData['currency'];
    }

    public function getAmountValue() {
        return $this->invoiceData['value'];
    }

    public function isAmountEqualsTo($value, $currency) {
        $foreign = new Money($value * 100, new Currency($currency));
        $local = new Money($this->getAmountValue() * 100, new Currency($this->getAmountCurrency()));
        return $local->equals($foreign);
    }

}