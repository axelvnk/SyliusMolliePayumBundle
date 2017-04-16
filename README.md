# SyliusMolliePayumBundle

Welcome to the SyliusMolliePayumBundle - a Payum implementation of the Mollie gateway to use in your Sylius (~beta) webshop.

For details on how to get started with SyliusMolliePayumBundle, keep on reading.

All code included in the SyliusMolliePayumBundle is released under the MIT or BSD license.

## Installation

### Step 1 - Install SyliusMolliePayumBundle using composer
Edit your composer.json to include the bundle as a dependency.

```js
{
    "require": {
        "axelvnk/sylius-mollie-payum-bundle": "dev-master",
    }
}
```

Open up a command line window and tell composer to download the new dependency.

``` bash
$ php composer.phar update axelvnk/sylius-mollie-payum-bundle
```

### Step 2 - Register the bundle in your AppKernel file


``` php
// app/AppKernel.php

<?php

public function registerBundles()
{
    $bundles = array(
        ...
        new Axelvnk\SyliusMolliePayumBundle\SyliusMolliePayumBundle(),
    );
}
```

### Step 3 - Include the bundles payment gateway config

``` yml
// app/config/config.yml

imports:
    - { resource: "@SyliusMolliePayumBundle/Resources/config/config.yml" }

```

### Step 4 - Configure the bundle to use your Mollie API key

``` yml
// app/config/parameters.yml

parameters:
    axelvnk.payum.mollie_api_key: YOUR_API_KEY

```

### Step 5 - Add gateway config to Sylius database
This is just necessary to be able to select 'mollie' as a gateway for your payment methods through the admin interface. The config and factory name aren't even used, so don't worry about being correct.


``` sql
INSERT INTO `sylius_gateway_config` (`config`, `gateway_name`, `factory_name`)
VALUES ('a:1:{s:6:\"apiKey\";s:35:\"test_xxxxxxxxxxxxxxxxxxxxxxxxx\";}', 'mollie', 'axelvnk_mollie');
```

## Modifying default behavior

You can always change behavior by changing the parameter value for the classes.

``` yml
// app/config/parameters.yml

parameters:
    axelvnk.payum.action.capture.class: Your\Own\CaptureAction
    axelvnk.payum.action.status.class: Your\Own\StatusAction
    axelvnk.payum.action.notify.class: Your\Own\NotifyAction
    axelvnk.payum.action.resolve_next_route.class: Your\Own\ResolveNextRouteAction
```

## Nice to know..
By default, Sylius moves an order's checkout state to completed before knowing if the payment is fulfilled or not. 

In my ResolveNextRouteAction you can see that I redirect to the last step in the checkout process if the payment has failed for some reason. But Sylius removes a cart if the state is not longer new. So you will end up with an empty cart upon returning from Mollie if the payment has been cancelled or has failed. 

To prevent this from happening you should either fiddle with the state machine, or you simply do this in your custom CaptureAction :

``` php
<?php

$order->setCheckoutState(OrderCheckoutStates::STATE_PAYMENT_SELECTED);
```

And this in your custom ResolveNextRouteAction

``` php
<?php

/** @var Payment $payment */
$payment = $request->getModel();

/** @var Order $order */
$order = $payment->getOrder();

if ($payment->getState() === Payment::STATE_COMPLETED) {
    $order->setCheckoutState(OrderCheckoutStates::STATE_COMPLETED);
    $this->orderEmailManager->sendConfirmationEmail($order);
    $request->setRouteName('sylius_shop_order_thank_you');

    return;
}
$order->setState(OrderInterface::STATE_CART);
$order->setCheckoutState(OrderCheckoutStates::STATE_PAYMENT_SELECTED);
$order->setShippingState(OrderShippingStates::STATE_READY);
$order->setPaymentState(OrderPaymentStates::STATE_AWAITING_PAYMENT);

$request->setRouteName('sylius_shop_checkout_complete');
```
