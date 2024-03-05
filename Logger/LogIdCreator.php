<?php
namespace Svea\SveaPayment\Logger;

/**
 * Class LogIdCreator
 */
class LogIdCreator
{
    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return hash("crc32b", microtime(), false);
    }
}
