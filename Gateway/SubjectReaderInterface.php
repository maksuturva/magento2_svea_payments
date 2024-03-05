<?php

namespace Svea\SveaPayment\Gateway;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

interface SubjectReaderInterface
{
    /**
     * @param array $subject
     * @param string $key
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function read(array $subject, string $key);

    /**
     * @param array $subject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function readResponseObject(array $subject) : array;

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     * @throws \InvalidArgumentException
     */
    public function readPayment(array $subject): PaymentDataObjectInterface;

    /**
     * @param array $subject
     * @return DataObject
     * @throws \InvalidArgumentException
     */
    public function readStateObject(array $subject) : DataObject;
}
