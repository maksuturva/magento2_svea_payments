<?php

namespace Svea\SveaPayment\Model\Request;

class DeliveryDataSpecification
{
    private $allowedDeliveryCodes = [
        'ITELL',
        'MATHU',
        'KAUKO',
        'TRANS',
        'KIITO',
        'MYPAC',
        'DBSCH',
        'FEDEX',
        'DHLDP',
        'TNTNV',
        'UPSAM',
        'UNREG',
        'PICKU',
        'ODLVR',
        'SERVI',
        'ELECT',
        'UNREF',
        'UNRDL',
    ];

    /**
     * @return string[]
     */
    public function getAllowedDeliveryCodes(): array
    {
        return $this->allowedDeliveryCodes;
    }

    /**
     * @return string|null
     */
    public function getFallbackDefaultCode(): ?string
    {
        return 'ODLVR';
    }
}
