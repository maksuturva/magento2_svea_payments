<?php
namespace Svea\SveaPayment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class OrderStatusQueryButton extends Field
{
    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element) : string
    {
        $this->setElement($element);
        $data = $element->getOriginalData();
        $button = $this->createButtonBlock($data['button_label'], $this->resolveUrl($data['time_period'], $data['query_type'], $element->getScopeId()));

        return $button->toHtml();
    }

    /**
     * @param string $timePeriod
     * @param string $type
     *
     * @return string
     */
    private function resolveUrl(string $timePeriod, string $type, string $storeId)
    {
        return $this->getUrl('svea_payment/order/statusCheck', ['period' => $timePeriod, 'query_type' => $type, 'store_id' => $storeId]);
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
            ]);
    }
}
