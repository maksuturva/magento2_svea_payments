<?php

namespace Svea\SveaPayment\Model\ResourceModel\Migrate;

use Exception;

interface MigrateSalesInterface
{
    /**
     * @param int|null $fromDate
     *
     * @return void
     * @throws Exception
     */
    public function execute(?int $fromDate = null): void;
}
