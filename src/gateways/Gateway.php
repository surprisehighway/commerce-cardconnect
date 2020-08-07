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
use craft\commerce\models\Transaction;
use craft\commerce\omnipay\base\CreditCardGateway;
use craft\web\View;

use Omnipay\Cardconnect\Gateway as OmnipayGateway;
use Omnipay\Common\AbstractGateway;

use yii\helpers\Html;

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

    // Private Properties
    // =========================================================================

    /**
     * @var array
     */
    private $_srcParams = [
        'tokenizewheninactive' => 'false',
        'formatinput' => 'true',
        'placeholder' => 'Card Number',
        'css' => 'body{margin: 0;} .error{border-color: red; color: red;}'
    ];

    /**
     * @var array
     */
    private $_options = [
        'id' => 'tokenFrame',
        'name' => 'tokenFrame',
        'frameborder' => '0',
        'scrolling' => 'no',
        'height' => '22'
    ];

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

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $defaults = [
            'paymentForm' => $this->getPaymentFormModel()
        ];

        $params = array_merge($defaults, $params);

        if (isset($params['useTokens']) && $params['useTokens'] === false) {
            $template = 'commerce/_components/gateways/_creditCardFields';
        } else {
            $template = 'commerce-cardconnect/paymentForm';
            $params['gateway'] = $this;
        }

        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = Craft::$app->getView()->renderTemplate($template, $params);
        $view->setTemplateMode($previousMode);

        return $html;
    }

    /**
     * Outputs iframe and hidden input for tokenized number field
     *
     * @param array|null $srcParams Key value pairs of for query params to pass to the tokenizer src
     * @param array $options Key value pairs for attributes to use during tag creation
     * @return string
     */
    public function getTokenizedNumberInput(?array $srcParams = null, array $options = []): string
    {
        if ($srcParams === null) {
            $srcParams = $this->_srcParams;
            $srcParams['tokenizewheninactive'] = Craft::$app->getRequest()->isMobileBrowser(true) ? 'true' : 'false';
        }

        if (isset($srcParams['css'])) {
            $srcParams['css'] = trim(preg_replace("/ {2,}/", ' ', str_replace(["\r\n", "\r", "\n", "\t"], '', $srcParams['css'])));
        }

        $iframeSrc = $this->_getIframeUrl();
        if (!empty($srcParams)) {
            $iframeSrc .= '?' . http_build_query($srcParams, null, '&', PHP_QUERY_RFC3986);
        }
        $options = array_merge($this->_options, $options);
        $options['src'] = $iframeSrc;
        $html = Html::tag('iframe', '', $options) . PHP_EOL . Html::hiddenInput('number', null, ['id' => 'number']);
        return $html;
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
    protected function createPaymentRequest(Transaction $transaction, $card = null, $itemBag = null): array
    {
        $request = parent::createPaymentRequest($transaction, $card, $itemBag);
        $request['orderId'] = $transaction->orderId;

        return $request;
    }

    /**
    * @inheritdoc
    */
    protected function getGatewayClassName()
    {
        return '\\'.OmnipayGateway::class;
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets tokenizer endpoint base URL depending on test mode
     *
     * @return string
     */
    private function _getIframeUrl(): string {
        $baseUrl = $this->testMode ? 'fts-uat.cardconnect.com' : $this->apiHost;
        return "https://{$baseUrl}/itoke/ajax-tokenizer.html";
    }
}
