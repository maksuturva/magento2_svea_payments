<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Model\Order\Status\Query;

use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Sales\Api\Data\OrderInterface;
use Svea\SveaPayment\Api\Order\OrderValidatorInterface;
use Svea\SveaPayment\Api\Query\DateValidatorInterface;
use function date_create;

class QueryValidator
{
    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var Date
     */
    private Date $date;

    /**
     * @var array
     */
    private array $validators;

    /**
     * @param DateTime $dateTime
     * @param Date $date
     * @param array $validators
     */
    public function __construct(
        DateTime $dateTime,
        Date     $date,
        array    $validators = []
    ) {
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->validators = $validators;
    }

    /**
     * Is order valid for query status check
     *
     * @param OrderInterface $order
     * @param bool $isManualQuery
     *
     * @return bool
     */
    public function isValidForStatusCheck(OrderInterface $order, bool $isManualQuery = false): bool
    {
        if (!$this->validateOrder($order)) {
            return false;
        }

        return $isManualQuery ?: $this->validateDate($order);
    }

    /**
     * @param OrderInterface $order
     *
     * @return bool
     */
    private function validateOrder(OrderInterface $order): bool
    {
        foreach ($this->validators as $validator) {
            if (!($validator instanceof OrderValidatorInterface)) {
                continue;
            }
            if ($validator->isValid($order)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Date validating. Date validators uses DateValidatorInterface.
     *
     * @param OrderInterface $order
     *
     * @return bool
     */
    private function validateDate(OrderInterface $order): bool
    {
        $createdAt = date_create($order->getCreatedAt());
        $lastStatusCheck = date_create($order->getData('svea_last_status_query'));
        $timeNow = date_create($this->dateTime->formatDate($this->date->gmtTimestamp()));
        foreach ($this->validators as $validator) {
            if (!($validator instanceof DateValidatorInterface)) {
                continue;
            }
            if ($validator->isValid($createdAt, $lastStatusCheck, $timeNow)) {
                return true;
            }
        }

        return false;
    }
}
