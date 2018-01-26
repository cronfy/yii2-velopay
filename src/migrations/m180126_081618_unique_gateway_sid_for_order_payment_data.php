<?php

use yii\db\Migration;

/**
 * Class m180126_081618_unique_gateway_sid_for_order_payment_data
 */
class m180126_081618_unique_gateway_sid_for_order_payment_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex('gateway_sid|unique', 'order_payment_data', 'gateway_sid', true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180126_081618_unique_gateway_sid_for_order_payment_data cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180126_081618_unique_gateway_sid_for_order_payment_data cannot be reverted.\n";

        return false;
    }
    */
}
