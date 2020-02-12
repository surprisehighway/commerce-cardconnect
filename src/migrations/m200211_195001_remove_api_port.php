<?php

namespace jmauzyk\commerce\cardconnect\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\commerce\Plugin;
use craft\commerce\records\Gateway;

/**
 * m200211_195001_remove_api_port migration.
 */
class m200211_195001_remove_api_port extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $schemaVersion = Craft::$app->projectConfig->get(
            'plugins.commerce-cardconnect.schemaVersion',
            true
        );

        if (version_compare($schemaVersion, '1.1', '<')) {
            $rows = (new Query())
                ->from('{{%commerce_gateways}}')
                ->where(['type' => 'jmauzyk\commerce\cardconnect\gateways\Gateway'])
                ->all();

            $service = Plugin::getInstance()->getGateways();
            foreach ($rows as $row) {
                $settings = json_decode($row['settings']);
                if (isset($settings->apiPort)) {
                    unset($settings->apiPort);
                    $this->update('{{%commerce_gateways}}', ['settings' => json_encode($settings)], ['id' => $row['id']]);
                    $gateway = $service->getGatewayById($row['id']);
                    $service->saveGateway($gateway);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200211_195001_remove_api_port cannot be reverted.\n";
        return false;
    }
}
