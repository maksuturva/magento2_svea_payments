<?php

namespace Svea\SveaPayment\Api\Query;

use DateTimeInterface;

interface DateValidatorInterface
{
    /**
     * @param DateTimeInterface $createdAt
     * @param DateTimeInterface $lastStatusCheck
     * @param DateTimeInterface $timeNow
     *
     * @return bool
     */
    public function isValid(
        DateTimeInterface $createdAt,
        DateTimeInterface $lastStatusCheck,
        DateTimeInterface $timeNow
    ): bool;
}
