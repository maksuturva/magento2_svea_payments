<?php

namespace Svea\SveaPayment\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Shipment;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Request\DeliveryInfo\DataBuilder;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Gateway\Validator\OrderValidatorInterface;
use Svea\SveaPayment\Model\Order\Status\Query;
use Svea\SveaPayment\Model\System\Config\Source\DeliveryMode;
use function array_merge;
use function count;
use function in_array;

class DeliveryCommand implements CommandInterface
{
    const COMMAND_CODE = 'svea_delivery';
    const COMMAND_CODE_ADD = 'svea_delivery_add';
    const COMMAND_CODE_UPDATE = 'svea_delivery_update';
    const COMMAND_CODE_DELETE = 'svea_delivery_delete';
    const TARGET_ADDITIONS = 'add';
    const TARGET_REMOVALS = 'remove';
    const TARGET_UPDATES = 'update';
    private const FLAG_INFO_SENT = 'svea_info_sent';

    /**
     * @var CommandPoolInterface
     */
    private CommandPoolInterface $commandPool;

    /**
     * @var SubjectReaderInterface
     */
    private SubjectReaderInterface $subjectReader;

    /**
     * @var ArrayResultFactory
     */
    private ArrayResultFactory $resultFactory;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var OrderValidatorInterface
     */
    private OrderValidatorInterface $orderValidator;

    public function __construct(
        CommandPoolInterface    $commandPool,
        SubjectReaderInterface  $subjectReader,
        ArrayResultFactory      $resultFactory,
        Config                  $config,
        OrderValidatorInterface $orderValidator,
    ) {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
        $this->resultFactory = $resultFactory;
        $this->config = $config;
        $this->orderValidator = $orderValidator;
    }

    /**
     * @param array $commandSubject
     *
     * @return Command\ResultInterface|null
     * @throws CommandException
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var Shipment $shipment */
        $shipment = $this->subjectReader->read($commandSubject, 'shipment');
        if (!$this->validate($shipment)) {
            return null;
        }
        $commandSubject[DataBuilder::METHOD_CODE] = $shipment->getOrder()->getShippingMethod();
        if (!$shipment->isObjectNew()) {
            $commandSubject[DataBuilder::FORCE_UPDATE] = true;
        }
        $results = $this->sendInfo($commandSubject);
        $shipment->setData(self::FLAG_INFO_SENT, true);

        return $this->resultFactory->create(['array' => $results]);
    }

    /**
     * @param Shipment $shipment
     *
     * @return bool
     * @throws LocalizedException
     */
    private function validate(Shipment $shipment): bool
    {
        $this->orderValidator->validate($shipment->getOrder());
        if ($this->config->getDeliveryMode() == DeliveryMode::MODE_DISABLED) {
            return false;
        }
        if ($shipment->hasData(self::FLAG_INFO_SENT)) {
            return false;
        }
        if ($this->config->getDeliveryPaymentMethods() == 1) {
            $methods = $this->config->getDeliveryPaymentMethodsSpecific();
            $payment = $shipment->getOrder()->getPayment();
            if (!in_array($payment->getAdditionalInformation('svea_method_code'), $methods)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $commandSubject
     *
     * @return array
     * @throws CommandException
     * @throws NotFoundException
     */
    private function sendInfo(array $commandSubject): array
    {
        $results = [];
        $additions = $commandSubject[self::TARGET_ADDITIONS] ?? false;
        $removals = $commandSubject[self::TARGET_REMOVALS] ?? false;
        $updates = $commandSubject[self::TARGET_UPDATES] ?? false;
        $order = $commandSubject['shipment']->getOrder();

        if ($this->config->getDeliveryMode() == DeliveryMode::MODE_CUSTOM) {
            $commandSubject[DataBuilder::METHOD_CODE] = $this->config->getDeliveryCustomMethod();
            $commandSubject[DataBuilder::INFO_ADD] = DataBuilder::INFO_VALUE_BY_METHOD;

            // Check if we are sending all the products in the order
            $allProducts = true;
            foreach ($order->getAllItems() as $item) {
                if ($item->getQtyShipped() != $item->getQtyOrdered()) {
                    $allProducts = false;
                    break;
                }
            }

            if ($allProducts) {
                $commandSubject[DataBuilder::ALL_SENT_FLAG] = true;

                $results = [$this->executeCommand(self::COMMAND_CODE_ADD, $commandSubject)->get()];
            }

        } elseif (!$additions && !$updates && !$removals) {
            $commandSubject[DataBuilder::INFO_ADD] = DataBuilder::INFO_VALUE_BY_METHOD;
            $results = [$this->executeCommand(self::COMMAND_CODE_ADD, $commandSubject)->get()];
        } else {
            if ($additions) {
                $results = $this->sendTracks(
                    self::COMMAND_CODE_ADD,
                    $commandSubject,
                    $additions,
                    [DataBuilder::INFO_ADD]
                );
            }
            if ($updates) {
                $updated = $this->sendTracks(
                    self::COMMAND_CODE_UPDATE,
                    $commandSubject,
                    $updates,
                    [
                        DataBuilder::INFO_ADD,
                        DataBuilder::INFO_REMOVE
                    ]
                );
                $results = array_merge($results, $updated);
            }
            if ($removals) {
                $removed = $this->sendTracks(
                    self::COMMAND_CODE_DELETE,
                    $commandSubject,
                    $removals,
                    [DataBuilder::INFO_REMOVE]
                );
                $results = array_merge($results, $removed);
            }
        }

        return $results;
    }

    /**
     * @param string $commandName
     * @param array $commandSubject
     *
     * @return Command\ResultInterface|null
     * @throws CommandException
     * @throws NotFoundException
     */
    private function executeCommand(string $commandName, array $commandSubject)
    {
        return $this->commandPool->get($commandName)->execute($commandSubject);
    }

    /**
     * @param string $command
     * @param array $commandSubject
     * @param Shipment\Track[] $tracks
     * @param string[] $types
     *
     * @return array
     * @throws CommandException
     * @throws NotFoundException
     */
    private function sendTracks(string $command, array $commandSubject, array $tracks, array $types)
    {
        $results = [];
        $tracks = $this->collectTracks($tracks);
        $count = count($tracks);
        foreach ($tracks as $i => $track) {
            $commandSubject[DataBuilder::METHOD_CODE] = $track->getCarrierCode();
            $hasRemoval = false;
            foreach ($types as $type) {
                $commandSubject[$type] = $track;
                if ($type == DataBuilder::INFO_REMOVE) {
                    $hasRemoval = true;
                }
            }
            if (!$hasRemoval && $i === $count - 1) {
                $commandSubject[DataBuilder::ALL_SENT_FLAG] = true;
            }
            $results[] = $this->executeCommand($command, $commandSubject)->get();
            $track->setData(self::FLAG_INFO_SENT, true);
        }

        return $results;
    }

    /**
     * @param Shipment\Track[] $tracks
     */
    private function collectTracks(array $tracks)
    {
        $collected = [];
        foreach ($tracks as $track) {
            if (!$track->hasData(self::FLAG_INFO_SENT)) {
                $collected[] = $track;
            }
        }

        return $collected;
    }
}
