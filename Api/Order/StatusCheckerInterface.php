<?php
namespace Svea\SveaPayment\Api\Order;

use Magento\Sales\Model\Order;

interface StatusCheckerInterface
{
    /**
     * @param Order $order
     * @return mixed
     */
    public function execute(Order $order);
}
