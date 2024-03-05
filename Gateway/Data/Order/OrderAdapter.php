<?php

namespace Svea\SveaPayment\Gateway\Data\Order;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\Order\AddressAdapterFactory;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderAdapter implements OrderAdapterInterface
{
    /**
     * @var OrderInterface
     */
    private OrderInterface $order;

    /**
     * @var AddressAdapterFactory
     */
    private AddressAdapterFactory $addressAdapterFactory;

    /**
     * @param OrderInterface $order
     * @param AddressAdapterFactory $addressAdapterFactory
     */
    public function __construct(
        OrderInterface        $order,
        AddressAdapterFactory $addressAdapterFactory
    ) {
        $this->order = $order;
        $this->addressAdapterFactory = $addressAdapterFactory;
    }

    /**
     * @return OrderInterface
     */
    public function getCurrentOrder(): OrderInterface
    {
        return $this->order;
    }

    public function getCustomerId(): ?int
    {
        return $this->order->getCustomerId();
    }

    public function getStoreId(): int
    {
        return $this->order->getStoreId();
    }

    public function getShippingAddress(): ?AddressAdapterInterface
    {
        if ($this->order->getShippingAddress()) {
            return $this->addressAdapterFactory->create(
                ['address' => $this->order->getShippingAddress()]
            );
        }

        return null;
    }

    public function getItems(): array
    {
        return $this->order->getItems();
    }

    public function getBillingAddress(): ?AddressAdapterInterface
    {
        if ($this->order->getBillingAddress()) {
            return $this->addressAdapterFactory->create(
                ['address' => $this->order->getBillingAddress()]
            );
        }

        return null;
    }

    public function getOrderIncrementId(): string
    {
        return $this->order->getIncrementId();
    }

    public function getId(): int
    {
        return $this->order->getEntityId();
    }

    public function getGrandTotalAmount(): ?float
    {
        return $this->order->getBaseGrandTotal();
    }

    public function getCurrencyCode(): string
    {
        return $this->order->getBaseCurrencyCode();
    }

    public function getRemoteIp(): ?string
    {
        return $this->order->getRemoteIp();
    }

    public function getBaseDiscountAmount(): ?float
    {
        return $this->order->getBaseDiscountAmount();
    }

    public function getBaseShippingAmount(): ?float
    {
        return $this->order->getBaseShippingAmount();
    }

    public function getDiscountDescription(): ?string
    {
        return $this->order->getDiscountDescription();
    }

    public function getShippingDescription(): ?string
    {
        return $this->order->getShippingDescription();
    }

    public function getBaseShippingTaxAmount()
    {
        return $this->order->getBaseShippingTaxAmount();
    }

    public function getBaseGiftCardAmount(): ?float
    {
        return $this->order->getData('base_gift_cards_amount');
    }
}
