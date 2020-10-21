<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\web\assets\cardsecurepaymentform;

use jmauzyk\commerce\cardconnect\web\assets\jsencrypt\JsEncryptAsset;

use craft\web\AssetBundle;

/**
 * CardSecure payment form asset bundle
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.0
 *
 */
class CardSecurePaymentFormAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__;

        $this->js = [
            'js/paymentForm.min.js',
        ];

        $this->depends = [
            JsEncryptAsset::class,
        ];

        parent::init();
    }
}
