<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\models;

use jmauzyk\commerce\cardconnect\records\Profile as ProfileRecord;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\base\Model;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;

/**
 * CardConnect profile model
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.0
 *
 */
class Profile extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int Customer ID
     */
    public $id;

    /**
     * @var int The user ID
     */
    public $userId;

    /**
     * @var int The gateway ID.
     */
    public $gatewayId;

    /**
     * @var string Reference
     */
    public $reference;

    /**
     * @var User|null $_user
     */
    private $_user;

    /**
     * @var GatewayInterface|null $_user
     */
    private $_gateway;

    // Public Methods
    // =========================================================================

    /**
     * Returns the customer identifier
     *
     * @return string
     */
    public function __toString()
    {
        return $this->reference;
    }

    /**
     * Returns the user element associated with this profile.
     *
     * @return User|null
     */
    public function getUser()
    {
        if (null === $this->_user) {
            $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }

        return $this->_user;
    }

    /**
     * Returns the gateway associated with this profile.
     *
     * @return GatewayInterface|null
     */
    public function getGateway()
    {
        if (null === $this->_gateway) {
            $this->_gateway = Commerce::getInstance()->getGateways()->getGatewayById($this->gatewayId);
        }

        return $this->_gateway;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['reference'], 'unique', 'targetAttribute' => ['gatewayId', 'reference'], 'targetClass' => ProfileRecord::class],
            [['gatewayId', 'userId', 'reference'], 'required']
        ];
    }
}
