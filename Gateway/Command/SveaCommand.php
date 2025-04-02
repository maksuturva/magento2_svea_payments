<?php

namespace Svea\SveaPayment\Gateway\Command;

use Magento\Framework\Logger\Monolog as Logger;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;

class SveaCommand implements CommandInterface
{
    /**
     * @var BuilderInterface
     */
    private $requestBuilder;

    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ArrayResultFactory
     */
    private $resultFactory;

    public function __construct(
        BuilderInterface         $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface          $client,
        ValidatorInterface       $validator,
        Logger                   $logger,
        ArrayResultFactory       $resultFactory,
        ?HandlerInterface        $handler = null
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->resultFactory = $resultFactory;
        $this->handler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transferO);
        if ($this->validator !== null) {
            $result = $this->validator->validate(
                \array_merge($commandSubject, ['response' => $response])
            );
            if (!$result->isValid()) {
                $this->processErrors($result);
            }
        }

        if ($this->handler) {
            $this->handler->handle($commandSubject, $response);
        }

        return $this->resultFactory->create(['array' => $response]);
    }

    /**
     * @param ResultInterface $result
     *
     * @throws CommandException
     */
    private function processErrors(ResultInterface $result)
    {
        $errors = \implode(', ', $result->getFailsDescription());
        $code = !empty($result->getErrorCodes()) ? $result->getErrorCodes()[0] : 0;
        $this->logger->error(\sprintf('Gateway command error: %s', $errors));

        throw new CommandException(\__('Error on Svea Payments transaction: %1 (%2)', $errors), null, $code);
    }
}
