<?php
namespace Svea\SveaPayment\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;

class PaymentHandlingException extends LocalizedException
{
    const ERROR_TYPE_VALUES_MISMATCH = 'values_mismatch_error';
    const ERROR_TYPE_EMPTY_FIELD = 'empty_field_error';
    const ERROR_TYPE_SELLERCOSTS_VALUES_MISMATCH = 'sellercosts_values_mismatch_error';

    /**
     * @var Order
     */
    private $order;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var array|null
     */
    private $errorParams;

    public function __construct(
        Phrase $phrase,
        ?Order $order = null,
        ?string $type = null,
        $code = 0,
        ?array $errorParams = null,
        ?\Exception $cause = null
    ) {
        parent::__construct($phrase, $cause, $code);
        $this->order = $order;
        $this->type = $type;
        $this->errorParams = $errorParams;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string[]
     */
    public function getErrorParameters(): array
    {
        $parameters = [
            'type' => $this->getType(),
        ];
        if ($this->order) {
            $parameters['pmtId'] = $this->order->getIncrementId();
        }
        if (!empty($this->errorParams)) {
            $parameters = \array_merge($parameters, $this->errorParams);
        }

        return $parameters;
    }
}
