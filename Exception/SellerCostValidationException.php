<?php

namespace Svea\SveaPayment\Exception;

use Throwable;

class SellerCostValidationException extends \Exception
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var string
     */
    private $requestPmtSellercosts;

    /**
     * @var string
     */
    private $formPmtSellercosts;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $orderId
     * @param string|null $requestPmtSellercosts
     * @param string|null $formPmtSellercosts
     */
    public function __construct(
        $message = "",
        $code = 0,
        Throwable $previous = null,
        string $orderId = null,
        string $requestPmtSellercosts = null,
        string $formPmtSellercosts = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->orderId = $orderId;
        $this->requestPmtSellercosts = $requestPmtSellercosts;
        $this->formPmtSellercosts = $formPmtSellercosts;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @return string|null
     */
    public function getRequestPmtSellercosts(): ?string
    {
        return $this->requestPmtSellercosts;
    }

    /**
     * @return string|null
     */
    public function getFormPmtSellercosts(): ?string
    {
        return $this->formPmtSellercosts;
    }
}
