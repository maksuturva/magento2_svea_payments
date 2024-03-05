<?php

namespace Svea\SveaPayment\Model\ResourceModel\Migrate;

use Magento\Framework\Exception\LocalizedException;

interface MigrateConfigInterface
{
    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute(): void;
}
