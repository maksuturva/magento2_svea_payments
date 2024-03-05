<?php
namespace Svea\SveaPayment\Model\Order\Status\Query;

use Magento\Sales\Model\Order;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Model\Payment\AdditionalData;

class ResponseValidator
{
    /**
     * @var AmountHandler $amountHandler
     */
    private $amountHandler;

    /**
     * @var AdditionalData $additionalData
     */
    private $additionalData;

    /**
     * @param AmountHandler $amountHandler
     * @param AdditionalData $additionalData
     */
    public function __construct(
        AmountHandler $amountHandler,
        AdditionalData $additionalData
    ) {
        $this->amountHandler = $amountHandler;
        $this->additionalData = $additionalData;
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    public function validateFields(array $response): bool
    {
        return empty($this->collectMissingRequiredFields($response));
    }

    /**
     * @param Order $order
     * @param array $response
     *
     * @return bool|\Magento\Framework\Phrase
     */
    public function validateOrderId(Order $order, array $response)
    {
        if (empty($response['pmtq_orderid'])) {
            return \__('Mandatory response field pmtq_orderid is missing.');
        }

        if ($response['pmtq_orderid'] != $order->getIncrementId()) {
            return \__('Local Order Id and Response Order Id do not match!');
        }

        return true;
    }

    public function validatePaymentId(Order $order, array $response)
    {
        if (empty($response['pmtq_id'])) {
            return \__('Mandatory response field pmtq_id is missing.');
        }

        if ($response['pmtq_id'] != $this->additionalData->getSveaTransactionId($order->getPayment())) {
            return \__('Local pmtq_id (transactionid) and Response pmtq_id do not match!');
        }

        return true;

    }

    /**
     * @param Order $order
     * @param array $response
     *
     * @return bool|\Magento\Framework\Phrase
     */
    public function validateAmounts(Order $order, array $response)
    {
        $total = $order->getGrandTotal();
        $pmtqAmount = $this->amountHandler->parseFloat($response["pmtq_amount"]);
        if (empty($response["pmtq_sellercosts"])) {
            $pmtqSellerCosts = 0.00;
        } else {
            $pmtqSellerCosts = $this->amountHandler->parseFloat($response["pmtq_sellercosts"]);
        }
        if (empty($response["pmtq_invoicingfee"])) {
            $pmtqInvoicingFee = 0.00;
        } else {
            $pmtqInvoicingFee = $this->amountHandler->parseFloat($response["pmtq_invoicingfee"]);
        }

        // 5.00 is maximum accepted sum difference
        if (\abs($total - ($pmtqAmount + $pmtqSellerCosts - $pmtqInvoicingFee)) > 5.00) {
            return \__('Order and status query response sum mismatch!');
        }

        return true;
    }

    /**
     * @param array $response
     *
     * @return array
     */
    public function collectMissingRequiredFields(array $response): array
    {
        $missingFields = [];
        $requiredFields = [
            'pmtq_action',
            'pmtq_version',
            'pmtq_sellerid',
            'pmtq_id',
            'pmtq_amount',
            'pmtq_returncode',
            'pmtq_returntext',
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($response[$requiredField])) {
                $missingFields[] = $requiredField;
            }
        }

        return $missingFields;
    }
}
