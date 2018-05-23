<?php

use yii\db\Migration;

/**
 * Handles the creation of table `invoice`.
 */
class m000000_000001_cronfy_y2vlp_create_velopay_invoice_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('velopay_invoice', [
            'id' => $this->primaryKey(),
            'invoiceData' => $this->string(1024),
            'gatewayData' => $this->string(1024),

            // что за шлюз
            'gateway_sid' => $this->string(),
            // id платежа от шлюза
            'gateway_invoice_sid' => $this->string(),

            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),

            'version' => $this->bigInteger()->notNull()->defaultValue(0),
        ], 'CHARACTER SET utf8 ENGINE=InnoDb');

        $this->createIndex(
            'gateway_sid,gateway_invoice_sid|unique',
            'velopay_invoice',
            ['gateway_sid', 'gateway_invoice_sid'],
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('velopay_invoice');
    }
}
