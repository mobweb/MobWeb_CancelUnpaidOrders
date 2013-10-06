# MobWeb_CancelUnpaidOrders extension for Magento

Automatically cancel orders with a certain order status and payment method after a specified amount of time.

This extension automatically cancels orders that fit your cancelling criteria after a specified amount of time. Just select one or more order statuses and payment methods and specifiy the expiration time. Once that expiration time is reached for an order and it also has one of the specified order statuses and payment methods, the order will be deleted.

### Usage Example

A lot of stores offer their customers some sort of pre-payment method. One example would be a pre-payment invoice. As soon as the customer places their order they will be sent a PDF invoice that's payable within a few days. Only when the payment is received will the order be shipped out.

Now a certain number of these customers will decide differently and just not make the pre-payment. For the store owner this means manually cancelling the unpaid orders after the payment period has run out. This laborious can be fully automated with this extension!

The store owner just has to set up the extension once, select the "pre-payment invoice" payment method and the "Pending" or "Processing" order status as well as the customer's payment period. The extension will then automatically cancel all the orders that have exceeded this payment period and still have an order status of "Pending" or "Processing".

## Installation

Install using [colinmollenhour/modman](https://github.com/colinmollenhour/modman/).

## Questions? Need help?

Most of my repositories posted here are projects created for customization requests for clients, so they probably aren't very well documented and the code isn't always 100% flexible. If you have a question or are confused about how something is supposed to work, feel free to get in touch and I'll try and help: [info@mobweb.ch](mailto:info@mobweb.ch).