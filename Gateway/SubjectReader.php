<?php

namespace Svea\SveaPayment\Gateway;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class SubjectReader implements SubjectReaderInterface
{
    /**
     * @param array $subject
     * @param string $key
     *
     * @return mixed|void
     */
    public function read(array $subject, string $key)
    {
        if (!isset($subject[$key])) {
            throw new \InvalidArgumentException(\sprintf('Key "%s" does not exist', $key));
        }

        return $subject[$key];
    }

    /**
     * @param array $subject
     * @return array
     */
    public function readResponseObject(array $subject): array
    {
        if (!isset($subject['response']) || !\is_array($subject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        return $subject['response'];
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject): PaymentDataObjectInterface
    {
        if (!isset($subject['payment']) || !$subject['payment'] instanceof PaymentDataObjectInterface) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        return $subject['payment'];
    }

    /**
     * Read state object from subject
     *
     * @param array $subject
     * @return DataObject
     */
    public function readStateObject(array $subject): DataObject
    {
        if (!isset($subject['stateObject']) || !$subject['stateObject'] instanceof DataObject) {
            throw new \InvalidArgumentException('State object does not exist');
        }

        return $subject['stateObject'];
    }
}
