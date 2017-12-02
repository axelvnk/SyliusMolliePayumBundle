<?php

namespace Axelvnk\SyliusMolliePayumBundle\Payum;

use Mollie_API_Client;
use Sylius\Component\Core\Model\PaymentInterface;

class Api
{
    /**
     * @var Mollie_API_Client
     */
    private $client;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param string $apiKey
     * @throws \Mollie_API_Exception
     */
    public function __construct($apiKey)
    {
        $this->client = new Mollie_API_Client;
        $this->client->setApiKey($apiKey);
        $this->apiKey = $apiKey;
    }

    /**
     * @param float $amount
     * @param string $description
     * @param string $redirectUrl
     * @param string $webhookUrl
     * @param PaymentInterface $payment
     * @return array
     */
    public function createTransaction($amount, $description, $redirectUrl, $webhookUrl, $payment)
    {
        $payment = $this->client->payments->create(
            [
                'amount' => $amount,
                'description' => $description,
                'redirectUrl' => $redirectUrl,
                'webhookUrl' => $webhookUrl,
                'metadata' => $this->getPaymentMetadata($payment),
                'method' => strtolower($payment->getMethod()->getCode()),
            ]
        );

        return [
            'transaction_id' => $payment->id,
            'payment_url' => $payment->getPaymentUrl(),
        ];
    }

    /**
     * @param string $transactionId
     * @return \stdClass
     */
    public function getTransactionData($transactionId)
    {
        return $this->client->payments->get($transactionId);
    }

    /**
     * @param PaymentInterface $payment
     * @return array
     */
    protected function getPaymentMetadata(PaymentInterface $payment)
    {
        return [
            'payment_id' => $payment->getId(),
            'order_number' => $payment->getOrder()->getNumber(),
        ];
    }
}
