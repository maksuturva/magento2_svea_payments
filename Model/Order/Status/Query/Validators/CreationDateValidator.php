<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Model\Order\Status\Query\Validators;

use DateTimeInterface;
use Svea\SveaPayment\Api\Query\DateValidatorInterface;

/**
 * Do status query check for the order once an hour during the five hours after the order is created
 */
class CreationDateValidator implements DateValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function isValid(
        DateTimeInterface $createdAt,
        DateTimeInterface $lastStatusCheck,
        DateTimeInterface $timeNow
    ): bool {
        if ($this->hasSameDate($createdAt, $timeNow) && $this->isLessThanFiveHours($createdAt, $timeNow)) {
            return $this->hourlyCheck($lastStatusCheck, $timeNow);
        }

        return false;
    }

    /**
     * @param DateTimeInterface $createdAt
     * @param DateTimeInterface $timeNow
     *
     * @return bool
     */
    private function hasSameDate(DateTimeInterface $createdAt, DateTimeInterface $timeNow): bool
    {
        return $createdAt->diff($timeNow)->d === 0;
    }

    /**
     * @param DateTimeInterface $createdAt
     * @param DateTimeInterface $timeNow
     *
     * @return bool
     */
    private function isLessThanFiveHours(DateTimeInterface $createdAt, DateTimeInterface $timeNow): bool
    {
        return $createdAt->diff($timeNow)->h < 5;
    }

    /**
     * Check once an hour
     *
     * @param DateTimeInterface $lastStatusCheck
     * @param DateTimeInterface $timeNow
     *
     * @return bool
     */
    private function hourlyCheck(DateTimeInterface $lastStatusCheck, DateTimeInterface $timeNow): bool
    {
        return $lastStatusCheck->diff($timeNow)->h >= 1;
    }
}
