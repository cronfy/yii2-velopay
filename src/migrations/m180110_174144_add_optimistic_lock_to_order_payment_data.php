<?php

use yii\db\Migration;

/**
 * Class m180110_174144_add_optimistic_lock_to_order_payment_data
 */
class m180110_174144_add_optimistic_lock_to_order_payment_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('order_payment_data', 'version', $this->bigInteger()->notNull()->defaultValue(0));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180110_174144_add_optimistic_lock_to_order_payment_data cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180110_174144_add_optimistic_lock_to_order_payment_data cannot be reverted.\n";

        return false;
    }
    */
}
