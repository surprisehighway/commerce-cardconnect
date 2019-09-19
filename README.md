# CardConnect for Craft Commerce

This plugin provides a [CardConnect](https://cardconnect.com/) integration for [Craft Commerce](https://craftcms.com/commerce).

## Requirements

This plugin requires Craft CMS 3.1.0 and Commerce 2.0.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project's Control Panel and search for *CardConnect for Craft Commerce*. Then click on the *Install* button in its modal window.

#### With Composer

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Tell Composer to load the plugin:

        composer require jmauzyk/commerce-cardconnect

3. In the Control Panel, go to Settings → Plugins and click the *Install* button for CardConnect for Craft Commerce; or tell Craft to install the plugin:

		./craft install/plugin commerce-cardconnect

## Setup

#### CardConnect API Credentials

You will receive production API credentials including a username, password, merchant ID, and endpoint from CardConnect when you create an account. For test credentials and test card numbers, see the [CardConnect Support Center](https://support.cardconnect.com/securenet-migration#accessing-cardConnects-uat-environment).

#### Adding a CardConnect Gateway

Go to Commerce → System Settings → Gateways and click the *New Gateway* button. Set the gateway type to *CardConnect*, fill in your test or production API credentials, and save.

## Payment Forms

After installing and configuring the gateway, form fields will submit a transaction to CardConnect.

#### Default Payment Form

The basic Commerce payment form can be output using the following line of code.

	{{ cart.gateway.getPaymentFormHtml({})|raw }}

#### Custom Payment Form

If additional customization is needed, custom payment forms can be used so long as they include inputs for the following attributes:
* `firstName`
* `lastName`
* `number`
* `cvv`
* An expiration date in the form of:
	* Individual `month` and `year`
	* Combined `expiry` (MM/YYYY)
