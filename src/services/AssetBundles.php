<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\services;

use Craft;
use yii\base\Component;

/**
 * Asset bundles service
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.1
 *
 */
class AssetBundles extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an array of published urls for CardSecure payment form asset bundle
     *
     * @return array|null
     */
    public function getCardSecureAssetBundleUrls(): array
    {
        $urls['jsencrypt'] = Craft::$app->assetManager->getPublishedUrl(
            '@jmauzyk/commerce/cardconnect/../lib/jsencrypt/jsencrypt.min.js',
            true
        );
        $urls['paymentForm'] = Craft::$app->assetManager->getPublishedUrl(
            '@jmauzyk/commerce/cardconnect/web/assets/cardsecurepaymentform/js/paymentForm.min.js',
            true
        );
        return $urls;
    }
}
