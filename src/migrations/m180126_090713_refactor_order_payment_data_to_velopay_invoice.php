<?php

use yii\db\Migration;

/**
 * Class m180126_090713_refactor_order_payment_data_to_velopay_invoice
 */
class m180126_090713_refactor_order_payment_data_to_velopay_invoice extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameTable('order_payment_data', 'velopay_invoice');
        $this->renameColumn('velopay_invoice', 'sid', 'invoiceData');
        $this->renameColumn('velopay_invoice', 'data', 'gatewayData');
        $this->renameColumn('velopay_invoice', 'gateway_sid', 'gateway_transaction_sid');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180126_090713_refactor_order_payment_data_to_velopay_invoice cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180126_090713_refactor_order_payment_data_to_velopay_invoice cannot be reverted.\n";

        return false;
    }
    */
}
