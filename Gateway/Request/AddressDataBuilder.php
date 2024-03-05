<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;

class AddressDataBuilder implements BuilderInterface
{
    /**
     * Name of recipient (delivery). Street address or p.o. box. 40 characters maximum
     */
    const DELIVERY_NAME = 'pmt_deliveryname';

    /**
     * Address of recipient (delivery). 40 characters maximum
     */
    const DELIVERY_ADDRESS = 'pmt_deliveryaddress';

    /**
     * Postal code of recipient (delivery). 5 characters maximum
     */
    const DELIVERY_POSTAL_CODE = 'pmt_deliverypostalcode';

    /**
     * City of recipient (delivery). 40 characters maximum
     */
    const DELIVERY_CITY = 'pmt_deliverycity';

    /**
     * Country code of recipient (delivery)
     * (ISO 3166-1 alpha-2 standard based 2-character country code)
     */
    const DELIVERY_COUNTRY = 'pmt_deliverycountry';

    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    public function __construct(
        SubjectReaderInterface $subjectReader
    ) {
        $this->subjectReader  = $subjectReader;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader ->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $result = [];

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result = [
                self::DELIVERY_NAME => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
                self::DELIVERY_ADDRESS => $shippingAddress->getStreetLine1(),
                self::DELIVERY_POSTAL_CODE => $shippingAddress->getPostcode(),
                self::DELIVERY_CITY => $shippingAddress->getCity(),
                self::DELIVERY_COUNTRY => $shippingAddress->getCountryId(),
            ];
        }

        return $result;
    }
}
