<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Calculation;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Data\Order\OrderAdapterFactory;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use function __;

class ShippingRowBuilder implements RowBuilderInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * @var Calculation
     */
    private $calculationModel;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var GroupRepository
     */
    private $groupRepository;

    /**
     * @var OrderAdapterFactory
     */
    private $orderAdapterFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var AmountHandler
     */
    private $amountHandler;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        TaxHelper $taxHelper,
        Calculation $calculationModel,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        GroupRepository $groupRepository,
        OrderAdapterFactory $orderAdapterFactory,
        TimezoneInterface $timezone,
        AmountHandler $amountHandler
    ) {
        $this->subjectReader  = $subjectReader;
        $this->taxHelper = $taxHelper;
        $this->calculationModel = $calculationModel;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->groupRepository = $groupRepository;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->timezone = $timezone;
        $this->amountHandler = $amountHandler;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts) : array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();

        $shippingDescription = $orderAdapter->getShippingDescription() ?: 'Free Shipping';
        $shippingCost = $orderAdapter->getBaseShippingAmount() ?? 0.0;
        $taxId = $this->taxHelper->getShippingTaxClass($orderAdapter->getStoreId());
        $request = $this->calculationModel->getRateRequest();

        $request->setCustomerClassId($this->getCustomerTaxClass())->setProductClassId($taxId);

        $shippingTax = $orderAdapter->getBaseShippingTaxAmount() ?? 0;
        $shippingTaxRate = $this->getShippingTaxRate($shippingTax, $shippingCost);

        $row = [
            self::NAME => __('Shipping'),
            self::DESC => $shippingDescription,
            self::QUANTITY => 1,
            self::DELIVERY_DATE => $this->timezone->date()->format('d.m.Y'),
            self::PRICE_NET => $this->amountHandler->formatFloat($shippingCost),
            self::VAT => $this->amountHandler->formatFloat($shippingTaxRate),
            self::DISCOUNT_PERCENTAGE => '0,00',
            self::TYPE => 2,
        ];

        $sellerCosts += $shippingCost + $shippingTax;

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $row,
        ];
    }

    private function getCustomerTaxClass(): int
    {
        $customerGroup = $this->checkoutSession->getQuote()->getCustomerGroupId();
        if (!$customerGroup) {
            $customerGroup = $this->scopeConfig->getValue('customer/create_account/default_group');
        }

        return $this->groupRepository->getById($customerGroup)->getTaxClassId();
    }

    /**
     * @param float $shippingTax
     * @param float $shippingCost
     * @return float|int
     */
    private function getShippingTaxRate(float $shippingTax, float $shippingCost)
    {
        if ($shippingCost === 0.0) {
            return 0;
        }

        return (float)(\round(($shippingTax / $shippingCost) * 100 * 2) / 2);
    }
}
