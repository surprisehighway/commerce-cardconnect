<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\records;

use craft\commerce\records\Gateway;
use craft\db\ActiveRecord;
use craft\records\User;
use yii\db\ActiveQueryInterface;

/**
 * CardConnect profile record
 *
 * @property int $id
 * @property int $userId
 * @property int $gatewayId
 * @property string $reference
 * @property Gateway $gateway
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.0
 *
 */
class Profile extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%cardconnect_profiles}}';
    }

    /**
     * Return the payment source's gateway
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGateway(): ActiveQueryInterface
    {
        return $this->hasOne(Gateway::class, ['gatewayId' => 'id']);
    }

    /**
     * Return the payment source's owner user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
