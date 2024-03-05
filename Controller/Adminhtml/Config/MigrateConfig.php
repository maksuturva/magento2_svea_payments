<?php

namespace Svea\SveaPayment\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Svea\SveaPayment\Model\ResourceModel\Migrate\MigrateConfigInterface;

class MigrateConfig extends Action
{
    /**
     * @var MigrateConfigInterface
     */
    private MigrateConfigInterface $migrateConfig;

    /**
     * @param Context $context
     * @param MigrateConfigInterface $migrateConfig
     */
    public function __construct(
        Context                $context,
        MigrateConfigInterface $migrateConfig,
    ) {
        $this->migrateConfig = $migrateConfig;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->migrateConfig->execute();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        return $resultRedirect;
    }
}
