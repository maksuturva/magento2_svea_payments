<?php

namespace Svea\SveaPayment\Model\Quote;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Quote\Api\CartRepositoryInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Source\RestoreShoppingCart;

class QuoteCancellation
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Session
     */
    private Session $session;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Config $config
     * @param Session $session
     * @param CartRepositoryInterface $quoteRepository
     * @param Logger $logger
     */
    public function __construct(
        Config                  $config,
        Session                 $session,
        CartRepositoryInterface $quoteRepository,
        Logger                  $logger
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @param string $case
     *
     * @return void
     */
    public function cancelQuote(string $case): void
    {
        $configValue = $this->config->getRestoreShoppingCart();
        if ($configValue === RestoreShoppingCart::NEVER ||
            ($configValue !== $case && $configValue !== RestoreShoppingCart::BOTH)
        ) {
            try {
                $quote = $this->quoteRepository->get($this->session->getQuoteId());
                $quote->setIsActive(false);
                $this->quoteRepository->save($quote);
                $this->session->clearQuote()->clearStorage();
            } catch (NoSuchEntityException $e) {
                $this->logger->critical($e);
            }
        }
    }
}
