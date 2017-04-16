<?php

namespace Axelvnk\SyliusMolliePayumBundle\Payum\Action;

use Axelvnk\SyliusMolliePayumBundle\Payum\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;

abstract class BaseApiAwareAction implements ActionInterface, ApiAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritdoc}
     */
    public function setApi($api)
    {
        if (!$api instanceof Api) {
            throw new UnsupportedApiException;
        }

        $this->api = $api;
    }
}
