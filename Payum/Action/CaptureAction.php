<?php

namespace Axelvnk\SyliusMolliePayumBundle\Payum\Action;

use Axelvnk\SyliusMolliePayumBundle\Payum\Configuration;
use Doctrine\ORM\EntityManager;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityToken;
use Sylius\Component\Core\Model\PaymentInterface;

class CaptureAction extends BaseApiAwareAction implements GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Capture $request
     */
    public function execute($request)
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        $notifyToken = $this->tokenFactory->createNotifyToken(
          $request->getToken()->getGatewayName(),
          $request->getToken()->getDetails()
        );

        $transaction = $this->api->createTransaction(
            $payment->getAmount() / 100,
            'Your order',
            $request->getToken()->getAfterUrl(),
            $notifyToken->getTargetUrl(),
            $payment
        );

        $payment->setDetails(['transaction_id' => $transaction['transaction_id']]);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        throw new HttpResponse(null, 302, ['Location' => $transaction['payment_url']]);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof PaymentInterface &&
            $request->getToken() instanceof PaymentSecurityToken &&
            $request->getToken()->getGatewayName() === Configuration::GATEWAY_NAME
        ;
    }
}
