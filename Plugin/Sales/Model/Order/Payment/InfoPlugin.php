<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Plugin\Sales\Model\Order\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment\Info;
use Svea\SveaPayment\Model\Creditmemo\SveaMaksuturvaSubstitution;

class InfoPlugin
{
    /**
     * @var SveaMaksuturvaSubstitution
     */
    private SveaMaksuturvaSubstitution $sveaMaksuturvaSubstitution;

    /**
     * @param SveaMaksuturvaSubstitution $sveaMaksuturvaSubstitution
     */
    public function __construct(
        SveaMaksuturvaSubstitution $sveaMaksuturvaSubstitution
    ) {

        $this->sveaMaksuturvaSubstitution = $sveaMaksuturvaSubstitution;
    }

    /**
     * @param Info $subject
     * @param MethodInterface $result
     *
     * @return MethodInterface
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetMethodInstance(Info $subject, MethodInterface $result)
    {
        if ($this->sveaMaksuturvaSubstitution->isMaksuturvaRefund($result)) {
            return $this->sveaMaksuturvaSubstitution->getSubstitutionPaymentMethod();
        }

        return $result;
    }
}
