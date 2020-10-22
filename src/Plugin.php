<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect;

use jmauzyk\commerce\cardconnect\gateways\Gateway;
use jmauzyk\commerce\cardconnect\plugin\Services;
use jmauzyk\commerce\cardconnect\variables\Variable;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\commerce\services\Gateways;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

/**
 * Class CardConnect
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.0.0
 *
 */
class Plugin extends \craft\base\Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.2.0';

    // Traits
    // =========================================================================

    use Services;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_setPluginComponents();

		Event::on(
			Gateways::class,
			Gateways::EVENT_REGISTER_GATEWAY_TYPES,
			function (RegisterComponentTypesEvent $event) {
				$event->types[] = Gateway::class;
			}
		);

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('commerceCardconnect', Variable::class);
            }
        );
    }
}
