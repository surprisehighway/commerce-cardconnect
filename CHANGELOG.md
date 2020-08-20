# Release Notes for CardConnect for Craft Commerce

## 1.3.1 - 2020-08-20
* Updated tokenized number input to make UI in Craft CP
* Fixed order ID not displaying in CardPointe

## 1.3.0 - 2020-08-07
> {warning} As of this release, payment forms generated using `getPaymentFormHtml()` will use tokenized card numbers by default via CardConnect's [Hosted iFrame Tokenizer](https://developer.cardconnect.com/hosted-iframe-tokenizer). For information on customizing form appearance, custom payment forms, and reverting to clear PANs, see [the documentation](https://github.com/jmauzyk/commerce-cardconnect/blob/master/README.md).

* Updated `getPaymentFormHtml()` method to implement Hosted iFrame Tokenizer
* Added `jmauzyk\commerce\cardconnect\gateways\Gateway::getTokenizedNumberInput()`
* Updated Omnipay: CardConnect to 1.2.0
* _Authorize_ and _Purchase_ requests now correctly send the order id instead of transaction hashes

## 1.2.0 - 2020-03-12
* Added composer support for Craft Commerce 3

## 1.1.0 - 2020-02-12
* Updated to be compatible with [new API URLs](https://developer.cardconnect.com/changelog/cardpointe-gateway-api#date-updated-9-16-2019)

## 1.0.1 - 2019-10-30
* Updated Omnipay: CardConnect to 1.0.1
* Fixed missing address
* Added address line 2 to request

## 1.0.0 - 2019-09-19
* Initial release
