<?php

namespace Svea\SveaPayment\Model\Ui\Collated;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Svea\SveaPayment\Api\Checkout\PaymentMethodCollectorInterface;
use Svea\SveaPayment\Api\PartPaymentCalculator\CalculatorProviderInterface;
use Svea\SveaPayment\Model\Ui\Payment\TemplateResolver;
use function explode;
use function in_array;
use function preg_replace;

class ConfigProvider extends \Svea\SveaPayment\Model\Ui\Payment\ConfigProvider implements ConfigProviderInterface
{
    public const SUBPAYMENT_STEPS = [
        'payment_method_subgroup_1',
        'payment_method_subgroup_2',
        'payment_method_subgroup_3',
        'payment_method_subgroup_4',
        'payment_method_subgroup_5',
    ];

    /**
     * @var PaymentMethodCollectorInterface
     */
    private PaymentMethodCollectorInterface $methodCollector;

    /**
     * @var Data
     */
    private Data $methodResolver;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

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
        $this->calculatorProvider = $calculatorProvider;
        $this->code = $code;
        parent::__construct(
            $methodResolver,
            $urlBuilder,
            $methodCollector,
            $templateManager,
            $calculatorProvider,
            $code
        );
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

        return [
            'methodData' => $this->methodCollector->getQuoteMethods(),
            'methods' => $this->subMethods($method),
            'template' => 'Svea_SveaPayment/payment/collated_icons_form',
            'preselectRequired' => (bool)$method->getConfigData('is_preselect_required'),
            'paymentDataUrl' => $this->getPaymentDataUrl(),
        ];
    }

    /**
     * @param $method
     *
     * @return array
     */
    private function subMethods($method): array
    {
        $data = [];
        foreach (self::SUBPAYMENT_STEPS as $subPaymentStep) {
            $methods = $this->filterSubPaymentMethods($method, $subPaymentStep);
            if (empty($methods)) {
                continue;
            }
            $data[$subPaymentStep] = [
                'code' => $subPaymentStep,
                'title' => $method->getConfigData($subPaymentStep . '_title'),
                'methods' => $methods,
                'defaultPaymentMethod' => '',
                'preselectRequired' => (bool)$method->getConfigData('is_preselect_required'),
                'isPartPaymentCalculatorAvailable' => $this->calculatorProvider->isAvailableForMethods($methods)
            ];
        }

        return $data;
    }

    /**
     * @param $method
     * @param $subPaymentStep
     *
     * @return array
     */
    private function filterSubPaymentMethods($method, $subPaymentStep): array
    {
        $allowedMethods = [];
        $filteredMethods = [];
        $methods = $this->methodCollector->getAvailableQuoteMethods($method);
        if ($method->getConfigData($subPaymentStep . '_method_filter')) {
            $methodFilter = $method->getConfigData($subPaymentStep . '_method_filter');
            $methodFilter = preg_replace('/\s+/', '', $methodFilter);
            $allowedMethods = explode(',', $methodFilter);
        }
        if (empty($allowedMethods)) {
            return [];
        }
        foreach ($methods as $method) {
            if (in_array($method['code'], $allowedMethods)) {
                $filteredMethods[] = $method;
            }
        }

        return $filteredMethods;
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
