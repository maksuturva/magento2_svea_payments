<?php

namespace Svea\SveaPayment\Model\ResourceModel\Migrate;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Output\OutputInterface;

interface MigrateConfigInterface
{
    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute(OutputInterface $output): void;
}
