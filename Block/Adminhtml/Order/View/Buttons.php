<?php

namespace Svea\SveaPayment\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Button\SplitButton;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Config as SalesConfig;
use Svea\SveaPayment\Model\Order\Validators\SellerIdValidator;
use Svea\SveaPayment\Model\Payment\Method;
use function __;
use function sprintf;

class Buttons extends View
{
    /**
     * @var Method
     */
    private Method $paymentMethod;

    /**
     * @var SellerIdValidator
     */
    private SellerIdValidator $sellerIdValidator;

    public function __construct(
        Context           $context,
        Registry          $registry,
        SalesConfig       $salesConfig,
        Reorder           $reorderHelper,
        Method            $paymentMethod,
        SellerIdValidator $sellerIdValidator,
        array             $data = []
    ) {
        $this->paymentMethod = $paymentMethod;
        $this->sellerIdValidator = $sellerIdValidator;
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function _construct()
    {
        parent::_construct();
        if ($this->isVisible()) {
            $this->addButton('svea', [
                'label' => __('Svea'),
                'sort_order' => -1000,
                'class' => 'secondary svea',
                'button_class' => '',
                'class_name' => SplitButton::class,
                'options' => $this->getButtons(),
            ]);
        }

        return $this;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isVisible(): bool
    {
        return $this->getOrderId()
            && $this->getOrder()->getPayment()
            && $this->isValidPayment();
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isValidPayment(): bool
    {
        $method = $this->getOrder()->getPayment()->getMethodInstance();

        return ($this->paymentMethod->isSvea($method) && $this->sellerIdValidator->isValid($this->getOrder())) ||
            $this->paymentMethod->isMaksuturva($method);
    }

    /**
     * @return array[]
     */
    private function getButtons(): array
    {
        return [
            'status_query' => [
                'label' => __('Status Query'),
                'onclick' => sprintf('setLocation("%s")', $this->getUrl('svea_payment/order/statusCheck')),
            ],
        ];
    }
}
