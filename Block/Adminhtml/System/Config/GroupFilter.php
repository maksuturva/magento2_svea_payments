<?php

namespace Svea\SveaPayment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Svea\SveaPayment\Gateway\Config\Config;

class GroupFilter extends Fieldset
{
    /**
     * @var Config
     */
    private $sveaConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigInterface;

    public function __construct(
        Context              $context,
        Session              $authSession,
        Js                   $jsHelper,
        Config               $config,
        ScopeConfigInterface $scopeConfigInterface,
        ?SecureHtmlRenderer  $secureRenderer = null,
        array                $data = []
    ) {
        $this->sveaConfig = $config;
        $this->scopeConfigInterface = $scopeConfigInterface;
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
    }

    /**
     * Visibility toggle for migration section
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '';
        if (!empty($this->scopeConfigInterface->getValue(
            $this->sveaConfig::MAKSUTURVA_SELLERID,
            scopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0))) {
            $html .= parent::render($element);
        }

        return $html;
    }
}
