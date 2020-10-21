<?php

namespace jmauzyk\commerce\cardconnect\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m201001_131110_payment_sources migration.
 */
class m201001_131110_payment_sources extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%cardconnect_profiles}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'gatewayId' => $this->integer()->notNull(),
            'reference' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, '{{%cardconnect_profiles}}', 'gatewayId', '{{%commerce_gateways}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%cardconnect_profiles}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);

        $this->createIndex(null, '{{%cardconnect_profiles}}', 'gatewayId', false);
        $this->createIndex(null, '{{%cardconnect_profiles}}', 'userId', false);
        $this->createIndex(null, '{{%cardconnect_profiles}}', 'reference', true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201001_131110_payment_sources cannot be reverted.\n";
        return false;
    }
}
