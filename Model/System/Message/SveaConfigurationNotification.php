<?php

namespace Svea\SveaPayment\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Svea\SveaPayment\Gateway\Config\Config;

class SveaConfigurationNotification implements MessageInterface
{
    /**
     * Message identity
     */
    const MESSAGE_IDENTITY = 'svea_configuration_notification';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    public function __construct(Config $config, UrlInterface $urlInterface)
    {
        $this->config = $config;
        $this->urlInterface = $urlInterface;
    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        return empty($this->config->getSellerId());
    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $paymentsUrl = $this->urlInterface->getUrl('adminhtml/system_config/edit/section/svea_payment');

        return __('<a href="' . $paymentsUrl . '">Svea Payments</a>: Seller ID is mandatory. It is needed in order to retrieve available payment methods from Svea.');
    }

    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
