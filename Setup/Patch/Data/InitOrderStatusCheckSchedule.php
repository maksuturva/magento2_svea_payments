<?php

namespace Svea\SveaPayment\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use function rand;
use function sprintf;

class InitOrderStatusCheckSchedule implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * @param WriterInterface $writer
     */
    public function __construct(
        WriterInterface $writer
    ) {
        $this->writer = $writer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Initialize order status check cron job schedule
     *
     * @return void
     */
    public function apply()
    {
        $cronExprString = $this->createCronSchedule();
        $this->writer->save(Config::STATUS_QUERY_SCHEDULE, $cronExprString);
    }

    /**
     * @return string
     */
    private function createCronSchedule(): string
    {
        return sprintf('%s/30 * * * *', rand(0, 29));
    }
}
