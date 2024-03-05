<?php

namespace Svea\SveaPayment\Model\Ui\Payment;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Svea\SveaPayment\Api\Checkout\PaymentMethodCollectorInterface;
use Svea\SveaPayment\Api\PartPaymentCalculator\CalculatorProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Data
     */
    private Data $methodResolver;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var PaymentMethodCollectorInterface
     */
    private PaymentMethodCollectorInterface $methodCollector;

    /**
     * @var TemplateResolver
     */
    private TemplateResolver $templateResolver;

    /**
     * @var CalculatorProviderInterface
     */
    private CalculatorProviderInterface $calculatorProvider;

    /**
     * @var string
     */
    private string $code;

    /**
     * @param Data $methodResolver
     * @param UrlInterface $urlBuilder
     * @param PaymentMethodCollectorInterface $methodCollector
     * @param TemplateResolver $templateManager
     * @param CalculatorProviderInterface $calculatorProvider
     * @param string $code
     */
    public function __construct(
        Data                            $methodResolver,
        UrlInterface                    $urlBuilder,
        PaymentMethodCollectorInterface $methodCollector,
        TemplateResolver                $templateManager,
        CalculatorProviderInterface     $calculatorProvider,
        string                          $code
    ) {
        $this->methodResolver = $methodResolver;
        $this->urlBuilder = $urlBuilder;
        $this->methodCollector = $methodCollector;
        $this->templateResolver = $templateManager;
        $this->calculatorProvider = $calculatorProvider;
        $this->code = $code;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->code => $this->getMethodConfig(),
            ],
        ];
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getMethodConfig(): array
    {
        $method = $this->methodResolver->getMethodInstance($this->code);
        $availableMethods = $this->methodCollector->getAvailableQuoteMethods($method);

        return [
            'methodData' => $this->methodCollector->getQuoteMethods(),
            'methods' => $availableMethods,
            'defaultPaymentMethod' => '',
            'template' => $this->templateResolver->getTemplate($method),
            'preselectRequired' => (bool)$method->getConfigData('is_preselect_required'),
            'paymentDataUrl' => $this->getPaymentDataUrl(),
            'isPartPaymentCalculatorAvailable' => $this->calculatorProvider->isAvailableForMethods($availableMethods)
        ];
    }

    /**
     * @return string
     */
    private function getPaymentDataUrl(): string
    {
        return $this->urlBuilder->getUrl('svea_svea_payment/redirect/getpaymentdata', [
            '_secure' => true,
        ]);
    }
}
