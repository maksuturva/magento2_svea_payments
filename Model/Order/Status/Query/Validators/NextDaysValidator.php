<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Model\Order\Status\Query\Validators;

use DateTimeInterface;
use Svea\SveaPayment\Api\Query\DateValidatorInterface;

/**
 * Do status query for the order once in a day when the order is older than a day
 */
class NextDaysValidator implements DateValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function isValid(
        DateTimeInterface $createdAt,
        DateTimeInterface $lastStatusCheck,
        DateTimeInterface $timeNow
    ): bool {
        if ($this->isOlderThanDay($createdAt, $timeNow)) {
            return $this->onceADay($lastStatusCheck, $timeNow);
        }

        return false;
    }

    /**
     * @param DateTimeInterface $createdAt
     * @param DateTimeInterface $timeNow
     *
     * @return bool
     */
    private function isOlderThanDay(DateTimeInterface $createdAt, DateTimeInterface $timeNow): bool
    {
        return $createdAt->diff($timeNow)->d >= 1;
    }

    /**
     * @param DateTimeInterface $lastStatusCheck
     * @param DateTimeInterface $timeNow
     *
     * @return bool
     */
    private function onceADay(DateTimeInterface $lastStatusCheck, DateTimeInterface $timeNow): bool
    {
        return $lastStatusCheck->diff($timeNow)->d >= 1;
    }
}
