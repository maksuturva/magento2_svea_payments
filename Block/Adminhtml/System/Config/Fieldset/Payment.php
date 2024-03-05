<?php

namespace Svea\SveaPayment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\Config;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Paypal\Block\Adminhtml\System\Config\Fieldset\Payment as PayPalPayment;

class Payment extends PayPalPayment
{
    /**
     * @var Manager
     */
    private Manager $moduleManager;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param Config $backendConfig
     * @param Manager $moduleManager
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context             $context,
        Session             $authSession,
        Js                  $jsHelper,
        Config              $backendConfig,
        Manager             $moduleManager,
        array               $data = [],
        ?SecureHtmlRenderer $secureRenderer = null)
    {
        parent::__construct($context, $authSession, $jsHelper, $backendConfig, $data, $secureRenderer);
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param $element
     *
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        // If Magento_Paypal module is disabled, add needed header class for Svea logo
        if (!$this->moduleManager->isEnabled('Magento_Paypal')) {
            $html = Fieldset::_getHeaderTitleHtml($element);
            $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong></div>';

            return $html;
        }

        return parent::_getHeaderTitleHtml($element);
    }
}
