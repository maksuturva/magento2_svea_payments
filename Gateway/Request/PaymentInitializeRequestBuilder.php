<?php
namespace Svea\SveaPayment\Gateway\Request;

use Magento\Framework\ObjectManager\TMapFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Model\InfoInterface;
use Svea\SveaPayment\Model\Request\PaymentDataFormatter;

class PaymentInitializeRequestBuilder extends \Magento\Payment\Gateway\Request\BuilderComposite
{
    /**
     * @var PaymentDataObjectFactoryInterface
     */
    private $paymentFactory;

    /**
     * @var PaymentDataFormatter
     */
    private $dataFormatter;

    public function __construct(
        TMapFactory $tmapFactory,
        PaymentDataObjectFactoryInterface $paymentDataFactory,
        PaymentDataFormatter $dataFormatter,
        array $builders = []
    ) {
        parent::__construct($tmapFactory, $builders);
        $this->paymentFactory = $paymentDataFactory;
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        return $this->dataFormatter->format(parent::build($buildSubject));
    }

    /**
     * @param InfoInterface $payment
     *
     * @return array
     */
    public function buildFrom(InfoInterface $payment)
    {
        $subject = [
            'payment' => $this->paymentFactory->create($payment),
        ];

        return $this->build($subject);
    }
}
