<?php

namespace Svea\SveaPayment\Gateway\Request\DeliveryInfo;

use Magento\Framework\Logger\Monolog as Logger;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Svea\SveaPayment\Api\Delivery\MethodResolverInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Model\Payment\AdditionalData;
use function __;
use function sprintf;

class DataBuilder implements BuilderInterface
{
    const ALL_SENT_FLAG = 'all_sent';
    /** Value to set to pkg_deliverymethodid  */
    const METHOD_CODE = 'method_code';
    const FORCE_UPDATE = 'force_update';
    /** Value to set to pkg_adddeliveryinfo */
    const INFO_ADD = 'info_add';
    /** Value to set to pkg_remdeliveryinfo */
    const INFO_REMOVE = 'info_remove';
    /** Add and Remove targets with this value are updated to a new value based on pkg_deliverymethodid */
    const INFO_VALUE_BY_METHOD = 'by_method';

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var SubjectReaderInterface
     */
    private SubjectReaderInterface $subjectReader;

    /**
     * @var AdditionalData
     */
    private AdditionalData $paymentData;

    /**
     * @var MethodResolverInterface
     */
    private MethodResolverInterface $methodResolver;

    public function __construct(
        Logger                  $logger,
        Config                  $config,
        SubjectReaderInterface  $subjectReader,
        AdditionalData          $paymentData,
        MethodResolverInterface $methodResolver
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->paymentData = $paymentData;
        $this->methodResolver = $methodResolver;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();
        $transactionId = $this->paymentData->getSveaTransactionId($payment);
        $deliveryMethod = $this->methodResolver->resolve($buildSubject);
        $this->logger->info(sprintf(
            'Updating "%s" delivery info for package id %s',
            $deliveryMethod,
            $transactionId
        ));
        $data = [
            'pkg_version' => '0002',
            'pkg_sellerid' => $this->config->getSellerId(),
            'pkg_id' => $transactionId,
            'pkg_deliverymethodid' => $deliveryMethod,
            'pkg_allsent' => isset($buildSubject[self::ALL_SENT_FLAG]) ? 'Y' : 'N',
            'pkg_resptype' => 'XML',
            'pkg_keygeneration' => $this->config->getKeyVersion(),
        ];
        if (isset($buildSubject[self::FORCE_UPDATE])) {
            $data['pkg_forceupdate'] = 'Y';
        }
        if (isset($buildSubject[self::INFO_ADD])) {
            $data['pkg_adddeliveryinfo'] = $this->getInfoValue($buildSubject, self::INFO_ADD, $deliveryMethod);
        }
        if (isset($buildSubject[self::INFO_REMOVE])) {
            $data['pkg_remdeliveryinfo'] = $this->getInfoValue($buildSubject, self::INFO_REMOVE, $deliveryMethod);
        }

        return $data;
    }

    /**
     * @param array $subject
     * @param string $type
     * @param string $deliveryMethod
     *
     * @return string
     */
    private function getInfoValue(array $subject, string $type, string $deliveryMethod): string
    {
        $value = $subject[$type];
        if ($value == self::INFO_VALUE_BY_METHOD) {
            $value = __('Delivery notification');
        }
        if ($value instanceof Track) {
            $value = $value->getTrackNumber();
        }

        return (string)$value;
    }
}
