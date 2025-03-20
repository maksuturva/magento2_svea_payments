<?php
namespace Svea\SveaPayment\Logger\Handlers;

use Exception;
use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Class Generic
 */
class Generic extends Base
{
    public function __construct(
        DriverInterface $filesystem,
        $loggerType,
        ?string $filePath = null,
        ?string $fileName = null
    ) {
        $this->loggerType = $loggerType;
        parent::__construct($filesystem, $filePath, $fileName);
    }
}
