<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Plugin\Framework\Logger;

use Magento\Framework\Logger\Monolog;
use Svea\SveaPayment\Logger\LogIdCreator;

class MonologPlugin
{
    /**
     * @var LogIdCreator
     */
    private $logIdCreator;

    public function __construct(
        LogIdCreator $logIdCreator
    ) {
        $this->logIdCreator = $logIdCreator;
    }

    /**
     * @param Monolog $subject
     * @param $level
     * @param $message
     * @param array $context
     *
     * @return array
     */
    public function beforeAddRecord(Monolog $subject, $level, $message, array $context = []): array
    {
        $uniqueId = $this->logIdCreator->getUniqueId();
        $context['unique_id'] = $uniqueId;

        return [
            $level,
            $message,
            $context
        ];
    }
}
