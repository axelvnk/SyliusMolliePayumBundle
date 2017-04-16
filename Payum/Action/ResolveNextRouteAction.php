<?php

namespace Axelvnk\SyliusMolliePayumBundle\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Sylius\Bundle\PayumBundle\Request\ResolveNextRoute;
use Sylius\Component\Core\Model\PaymentInterface;

class ResolveNextRouteAction implements ActionInterface
{
    /**
     * @param ResolveNextRoute $request
     */
    public function execute($request)
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        if ($payment->getState() === PaymentInterface::STATE_COMPLETED) {
            $request->setRouteName('sylius_shop_order_thank_you');

            return;
        }

        $request->setRouteName('sylius_shop_checkout_complete');
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return $request instanceof ResolveNextRoute;
    }
}
