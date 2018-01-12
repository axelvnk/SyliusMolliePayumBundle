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
use Symfony\Component\Translation\TranslatorInterface;

class CaptureAction extends BaseApiAwareAction implements GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
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
            $this->getTransactionDescription($payment),
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

    /**
     * @param PaymentInterface $payment
     * @return string
     */
    protected function getTransactionDescription(PaymentInterface $payment) {
        $key = 'sylius.mollie_payum_action.payment.description';
        $parameters = [
            '%order_number%' => $payment->getOrder()->getNumber(),
            '%payment_method_name%' => $payment->getMethod()->getName(),
            '%payment_method_description%' => $payment->getMethod()->getDescription(),
        ];

        $translation = $this->translator->trans($key, $parameters);
        if ($translation === $key) {
            $translation = 'Your order';
        }

        return $translation;
    }
}
