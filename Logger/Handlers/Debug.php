<?php

namespace Svea\SveaPayment\Logger\Handlers;

use Magento\Developer\Model\Logger\Handler\Debug as DebugHandler;

class Debug extends DebugHandler
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/svea_payment_debug.log';
}
