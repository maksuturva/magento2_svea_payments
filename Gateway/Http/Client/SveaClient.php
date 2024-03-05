<?php
namespace Svea\SveaPayment\Gateway\Http\Client;

use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl as CurlClient;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Response\XmlDataResolver;
use Svea\SveaPayment\Model\UserAgent;

class SveaClient implements ClientInterface
{
    const RESPONSE_TYPE_XML = 'XML';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var XmlDataResolver
     */
    private $xmlResolver;

    /**
     * @var UserAgent
     */
    private $userAgent;

    /**
     * @var State
     */
    private  $state;

    /**
     * @var string
     */
    private $responseType;

    public function __construct(
        Logger          $logger,
        Config          $config,
        Curl            $curl,
        XmlDataResolver $xmlDataResolver,
        UserAgent       $userAgent,
        State           $state,
        string          $responseType = self::RESPONSE_TYPE_XML
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->curl = $curl;
        $this->xmlResolver = $xmlDataResolver;
        $this->responseType = $responseType;
        $this->userAgent = $userAgent;
        $this->state = $state;
    }

    /**
     * @param string|null $username
     * @param string|null $password
     *
     * @return CurlClient
     */
    public function createClient(?string $username = null, ?string $password = null)
    {
        if (empty($username)) {
            $username = $this->config->getSellerId();
        }
        if (empty($password)) {
            $password = $this->config->getSecretKey();
        }

        return $this->curl->create($username, $password);
    }

    /**
     * @param string $method
     * @param string $service
     * @param array $data
     *
     * @return array
     */
    public function serviceRequest(string $method, string $service, array $data): array
    {
        $client = $this->createClient();
        $url = $this->config->getCommunicationUrl($service);

        return $this->doRequest($client, $url, $method, $data);
    }

    /**
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $client = $this->createClient($transferObject->getAuthUsername(), $transferObject->getAuthPassword());

        return $this->doRequest(
            $client,
            $transferObject->getUri(),
            $transferObject->getMethod(),
            $transferObject->getBody()
        );
    }

    /**
     * @param CurlClient $client
     * @param string $url
     * @param string $method
     * @param array $data
     *
     * @return array
     */
    private function doRequest(CurlClient $client, string $url, string $method, array $data): array
    {
        if ($this->getApplicationMode() !== State::MODE_PRODUCTION) {
            $this->logger->debug(\sprintf('API Request (%s %s): %s', $method, $url, \json_encode($data)));
        } else if ($this->getApplicationMode() === State::MODE_PRODUCTION) {
            $this->logger->info(\sprintf('API Request (%s %s)', $method, $url));
        }
        $this->addUserAgentToRequest($client);

        try {
            switch (\strtoupper($method)) {
                case 'POST':
                    $client->post($url, $data);
                    break;
                case 'GET':
                    $client->get($url);
                    break;
                default:
                    throw new \Exception(\sprintf('Client does not support "%s" method', $method));
            }

            $response = $client->getBody();
            if ($this->getApplicationMode() !== State::MODE_PRODUCTION) {
                $this->logger->debug(\sprintf('API Response (%s %s): %s', $method, $url, $response));
            } else if ($this->getApplicationMode() === State::MODE_PRODUCTION) {
                $this->logger->info(\sprintf('API Response (%s %s)', $method, $url));
            }

            return $this->resolveResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('An exception has occurred: ' . $e->getMessage());
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * @param string $response
     *
     * @return array
     * @throws LocalizedException
     */
    private function resolveResponse(string $response)
    {
        switch ($this->responseType) {
            case self::RESPONSE_TYPE_XML:
                return $this->xmlResolver->resolveXmlData($response);
            default:
                throw new LocalizedException(\__(
                    'Svea client does not support response type "%1"',
                    $this->responseType
                ));
        }
    }

    /**
     * @param CurlClient $client
     *
     * @return void
     */
    private function addUserAgentToRequest(CurlClient $client)
    {
        $client->setOption(CURLOPT_USERAGENT, $this->userAgent->getUserAgent());
    }

    /**
     * @return string
     */
    public function getApplicationMode(): string
    {
        return $this->state->getMode();
    }
}
