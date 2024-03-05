<?php

namespace Svea\SveaPayment\Model\Source;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Svea\SveaPayment\Gateway\Config\Config;
use function sprintf;
use function strpos;
use function substr;

class StatusQuerySchedule extends Field
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context             $context,
        Config              $config,
        array               $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->config = $config;
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $schedule = $this->config->getStatusQuerySchedule();
        if (!$schedule) {
            return '';
        }
        $startingMinute = substr($schedule . '/', 0, strpos($schedule, '/'));

        return sprintf('At every 30th minute from %s through 59. Cron expression (%s).', $startingMinute, $schedule);
    }
}
