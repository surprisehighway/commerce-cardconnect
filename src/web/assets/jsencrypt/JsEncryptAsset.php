<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\web\assets\jsencrypt;

use craft\web\AssetBundle;

/**
 * JSEncrypt asset bundle
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.0
 *
 */
class JsEncryptAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@jmauzyk/commerce/cardconnect/../lib/jsencrypt';

        $this->depends = [];

        $this->js = [
            'jsencrypt.min.js',
        ];

        parent::init();
    }
}
