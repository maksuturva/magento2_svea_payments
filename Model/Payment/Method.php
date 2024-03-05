<?php

namespace Svea\SveaPayment\Model\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Substitution;
use Magento\Payment\Model\MethodInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use function in_array;
use function str_contains;

class Method
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param MethodInterface $method
     *
     * @return bool
     */
    public function isSvea(MethodInterface $method): bool
    {
        return str_contains($method->getCode(), 'svea');
    }

    /**
     * @param MethodInterface $method
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isMaksuturva(MethodInterface $method): bool
    {
        $code = $method->getCode();
        if ($code === Substitution::CODE) {
            $infoInstance = $method->getInfoInstance();
            $code = $infoInstance->getMethod();
        }

        return $this->isMaksuturvaCode($code);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function isMaksuturvaCode(string $code): bool
    {
        return str_contains($code, 'maksuturva');
    }

    /**
     * @return string[]
     */
    public function getSveaCollectionFilter(): array
    {
        return ['like' => '%svea%'];
    }

    /**
     * @return string[]
     */
    public function getMaksuturvaCollectionFilter(): array
    {
        return ['like' => '%maksuturva%'];
    }

    /**
     * @param ?string $method
     *
     * @return bool
     */
    public function isDelayedCapture(?string $method): bool
    {
        return $method && in_array($method, $this->config->getDelayedCaptureMethods());
    }
}
