<?php

namespace Svea\SveaPayment\Gateway\Request\DeliveryInfo;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog as Logger;
use Svea\SveaPayment\Api\Delivery\MethodResolverInterface;
use Svea\SveaPayment\Gateway\SubjectReader;
use Svea\SveaPayment\Model\Request\DeliveryDataSpecification;

class MethodResolver implements MethodResolverInterface
{
    /**
     * @var DeliveryDataSpecification
     */
    private $specification;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string[]
     */
    private $mappings;

    public function __construct(
        DeliveryDataSpecification $specification,
        SubjectReader             $subjectReader,
        Logger                    $logger,
        $mappings = []
    ) {
        $this->specification = $specification;
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
        $this->mappings = $mappings;
    }

    /**
     * @param array $buildSubject
     *
     * @return string
     * @throws LocalizedException
     */
    public function resolve(array $buildSubject): string
    {
        $method = $this->subjectReader->read($buildSubject, DataBuilder::METHOD_CODE);

        return $this->resolveValid($method);
    }

    /**
     * @param string $method
     *
     * @return string
     * @throws LocalizedException
     */
    private function resolveValid(string $method): string
    {
        if ($this->mappings && isset($this->mappings[$method])) {
            $method = $this->mappings[$method];
        } elseif (!$this->isValid($method)) {
            $resolved = $this->specification->getFallbackDefaultCode();
            if (!empty($resolved)) {
                $this->logger->info(\sprintf(
                    'Delivery data: fallback method of "%s" used for "%s"',
                    $resolved,
                    $method
                ));
            }
            $method = $resolved;
        }

        $this->validate($method);

        return $method;
    }

    /**
     * @param string $method
     *
     * @throws LocalizedException
     */
    private function validate(string $method): void
    {
        if (!$this->isValid($method)) {
            throw new LocalizedException(\__('Invalid delivery method id "%1".', $method));
        }
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    private function isValid(string $method): bool
    {
        return \in_array($method, $this->specification->getAllowedDeliveryCodes());
    }
}
