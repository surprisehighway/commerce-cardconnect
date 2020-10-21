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

use jmauzyk\commerce\cardconnect\errors\ProfileException;
use jmauzyk\commerce\cardconnect\errors\PaymentSourceException;
use jmauzyk\commerce\cardconnect\models\CreditCardPaymentForm;
use jmauzyk\commerce\cardconnect\models\Profile;
use jmauzyk\commerce\cardconnect\Plugin;
use jmauzyk\commerce\cardconnect\web\assets\cardsecurepaymentform\CardSecurePaymentFormAsset;

use Craft;
use craft\commerce\errors\PaymentException;
use craft\commerce\models\Address;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\omnipay\base\CreditCardGateway;
use craft\commerce\records\PaymentSource as PaymentSourceRecord;
use craft\web\Request;
use craft\web\View;

use Omnipay\Cardconnect\Gateway as OmnipayGateway;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\ResponseInterface;

use yii\base\NotSupportedException;
use yii\helpers\Html;

/**
 * Class Gateway
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.0.0
 *
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
     * @var string
     */
    public $tokenization = 'iframe';

    /**
     * @var bool
     */
    public $validateBeforeSave = true;

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
    private $_cpSrcParams = [
        'css' =>
        "body {
            margin: 0;
        }

        input {
            width: 100%;
            font-family: system-ui, BlinkMacSystemFont, -apple-system, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu',
                'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            font-size: 14px;
            line-height: 20px;
            color: #3f4d5a;
            min-height: 3px;
            box-sizing: border-box;
            -webkit-appearance: none;
            appearance: none;
            padding: 6px 9px;
            border-radius: 3px;
            border: 1px solid rgba(96, 125, 159, 0.25);
            background-color: #fbfcfe;
            box-shadow: inset 0 1px 4px -1px rgba(96, 125, 159, 0.25);
            background-clip:
            padding-box;
        }

        input:focus {
            outline: none;
            border-color: rgba(96, 125, 159, 0.8);
        }

        input::placeholder {
            color: hsl(211, 13%, 65%);
            opacity: 1;
        }

        .error {
            border: 1px solid #EF4E4E !important;
        }"
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

    /**
     * @var array
     */
    private $_cpOptions = [
        'class' => 'fullwidth',
        'height' => '34'
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
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new CreditCardPaymentForm();
    }

    /**
     * @inheritdoc
     */
    public function supportsPaymentSources(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function populateRequest(array &$request, BasePaymentForm $paymentForm = null)
    {
        if ($paymentForm && (!isset($request['profile']) || !$request['profile'])) {
            $request['profile'] = $paymentForm->profile;
        }
    }

    /**
     * Gets the API subdomain based on saved API settings and test mode
     *
     * @return string
     */
    public function getApiSubdomain(): string
    {
        return $this->testMode ? 'fts-uat' : explode('.', $this->apiHost)[0];
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
        $template = 'commerce-cardconnect/paymentForm';
        $request = Craft::$app->getRequest();
        $defaults = [
            'paymentForm' => $this->getPaymentFormModel(),
            'tokenization' => $this->tokenization,
            'cp' => $request->isCpRequest,
            'subdomain' => $this->getApiSubdomain()
        ];
        $params = array_merge($defaults, $params);

        if ($this->tokenization === 'iframe') {
            $srcParams = $params['srcParams'] ?? null;
            $options = $params['options'] ?? [];
            $params['tokenizedNumberInput'] = $this->getIframeNumberInput($srcParams, $options, $request);
        }

        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        if ($this->tokenization === 'cardsecure') {
            $view->registerAssetBundle(CardSecurePaymentFormAsset::class);
        }

        $html = $view->renderTemplate($template, $params);
        $view->setTemplateMode($previousMode);

        return $html;
    }

    /**
     * Outputs iframe and hidden input for tokenized number field
     *
     * @param array|null $srcParams Key value pairs of for query params to pass to the tokenizer src
     * @param array $options Key value pairs for attributes to use during tag creation
     * @return string
     * @deprecated 1.4.0. Use getIframeNumberInput() instead.
     */
    public function getTokenizedNumberInput(?array $srcParams = null, array $options = []): string
    {
        Craft::$app->getDeprecator()->log('Gateway::getTokenizedNumberInput()', '`Gateway::getTokenizedNumberInput()` has been deprecated. Use `Order::getIframeNumberInput()` instead.');

        return $this->getIframeNumberInput($srcParams, $options);
    }

    /**
     * Outputs iframe and hidden input for tokenized number field
     *
     * @param array|null $srcParams Key value pairs of for query params to pass to the tokenizer src
     * @param array $options Key value pairs for attributes to use during tag creation
     * @param Request|null $request Context of current request to determine rendering styles etc.
     * @return string
     */
    public function getIframeNumberInput(?array $srcParams = null, array $options = [], ?Request $request = null): string
    {
        if (!$request) {
            $request = Craft::$app->getRequest();
        }
        if ($srcParams === null) {
            $srcParams = $this->_srcParams;
            if ($request->isCpRequest) {
                $srcParams = array_merge($srcParams, $this->_cpSrcParams);
            }
            $srcParams['tokenizewheninactive'] = $request->isMobileBrowser(true) ? 'true' : 'false';
        }

        if (isset($srcParams['css'])) {
            $srcParams['css'] = trim(preg_replace("/ {2,}/", ' ', str_replace(["\r\n", "\r", "\n", "\t"], '', $srcParams['css'])));
        }

        $iframeSrc = $this->_getIframeUrl();
        if (!empty($srcParams)) {
            $iframeSrc .= '?' . http_build_query($srcParams, null, '&', PHP_QUERY_RFC3986);
        }
        $options = array_merge($this->_options, $options);
        if ($request->isCpRequest) {
            $options = array_merge($options, $this->_cpOptions);
        }
        $options['src'] = $iframeSrc;
        $html = Html::tag('iframe', '', $options) . PHP_EOL . Html::hiddenInput('number', null, ['id' => 'cc-number']);
        return $html;
    }

    /**
     * @inheritdoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
        try {
            $profile = $this->getCardconnectProfile($userId);
            $commerce = Commerce::getInstance();
            $cart = $commerce->getCarts()->getCart();
            $request = Craft::$app->getRequest();
            $billingAddressId = (int)$request->getParam('billingAddressId');
            $newBillingAddressArray = $request->getParam('billingAddress');

            if ($billingAddressId) {
                $customer = $commerce->getCustomers()->getCustomerByUserId($userId);

                if (!$customer) {
                    throw new NotSupportedException(Craft::t('commerce', 'You need a billing address to save a payment source.'));
                }

                $billingAddress = $commerce->getAddresses()->getAddressByIdAndCustomerId($billingAddressId, $customer->id);
                $cart->setBillingAddress($billingAddress);
                $cart->billingAddressId = $billingAddressId;
            } else if ($newBillingAddressArray) {
                $customerService = $commerce->getCustomers();
                $customerId = $customerService->getCustomer()->id;
                $customer = $customerService->getCustomerById($customerId);

                $newBillingAddress = new Address();

                foreach ($newBillingAddressArray as $key => $val) {
                    $newBillingAddress->$key = $val;
                }

                if ($customerService->saveAddress($newBillingAddress)) {
                    $customer->primaryBillingAddressId = $newBillingAddress->id;

                    if(!$customerService->saveCustomer($customer)) {
                        throw new PaymentSourceException('Unable to update primary address.');
                    }
                } else {
                    throw new PaymentSourceException('Unable to save address.');
                }

                $cart->setBillingAddress($newBillingAddress);
                $cart->billingAddressId = null;
            }

            if (!$address = $cart->getBillingAddress()) {
                $customer = $commerce->getCustomers()->getCustomerByUserId($userId);

                if (!$customer || !($address = $customer->getPrimaryBillingAddress())) {
                    throw new NotSupportedException(Craft::t('commerce', 'You need a billing address to save a payment source.'));
                }

                $cart->setBillingAddress($address);
                $cart->billingAddressId = $address->id;
            }

            $card = $this->createCard($sourceData, $cart);

            if ($this->validateBeforeSave) {
                $authParams = [
                    'card' => $card,
                    'currency' => $cart->paymentCurrency,
                    'amount' => 0
                ];

                $authRequest = $this->gateway()->authorize($authParams);
                $authResponse = $this->sendRequest($authRequest);

                if (!$authResponse->isSuccessful()) {
                    throw new PaymentSourceException('Card validation failed: ' . $authResponse->getMessage());
                }
            }

            $params = [
                'card' => $card,
                'currency' => $cart->paymentCurrency
            ];
            if ($profile !== null) {
                $params['profile'] = $profile->reference;
            }

            $this->populateRequest($params, $sourceData);
            $createCardRequest = $this->gateway()->createCard($params);
            $response = $this->sendRequest($createCardRequest);

            if (!$response->isSuccessful()) {
                throw new PaymentSourceException('Account creation failed: ' . $response->getMessage());
            }

            if ($profile === null) {
                $newProfile = new Profile([
                    'userId' => $userId,
                    'gatewayId' => $this->id,
                    'reference' => $response->getProfileId()
                ]);

                if (!Plugin::getInstance()->getProfiles()->saveProfile($newProfile)) {
                    throw new ProfileException('Could not save profile: ' . implode(', ', $newProfile->getErrorSummary(true)));
                }
            }

            $paymentSource = new PaymentSource([
                'userId' => $userId,
                'gatewayId' => $this->id,
                'token' => $this->extractCardReference($response),
                'response' => $response->getData(),
                'description' => $this->extractPaymentSourceDescription($response)
            ]);

            return $paymentSource;
        } catch (\Throwable $exception) {
            throw new PaymentSourceException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function deletePaymentSource($token): bool
    {
        try {
            $tokenFragments = explode('/', $token);
            $params = [
                'profile' => $tokenFragments[0],
                'acct' => $tokenFragments[1] ?? null
            ];
            $profile = Plugin::getInstance()->profiles->getProfileByReference($tokenFragments[0]);
            $paymentSources = PaymentSourceRecord::find()
                ->where(['userId' => $profile->userId])
                ->andWhere(['gatewayId' => $this->id]);

            if ($paymentSources->count() > 1) {
                $getCardRequest = $this->gateway()->getCard($params);
                $cardconnectProfile = $this->sendRequest($getCardRequest);

                if ($cardconnectProfile->isDefaultAcct()) {
                    $newDefault = $paymentSources->andWhere(['not', ['token' => $token]])->orderBy('dateCreated asc')->one();

                    $updateCardParams = [
                        'profile' => $newDefault->token,
                        'defaultacct' => 'Y',
                        'profileupdate' => 'Y'
                    ];
                    $updateCardRequest = $this->gateway()->updateCard($updateCardParams);
                    $updateCardResponse = $this->sendRequest($updateCardRequest);

                    if (!$updateCardResponse->isSuccessful()) {
                        throw new PaymentSourceException('Unable to change default account: ' . $updateCardResponse->getMessage());
                    }
                }
            }

            $deleteCardRequest = $this->gateway()->deleteCard($params);
            $response = $this->sendRequest($deleteCardRequest);
        } catch (\Throwable $throwable) {
            Craft::error($throwable->getMessage(), __METHOD__);
            return false;
        }

        if (!$response->isSuccessful()) {
            return false;
        }

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function getGatewayClassName()
    {
        return '\\'.OmnipayGateway::class;
    }

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
        $request['orderId'] = (string)$transaction->orderId;

        return $request;
    }

    /**
     * Extract a card reference from a response
     *
     * @param ResponseInterface $response The response to use
     *
     * @return string
     * @throws PaymentException on failure
     */
    protected function extractCardReference(ResponseInterface $response): string
    {
        if (!$response->isSuccessful()) {
            throw new PaymentException($response->getMessage());
        }

        return (string) $response->getProfileId() . '/' . $response->getAcctId();
    }

    /**
     * Extract a payment source description from a response.
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    protected function extractPaymentSourceDescription(ResponseInterface $response): string
    {
        $data = $response->getData();
        $acctType = '';
        switch ($data['accttype']) {
            case 'VISA':
                $acctType = 'Visa';
                break;

            case 'MC':
                $acctType = 'Mastercard';
                break;

            case 'DISC':
                $acctType = 'Discover';
                break;

            case 'AMEX':
                $acctType = 'Amex';
                break;

            default:
                $acctType = 'Card';
                break;
        }
        return $acctType . ' ending in ' . substr($data['token'], -4);
    }

    /**
     * Get the CardConnect profile for a User.
     *
     * @param int $userId
     *
     * @return Profile|null
     */
    protected function getCardconnectProfile(int $userId): ?Profile
    {
        $user = Craft::$app->getUsers()->getUserById($userId);
        $profiles = Plugin::getInstance()->getProfiles();
        $profile = $profiles->getProfile($this->id, $user);

        if ($profile !== null) {
            $getCardRequest = $this->gateway()->getCard(['profile' => $profile->reference]);
            $cardconnectProfile = $this->sendRequest($getCardRequest);

            if (!$cardconnectProfile->isSuccessful()) {
                // Delete local profile record
                $profiles->deleteProfileById($profile->id);
                $profile = null;
            }
        }

        return $profile;
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets tokenizer endpoint base URL depending on test mode
     *
     * @return string
     */
    private function _getIframeUrl(): string {
        $subdomain = $this->getApiSubdomain();
        return "https://{$subdomain}.cardconnect.com/itoke/ajax-tokenizer.html";
    }
}
