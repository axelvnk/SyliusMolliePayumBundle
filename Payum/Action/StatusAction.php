<?php

namespace Axelvnk\SyliusMolliePayumBundle\Payum\Action;

use Doctrine\ORM\EntityManagerInterface;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityToken;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;

class StatusAction extends BaseApiAwareAction
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param GetStatus $request
     */
    public function execute($request)
    {
        $payment = $request->getModel();

        if ($payment instanceof PaymentSecurityToken) {
            $payment = $this->entityManager->find($payment->getDetails()->getClass(), $payment->getDetails()->getId());
        }

        if (!$payment instanceof PaymentInterface) {
            throw new \RuntimeException(sprintf('Payment with id "%s" was not found', $payment->getId()));
        }

        $paymentDetails = $payment->getDetails();

        if (!isset($paymentDetails['transaction_id'])) {
            throw new \RuntimeException(sprintf('Payment with id "%s" does not have a transaction id set', $payment->getId()));
        }

        $transaction = $this->api->getTransactionData($paymentDetails['transaction_id']);

        switch ($transaction->status) {
            case 'open':
                $request->markNew();
                break;

            case 'pending':
                $request->markPending();
                break;

            case 'paid':
                $request->markCaptured();
                break;

            case 'cancelled':
                $request->markCanceled();
                break;

            case 'expired':
            case 'failed':
            default:
                $request->markExpired();
                break;
        }

        if ($request->getModel() instanceof PaymentSecurityToken) {
            $request->setModel($payment);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return $request instanceof GetStatusInterface;
    }
}
