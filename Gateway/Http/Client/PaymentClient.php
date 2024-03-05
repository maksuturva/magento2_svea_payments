<?php

namespace Svea\SveaPayment\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\App\State;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Response\XmlDataResolver;
use Svea\SveaPayment\Model\UserAgent;
use function __;

class PaymentClient extends SveaClient implements ClientInterface
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger          $logger,
        Config          $config,
        Curl            $curl,
        XmlDataResolver $xmlDataResolver,
        UserAgent       $userAgent,
        State           $state,
        string          $responseType = self::RESPONSE_TYPE_XML
    ) {
        parent::__construct($logger, $config, $curl, $xmlDataResolver, $userAgent, $state, $responseType);
        $this->logger = $logger;
    }

    /**
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $response = [];
        try {
            if (empty($transferObject->getBody())) {
                throw new LocalizedException(
                    __('Payment data is not defined')
                );
            }
            $response = parent::placeRequest($transferObject);
        } catch (\Exception $e) {
            $this->logger->log(
                'error',
                'An exception has occurred: ' . $e->getMessage()
            );
        }

        return $response;
    }
}
