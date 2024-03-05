<?php

namespace Svea\SveaPayment\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Http\Client\SveaClient;
use Svea\SveaPayment\Gateway\Validator\ResponseValidator;

class ApiClient
{
    /**
     * @var SveaClient
     */
    private $client;

    /**
     * @var ResponseValidator
     */
    private $validator;

    public function __construct(SveaClient $client, ResponseValidator $validator)
    {
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @param string $method
     * @param string $service
     * @param array|mixed $data
     *
     * @return array
     */
    private function doRequest(string $method, string $service, $data): array
    {
        $response = $this->client->serviceRequest($method, $service, $data);
        $result = $this->validator->validate(['response' => $response]);

        if (!$result->isValid()) {
            throw new LocalizedException(\__('Svea payment error: %1', \implode(', ', $result->getFailsDescription())));
        }

        return $response;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function paymentStatusQuery(array $data): array
    {
        return $this->doRequest('POST', Config::PAYMENT_STATUS_SERVICE_URN, $data);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getPaymentMethods(array $data): array
    {
        return $this->doRequest('POST', Config::PAYMENT_METHOD_URN, $data);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function paymentCancel(array $data): array
    {
        return $this->doRequest('POST', Config::PAYMENT_CANCEL_URN, $data);
    }
}
