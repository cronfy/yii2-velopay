<?php

use yii\db\Migration;

/**
 * Class m180121_163600_add_gateway_sid_to_order_payemnt_data
 */
class m180121_163600_add_gateway_sid_to_order_payemnt_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('order_payment_data', 'gateway_sid', $this->string());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180121_163600_add_gateway_sid_to_order_payemnt_data cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180121_163600_add_gateway_sid_to_order_payemnt_data cannot be reverted.\n";

        return false;
    }
    */
}
