<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\variables;

use jmauzyk\commerce\cardconnect\Plugin;

/**
 * CardConnect variables
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.1
 *
 */
class Variable
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an array of published urls for CardSecure payment form asset bundle
     *
     * @return array
     */
    public function getCardSecureAssetBundleUrls(): array
    {
        return Plugin::getInstance()->getAssetBundles()->getCardSecureAssetBundleUrls();
    }
}
