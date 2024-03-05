<?php

namespace Svea\SveaPayment\Gateway\Http;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Svea\SveaPayment\Gateway\Config\Config;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $httpMethod;

    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config,
        SerializerInterface $serializer,
        string $service,
        string $httpMethod = 'POST'
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config =  $config;
        $this->serializer = $serializer;
        $this->service = $service;
        $this->httpMethod = $httpMethod;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request) : TransferInterface
    {
        return $this->transferBuilder
            ->setMethod($this->httpMethod)
            ->setBody($request)
            ->setAuthUsername($this->getUsername())
            ->setAuthPassword($this->getPassword())
            ->setUri($this->getUrl())
            ->build();
    }

    private function getUrl() : string
    {
        return $this->config->getCommunicationUrl($this->service);
    }

    private function getUsername(): string
    {
        return $this->config->getSellerId();
    }

    private function getPassword() : string
    {
        return $this->config->getSecretKey();
    }
}
