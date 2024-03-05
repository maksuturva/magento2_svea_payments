<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;

use function preg_replace;

class CustomerDataBuilder implements BuilderInterface
{
    /**
     * Name of buyer (billing). 40 character maximum
     */
    const BUYER_NAME = 'pmt_buyername';

    /**
     * Address of buyer (billing). Street address or p.o. box. 40 characters maximum
     */
    const BUYER_ADDRESS = 'pmt_buyeraddress';

    /**
     * Postal code of buyer (billing). 5 characters maximum
     */
    const BUYER_POSTALCODE = 'pmt_buyerpostalcode';

    /**
     * City of buyer (billing). 40 characters maximum
     */
    const BUYER_CITY = 'pmt_buyercity';

    /**
     * Country code of buyer (billing)
     * (ISO 3166-1 alpha-2 standard based 2-character country code)
     * 2 characters maximum
     */
    const BUYER_COUNTRY = 'pmt_buyercountry';

    /**
     * Phone number of buyer (billing)
     */
    const BUYER_PHONE = 'pmt_buyerphone';

    /**
     * Email address of buyer
     */
    const BUYER_EMAIL = 'pmt_buyeremail';

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
    public function build(array $buildSubject) : array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $result = [];

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $result = [
                self::BUYER_NAME => $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
                self::BUYER_ADDRESS => $billingAddress->getStreetLine1(),
                self::BUYER_POSTALCODE => $billingAddress->getPostcode(),
                self::BUYER_CITY => $billingAddress->getCity(),
                self::BUYER_COUNTRY => $billingAddress->getCountryId(),
                self::BUYER_PHONE => preg_replace(
                    '/[^\+\d\s\-\(\)]/',
                    '',
                    $billingAddress->getTelephone()
                ),
                self::BUYER_EMAIL => $billingAddress->getEmail(),
            ];
        }

        return $result;
    }
}
