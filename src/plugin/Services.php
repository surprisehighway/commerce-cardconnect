<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\plugin;

use jmauzyk\commerce\cardconnect\services\AssetBundles;
use jmauzyk\commerce\cardconnect\services\Profiles;

/**
 * Trait Services
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.0
 *
 */
trait Services
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the profiles service
     *
     * @return Profiles The profiles service
     */
    public function getProfiles(): Profiles
    {
        return $this->get('profiles');
    }

    /**
     * Returns the asset bundles service
     *
     * @return AssetBundles The asset bundles service
     */
    public function getAssetBundles(): AssetBundles
    {
        return $this->get('assetBundles');
    }

    // Private Methods
    // =========================================================================

    /**
     * Set the components of the commerce plugin
     */
    private function _setPluginComponents()
    {
        $this->setComponents([
            'assetBundles' => AssetBundles::class,
            'profiles' => Profiles::class
        ]);
    }
}
