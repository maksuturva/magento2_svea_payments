<?php

namespace Svea\SveaPayment\Controller\Adminhtml\Order;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Svea\SveaPayment\Model\Order\Status\Query;
use Svea\SveaPayment\Model\Order\Status\Query\Status;
use Svea\SveaPayment\Model\Order\Status\Query\Validators\ManualQueryValidator;
use function __;
use function array_keys;
use function count;
use function implode;
use function sprintf;

class StatusCheck extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var Query
     */
    private Query $statusQuery;

    /**
     * @var ManualQueryValidator
     */
    private ManualQueryValidator $manualQueryValidator;

    /**
    * @var StoreManagerInterface
    */
    protected StoreManagerInterface $storeManager;

    /**
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param Query $statusQuery
     * @param ManualQueryValidator $manualQueryValidator
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context           $context,
        OrderRepositoryInterface $orderRepository,
        Query                    $statusQuery,
        ManualQueryValidator     $manualQueryValidator,
        StoreManagerInterface    $storeManager
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->statusQuery = $statusQuery;
        $this->manualQueryValidator = $manualQueryValidator;
        $this->storeManager = $storeManager;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        try {
            if ($this->manualQueryValidator->checkIsAllowed($this->getRequest())) {
                $this->check();
            }
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        return $resultRedirect;
    }

    /**
     * @return void
     * @throws Exception
     */
    private function check()
    {
        $orderId = $this->getRequest()->getParam('order_id', false);
        $storeId = $this->getRequest()->getParam('store_id', 0);
        $time = $this->getRequest()->getParam('period', '-1 day');

        $statuses = [];
        $this->storeManager->setCurrentStore($storeId);
        if ($orderId !== false) {
            $order = $this->orderRepository->get($orderId);
            if ($order) {
                $statuses = $this->statusQuery->queryOrders([$order], true);
            }
        } elseif (!empty($time)) {
            $statuses = $this->statusQuery->querySince($time, '-1 minutes', true);
        } else {
            throw new Exception('No target order id or period specified');
        }
        $this->reportStatuses($statuses);
    }

    /**
     * @param Status[] $statuses
     */
    private function reportStatuses(array $statuses): void
    {
        if (empty($statuses)) {
            $this->messageManager->addNoticeMessage(__('No orders to check'));
        } elseif (count($statuses) <= 5) {
            $this->messageManager->addSuccessMessage(__('Status Check: %1', $this->formatStatuses($statuses)));
        } else {
            $this->messageManager->addSuccessMessage(__('Checked order(s): ', array_keys($statuses)));
        }
    }

    /**
     * @param Status[] $statuses
     *
     * @return string
     */
    private function formatStatuses(array $statuses): string
    {
        $formatted = [];
        foreach ($statuses as $orderId => $status) {
            $formatted[] = sprintf('#%s: "%s"', $orderId, $status->getMessage());
        }

        return implode(', ', $formatted);
    }
}
