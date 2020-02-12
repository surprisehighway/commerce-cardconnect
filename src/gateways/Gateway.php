<?php
/**
* CardConnect for Craft Commerce plugin for Craft CMS 3.x
*
* CardConnect integration for Craft Commerce 2
*
* @link      http://www.joel-king.com
* @copyright Copyright (c) 2019 jmauzyk
*/

namespace jmauzyk\commerce\cardconnect\gateways;

use Craft;
use craft\commerce\omnipay\base\CreditCardGateway;

use Omnipay\Common\AbstractGateway;
use Omnipay\Cardconnect\Gateway as OmnipayGateway;

/**
* @author    jmauzyk
* @package   CardConnect
* @since     1.0.0
*/
class Gateway extends CreditCardGateway
{
    // Public Properties
    // =========================================================================

    /**
    * @var string
    */
    public $merchantId;

    /**
    * @var string
    */
    public $apiHost;

    /**
    * @var string
    */
    public $apiUsername;

    /**
    * @var string
    */
    public $apiPassword;

    /**
    * @var bool
    */
    public $testMode = false;

    // Public Methods
    // =========================================================================

    /**
    * @inheritdoc
    */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'CardConnect');
    }

    /**
    * @inheritdoc
    */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('commerce-cardconnect/gatewaySettings', ['gateway' => $this]);
    }

    // Protected Methods
    // =========================================================================

    /**
    * @inheritdoc
    */
    protected function createGateway(): AbstractGateway
    {
        /** @var Gateway $gateway */
        $gateway = static::createOmnipayGateway($this->getGatewayClassName());

        $gateway->setMerchantId($this->merchantId);
        $gateway->setApiHost($this->apiHost);
        $gateway->setApiUsername($this->apiUsername);
        $gateway->setApiPassword($this->apiPassword);
        $gateway->setTestMode($this->testMode);

        return $gateway;
    }

    /**
    * @inheritdoc
    */
    protected function getGatewayClassName()
    {
        return '\\'.OmnipayGateway::class;
    }
}
