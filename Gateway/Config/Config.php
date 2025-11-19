<?php

namespace Svea\SveaPayment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\ScopeInterface;
use Svea\SveaPayment\Model\Source\CommunicationEndpoint;
use function explode;
use function rtrim;
use function sprintf;
use function strpos;
use function substr;

class Config extends GatewayConfig
{
    const SVEA_PAYMENT_ID = 'svea_payment_id';
    const SVEA_SELLER_ID = 'svea_seller_id';
    const SELLER_ID = 'svea_config/svea_payment/sellerid';
    const SECRET_KEY = 'svea_config/svea_payment/secretkey';
    const LOCALE = 'general/locale/code';
    const KEY_VERSION = 'svea_config/svea_payment/keyversion';
    const CRON_ACTIVE = 'svea_config/svea_payment/cron_active';
    const COMMUNICATION_URL = 'svea_config/svea_payment/commurl';
    const COMMUNICATION_URL_CUSTOM = 'svea_config/svea_payment/commurl_custom';
    const DELAYED_CAPTURE_METHODS = 'svea_config/svea_payment/delayed_capture';
    const NEW_ORDER_STATUS = 'svea_config/svea_payment/order_status';
    const PAID_ORDER_STATUS = 'svea_config/svea_payment/paid_order_status';
    const CANCEL_ORDER_ON_FAILURE = 'svea_config/svea_payment/cancel_order_on_failure';
    const CAN_CANCEL_SETTLED = 'svea_config/svea_payment/can_cancel_settled';
    const DELIVERY_MODE = 'svea_config/svea_payment/delivery_mode';
    const DELIVERY_PAYMENT_METHODS = 'svea_config/svea_payment/delivery_payment_methods';
    const DELIVERY_PAYMENT_METHODS_SPECIFIC = 'svea_config/svea_payment/delivery_payment_methods_specific';
    const DELIVERY_CUSTOM_METHOD = 'svea_config/svea_payment/delivery_custom_method';
    const STATUS_QUERY_SCHEDULE = 'svea_config/svea_payment/status_query_schedule';
    const MANUAL_QUERY_CHECK_SHORT = 'svea_config/svea_payment/manual_query_check_short';
    const MANUAL_QUERY_CHECK_LONG = 'svea_config/svea_payment/manual_query_check_long';
    const RESTORE_SHOPPING_CART = 'svea_config/svea_payment/restore_shopping_cart';
    const PART_PAYMENT_CALCULATOR_ENABLED = 'svea_part_payment_calculator/calculator_config/enabled';
    const PART_PAYMENT_CALCULATOR_PLACEMENT = 'svea_part_payment_calculator/calculator_config/placement';
    const PART_PAYMENT_CALCULATOR_LAYOUT = 'svea_part_payment_calculator/calculator_config/layout';
    const PART_PAYMENT_CALCULATOR_PURCHASE_PRICE_VISIBILITY = 'svea_part_payment_calculator/calculator_config/purchase_price_visibility';
    const PART_PAYMENT_CALCULATOR_THRESHOLD_MINIMUM = 'svea_part_payment_calculator/calculator_config/threshold_minimum';
    const PART_PAYMENT_CALCULATOR_SCRIPT = 'svea_part_payment_calculator/calculator_config/script';
    const PAYMENT_SERVICE_URN = 'NewPaymentExtended.pmt';
    const PAYMENT_METHOD_URN = 'GetPaymentMethods.pmt';
    const  PAYMENT_STATUS_SERVICE_URN = 'PaymentStatusQuery.pmt';
    const PAYMENT_ADD_DELIVERYINFO_URN = 'addDeliveryInfo.pmt';
    const PAYMENT_UPDATE_DELIVERYINFO_URN = 'updateDeliveryInfo.pmt';
    const PAYMENT_DELETE_DELIVERYINFO_URN = 'deleteDeliveryInfo.pmt';
    const PAYMENT_CANCEL_URN = 'PaymentCancel.pmt';
    const PAYMENT_PLANS_URN = 'GetSveaPaymentPlanParams.pmt';
    /**
     *  Maksuturva sellerid config path
     */
    const MAKSUTURVA_SELLERID = "maksuturva_config/maksuturva_payment/sellerid";

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
                             ?string $methodCode = null,
                             $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct(
            $scopeConfig,
            $methodCode,
            $pathPattern
        );
        $this->scopeConfig = $scopeConfig;
    }

    public function getSellerId(): string
    {
        return (string)$this->scopeConfig->getValue(self::SELLER_ID, ScopeInterface::SCOPE_STORE);
    }

    public function getSecretKey(): string
    {
        return (string)$this->scopeConfig->getValue(self::SECRET_KEY, ScopeInterface::SCOPE_STORE);
    }

    public function getLocale($scopeCode)
    {
        $locale = $this->scopeConfig->getValue(self::LOCALE, ScopeInterface::SCOPE_STORE, $scopeCode);
        if (!$locale) {
            return null;
        }

        return substr($locale, 0, strpos($locale, "_"));
    }

    public function getCommunicationUrl(?string $service = null): string
    {
        return $this->getUrl($this->scopeConfig->getValue(self::COMMUNICATION_URL, ScopeInterface::SCOPE_STORE) ?? '', $service);
    }

    public function getCommunicationUrlCustom(?string $service = null): string
    {
        return $this->getUrl($this->scopeConfig->getValue(self::COMMUNICATION_URL_CUSTOM, ScopeInterface::SCOPE_STORE) ?? '', $service);
    }

    /**
     *  Gets the real communication url. If COMMUNICATION_URL_CUSTOM is set, gets that. Otherwise gets COMMUNICATION_URL. 
     */
    public function getCommunicationUrlReal(?string $service = null): string
    {
        $url = self::getCommunicationUrl($service);

        if (str_starts_with($url, CommunicationEndpoint::CUSTOM_ENVIRONMENT_URL)) {
            $url = self::getCommunicationUrlCustom($service);
        }

        return $url;
    }

    private function getUrl(string $baseUrl, ?string $service = null)
    {
        $baseUrl = rtrim($baseUrl, '/');

        return sprintf('%s/%s', $baseUrl, $service ?? '');
    }

    public function getKeyVersion(): string
    {
        return $this->scopeConfig->getValue(self::KEY_VERSION, ScopeInterface::SCOPE_STORE);
    }

    public function getNewOrderStatus(): ?string
    {
        return $this->scopeConfig->getValue(self::NEW_ORDER_STATUS, ScopeInterface::SCOPE_STORE);
    }

    public function getPaidOrderStatus(): ?string
    {
        return $this->scopeConfig->getValue(self::PAID_ORDER_STATUS, ScopeInterface::SCOPE_STORE);
    }

    public function cancelOrderOnFailure(): bool
    {
        return $this->scopeConfig->getValue(self::CANCEL_ORDER_ON_FAILURE, ScopeInterface::SCOPE_STORE);
    }

    public function canCancelSettled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CAN_CANCEL_SETTLED, ScopeInterface::SCOPE_STORE);
    }

    public function getDelayedCaptureMethods(): array
    {
        $delayedCaptures = $this->scopeConfig->getValue(self::DELAYED_CAPTURE_METHODS, ScopeInterface::SCOPE_STORE);
        if (empty($delayedCaptures)) {
            return [];
        }

        return explode(',', $delayedCaptures);
    }

    public function getDeliveryMode(): ?string
    {
        return $this->scopeConfig->getValue(self::DELIVERY_MODE);
    }

    public function getDeliveryPaymentMethods(): ?string
    {
        return $this->scopeConfig->getValue(self::DELIVERY_PAYMENT_METHODS);
    }

    public function getDeliveryPaymentMethodsSpecific(): array
    {
        $value = $this->scopeConfig->getValue(self::DELIVERY_PAYMENT_METHODS_SPECIFIC);

        return explode(',', $value);
    }

    public function getDeliveryCustomMethod(): ?string
    {
        return $this->scopeConfig->getValue(self::DELIVERY_CUSTOM_METHOD);
    }

    public function isCronActive(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CRON_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    public function getStatusQuerySchedule(): ?string
    {
        return $this->scopeConfig->getValue(self::STATUS_QUERY_SCHEDULE);
    }

    public function getManualQueryCheckShortValue(): ?int
    {
        return $this->scopeConfig->getValue(self::MANUAL_QUERY_CHECK_SHORT);
    }

    public function getManualQueryCheckLongValue(): ?int
    {
        return $this->scopeConfig->getValue(self::MANUAL_QUERY_CHECK_LONG);
    }

    public function getRestoreShoppingCart(): ?string
    {
        return $this->scopeConfig->getValue(self::RESTORE_SHOPPING_CART, ScopeInterface::SCOPE_STORE);
    }

    public function isCalculatorEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PART_PAYMENT_CALCULATOR_ENABLED, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getCalculatorPlacement(): string
    {
        return $this->scopeConfig->getValue(self::PART_PAYMENT_CALCULATOR_PLACEMENT, ScopeInterface::SCOPE_WEBSITE);
    }

    public function isCalculatorDynamicPriceChangesEnabled(): bool
    {
        // Always allow dynamic price changes.
        return true;
    }

    public function getCalculatorLayout(): string
    {
        return $this->scopeConfig->getValue(self::PART_PAYMENT_CALCULATOR_LAYOUT, ScopeInterface::SCOPE_WEBSITE);
    }

    public function isCalculatorPurchasePriceInfoVisible(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PART_PAYMENT_CALCULATOR_PURCHASE_PRICE_VISIBILITY, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getCalculatorMinimumThreshold(): ?int
    {
        return $this->scopeConfig->getValue(self::PART_PAYMENT_CALCULATOR_THRESHOLD_MINIMUM, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getCalculatorScript(): ?string
    {
        return $this->scopeConfig->getValue(self::PART_PAYMENT_CALCULATOR_SCRIPT, ScopeInterface::SCOPE_WEBSITE);
    }
}
