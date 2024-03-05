<?php

namespace Svea\SveaPayment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class MigrateConfigurationButton extends Field
{
    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->setElement($element);
        $data = $element->getOriginalData();
        $url = $this->getUrl('svea_payment/config/migrateConfig');
        $button = $this->createButtonBlock($data['button_label'], $url);

        return $button->toHtml();
    }

    /**
     * @param string $label
     * @param string $clickUrl
     *
     * @return WidgetButton
     * @throws LocalizedException
     */
    private function createButtonBlock(string $label, string $clickUrl): WidgetButton
    {
        return $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'label' => \__($label),
                'onclick' => \sprintf("setLocation('%s')", $clickUrl),
                'class' => 'action-add',
                'disabled' => false
            ]);
    }
}
