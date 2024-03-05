<?php
namespace Svea\SveaPayment\Gateway\Http\Client;

use Magento\Framework\HTTP\Client\CurlFactory as CurlClientFactory;

class Curl
{
    /**
     * @var CurlClientFactory
     */
    private $clientFactory;

    /**
     * @param CurlClientFactory $clientFactory
     */
    public function __construct(CurlClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function create(string $username, string $password)
    {
        $curl = $this->clientFactory->create();
        $curl->setCredentials($username, $password);
        $curl->setOptions([
            \CURLOPT_HEADER => 0,
            \CURLOPT_FRESH_CONNECT => 1,
            \CURLOPT_FORBID_REUSE => 1,
            \CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        return $curl;
    }
}
