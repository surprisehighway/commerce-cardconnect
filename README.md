# CardConnect for Craft Commerce

This plugin provides a [CardConnect](https://cardconnect.com/) integration for [Craft Commerce](https://craftcms.com/commerce).

## Requirements

This plugin requires Craft CMS 3.1.0 and Commerce 2.0.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

### From the Plugin Store

Go to the Plugin Store in your project's Control Panel and search for *CardConnect for Craft Commerce*. Then click on the *Install* button in its modal window.

### With Composer

1. Open your terminal and go to your Craft project:

    ```Shell
    cd /path/to/project
    ```

2. Tell Composer to load the plugin:
    ```Shell
    composer require jmauzyk/commerce-cardconnect
    ```

3. In the Control Panel, go to Settings → Plugins and click the *Install* button for CardConnect for Craft Commerce; or tell Craft to install the plugin:

	```Shell
    ./craft install/plugin commerce-cardconnect
    ```

## Setup

### CardConnect API Credentials

You will receive production API credentials including a username, password, merchant ID, and endpoint from CardConnect when you create an account. For test credentials and test card numbers, see the [CardConnect Support Center](https://support.cardconnect.com/securenet-migration#accessing-cardConnects-uat-environment).

### Adding a CardConnect Gateway

Go to Commerce → System Settings → Gateways and click the *New Gateway* button. Set the gateway type to *CardConnect*, fill in your test or production API credentials, and save.

## Payment Forms

After installing and configuring the gateway, form fields will submit a transaction to CardConnect.

### Default Payment Form

The basic Commerce payment form can be output using the following line of code.

```Twig
{{ cart.gateway.getPaymentFormHtml({})|raw }}
```

As of v1.3.0, payment forms generated using `getPaymentFormHtml()` will use tokenized card numbers by default via CardConnect's [Hosted iFrame Tokenizer](https://developer.cardconnect.com/hosted-iframe-tokenizer). To change the tokenization method or revert to using clear text card numbers, select the appropriate _Tokenization_ configuration under the gateway's settings. Per CardConnect's documentation it is __strongly recommended__ that you tokenize the clear text card data before passing it into a request.

### Custom Payment Form

If additional customization is needed, custom payment forms can be used so long as they include inputs for the following attributes:
* `firstName`
* `lastName`
* An account `number` in the form of:
    * Clear text card number
    * CardSecure token
* An expiration date in the form of:
	* Individual `month` and `year`
	* Combined `expiry` (MM/YYYY)
* `cvv`

To use iframe tokenization with custom payment forms, use the following line of code to output the hosted iframe tokenizer with an accompanying hidden `number` input. Be sure to include your own JS event handler like the _Primary Payment Page Event Listener_ shown in the [Hosted iFrame Tokenizer documentation](https://developer.cardconnect.com/hosted-iframe-tokenizer#implementing-the-hosted-iFrame).

```Twig
{{ cart.gateway.getIframeNumberInput()|raw }}
```

To use CardSecure API tokenization with custom payment forms, use the code below as a basis. Element `id` and `name` attributes must be as shown in order to work with the tokenizer javascript.

```Twig
<!-- Card Number -->
    <input type="tel" id="cc-cardNumber" name="cardNumber" value="">
    <input type="hidden" id="cc-number" name="number" value="">


<!-- Expiry -->
    <select id="cc-month" name="month">
        <!-- options... -->
    </select>
    <select id="cc-year" name="year">
        <!-- options... -->
    </select>

    <!-- OR -->

    <input type="text" id="cc-expiry" name="expiry" value="">


<!-- CVV -->
    <input type="tel" id="cc-cvv" name="cvv" value="">


<!-- Tokenizer JS -->
    {% do view.registerAssetBundle("jmauzyk\\commerce\\cardconnect\\web\\assets\\cardsecurepaymentform\\CardSecurePaymentFormAsset") %}
    {% js %}initTokenizer('{{ gateway.getApiSubdomain() }}');{% endjs %}
```

To speed up tokenization performance, it's recommended to use code similar to below to preconnect or dns-prefetch before the tokenization is called.

```Twig
{% set subdomain = gateway.getApiSubdomain() %}
{% set apiUrl = 'https://' ~ subdomain ~ '.cardconnect.com' %}
{% do view.registerLinkTag({rel: 'preconnect', href: apiUrl}) %}
{% do view.registerLinkTag({rel: 'dns-prefetch', href: apiUrl}) %}
```

### Hosted iFrame Tokenizer Customization

The hosted iframe's appearance and functionality can modified using:

* `srcParams` — An array containing optional parameters passed with the iframe `src` URL
* `options` — An array containing attributes for constructing the `iframe` tag

Below is an example of how you can customize the default payment form and tokenized number input. All CSS will have unnecessary whitespace removed and be correctly encoded for the iframe tag. See the Hosted iFrame Tokenizer documentation for a full reference of [optional parameters](https://developer.cardconnect.com/hosted-iframe-tokenizer#optional-parameters) and [whitelisted CSS properties](https://developer.cardconnect.com/hosted-iframe-tokenizer#whitelisted-css-properties).

```Twig
{% set css %}
    body {
        margin: 0;
    }

    input {
        border: 1px solid #ccc;
        border-radius: 0;
        color: #333;
    }

    input:focus {
        border-color: #09c;
    }

    .error, .error:focus {
        border-color: #c00;
        color: #c00;
    }
{% endset %}
{% set srcParams = {
    tokenizewheninactive: craft.app.request.isMobileBrowser(true),
    formatinput: 'true',
    invalidinputevent: 'true',
    css: css
} %}
{% set options = {
    height: '42'
} %}

{# To output default payment form with customization... #}
{{ cart.gateway.getPaymentFormHtml({srcParams: srcParams, options: options})|raw }}

{# To output tokenized number input with customization... #}
{{ gateway.getIframeNumberInput(srcParams, options)|raw }}
```
