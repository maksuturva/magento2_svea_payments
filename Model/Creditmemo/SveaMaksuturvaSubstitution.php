<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Model\Creditmemo;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Svea\SveaPayment\Model\Payment\Method;

class SveaMaksuturvaSubstitution
{
    const SVEA_MAKSUTURVA_SUBSTITUTION_PAYMENT_CODE = 'svea_maksuturva_substitution_payment';

    /**
     * @var Data
     */
    private Data $paymentData;

    /**
     * @var Method
     */
    private Method $method;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param Data $paymentData
     * @param Method $method
     * @param RequestInterface $request
     */
    public function __construct(
        Data             $paymentData,
        Method           $method,
        RequestInterface $request
    ) {
        $this->paymentData = $paymentData;
        $this->method = $method;
        $this->request = $request;
    }

    /**
     * @param MethodInterface $paymentMethod
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isMaksuturvaRefund(MethodInterface $paymentMethod): bool
    {
        return $this->method->isMaksuturva($paymentMethod) &&
            in_array($this->request->getControllerName(), [
                'order_creditmemo',
                'order_invoice'
            ]);

    }

    /**
     * @return MethodInterface
     * @throws LocalizedException
     */
    public function getSubstitutionPaymentMethod(): MethodInterface
    {
        return $this->paymentData->getMethodInstance(self::SVEA_MAKSUTURVA_SUBSTITUTION_PAYMENT_CODE);
    }
}
