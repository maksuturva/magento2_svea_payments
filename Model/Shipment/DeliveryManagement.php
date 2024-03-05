<?php

namespace Svea\SveaPayment\Model\Shipment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Sales\Model\Order\Shipment;
use Svea\SveaPayment\Gateway\Command\DeliveryCommand;
use Svea\SveaPayment\Gateway\SubjectBuilder;

class DeliveryManagement
{
    private const SKIPPABLE_DELETE_CODES = [
        31, // Delivery information not found in Svea => allow deletion in M2
    ];

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var SubjectBuilder
     */
    private $subjectBuilder;

    public function __construct(
        ManagerInterface     $messageManager,
        CommandPoolInterface $commandPool,
        SubjectBuilder       $subjectBuilder
    ) {
        $this->messageManager = $messageManager;
        $this->commandPool = $commandPool;
        $this->subjectBuilder = $subjectBuilder;
    }

    /**
     * @param Shipment $shipment
     * @param array $tracks
     * @param array $data
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function add(Shipment $shipment, array $tracks, array $data = [])
    {
        $subject = $this->buildSubject($shipment, $data);
        $subject[DeliveryCommand::TARGET_ADDITIONS] = $tracks;

        return $this->callCommand($subject);
    }

    /**
     * @param Shipment $shipment
     * @param Shipment\Track $track
     * @param array $data
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function update(Shipment $shipment, Shipment\Track $track, array $data = [])
    {
        $subject = $this->buildSubject($shipment, $data);
        $subject[DeliveryCommand::TARGET_UPDATES] = [$track];

        return $this->callCommand($subject);
    }

    /**
     * @param Shipment $shipment
     * @param Shipment\Track $track
     * @param array $data
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function delete(Shipment $shipment, Shipment\Track $track, array $data = [])
    {
        $subject = $this->buildSubject($shipment, $data);
        $subject[DeliveryCommand::TARGET_REMOVALS] = [$track];
        $result = null;

        try {
            $result = $this->callCommand($subject);
        } catch (CommandException $e) {
            if (!\in_array($e->getCode(), self::SKIPPABLE_DELETE_CODES)) {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * @param Shipment $shipment
     * @param array $data
     *
     * @return array
     */
    private function buildSubject(Shipment $shipment, array $data): array
    {
        $subject = $this->subjectBuilder->build($shipment->getOrder()->getPayment());
        $subject['shipment'] = $shipment;

        return \array_merge($subject, $data);
    }

    /**
     * @param array $subject
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws LocalizedException
     * @throws NotFoundException
     */
    private function callCommand(array $subject): ?ResultInterface
    {
        try {
            $result = $this->commandPool->get(DeliveryCommand::COMMAND_CODE)->execute($subject);
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e);
            throw $e;
        }

        return $result;
    }
}
