<?php
namespace Svea\SveaPayment\Model\Payment;

use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class AdditionalData
{
    const SVEA_TRANSACTION_ID = 'svea_transaction_id';
    const MAKSUTURVA_TRANSACTION_ID = 'maksuturva_transaction_id';

    /**
     * @var Serializer
     */
    private Serializer $serializer;

    /**
     * @var Method
     */
    private Method $method;

    public function __construct(
        Serializer $serializer,
        Method $method
    ) {
        $this->serializer = $serializer;
        $this->method = $method;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return array|mixed
     */
    public function get(OrderPaymentInterface $payment, ?string $key = null)
    {
        $additionalData = $payment->getAdditionalData();
        if ($additionalData && !\is_array($additionalData)) {
            $additionalData = $this->serializer->unserialize($additionalData);
        }

        if ($additionalData && $key) {
            return $additionalData[$key] ?? null;
        }

        return $additionalData;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param string $key
     * @param mixed $value
     */
    public function set(OrderPaymentInterface $payment, string $key, $value): void
    {
        $data = $this->get($payment);
        $data[$key] = $value;
        $this->setData($payment, $data);
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param array $data
     */
    public function setData(OrderPaymentInterface $payment, array $data): void
    {
        $payment->setAdditionalData($this->serializer->serialize($data));
    }

    /**
     * @param OrderPaymentInterface $payment
     *
     * @return array|mixed|null
     */
    public function getSveaTransactionId(OrderPaymentInterface $payment)
    {
        if ($this->method->isMaksuturvaCode($payment->getMethod())) {
            return $this->get($payment, self::MAKSUTURVA_TRANSACTION_ID);
        }
        return $this->get($payment, self::SVEA_TRANSACTION_ID);
    }
}
