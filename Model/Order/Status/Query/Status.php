<?php
namespace Svea\SveaPayment\Model\Order\Status\Query;

use Magento\Framework\Phrase;

class Status
{
    const CODE_SUCCESS = 'success';
    const CODE_NOTICE = 'notice';
    const CODE_ERROR = 'error';

    /**
     * @var string
     */
    private $code;

    /**
     * @var string|Phrase
     */
    private $message;

    /**
     * @var ?string
     */
    private $exception;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return Status
     */
    public function setCode(string $code): Status
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string|Phrase
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string|Phrase $message
     *
     * @return Status
     */
    public function setMessage($message): Status
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getException(): ?string
    {
        return $this->exception;
    }

    /**
     * @param ?string $exception
     *
     * @return Status
     */
    public function setException(?string $exception): Status
    {
        $this->exception = $exception;

        return $this;
    }
}
