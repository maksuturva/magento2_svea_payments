<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Svea\SveaPayment\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

use Magento\Framework\Module\Manager;

class RecurringData implements InstallDataInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @param Manager $moduleManager
     */
    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @inheritdoc
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $errors = [];
        $oldSveaModules = [
            'Svea_Maksuturva',
            'Svea_MaksuturvaCard',
            'Svea_MaksuturvaBase',
            'Svea_MaksuturvaCollated',
            'Svea_MaksuturvaGeneric',
            'Svea_MaksuturvaInvoice',
            'Svea_MaksuturvaPartPayment',
            'Svea_OrderComment'
        ];

        foreach($oldSveaModules as $sveaModule) {
            if($this->moduleManager->isEnabled($sveaModule)) {
                $errors[] = $sveaModule;
            }
        }

        if(sizeof($errors) > 0) {
            die("\nPlease disable old Svea modules: \n".
                "php bin/magento module:disable ".implode(' ', $errors)."\n");
        }
    }

}
