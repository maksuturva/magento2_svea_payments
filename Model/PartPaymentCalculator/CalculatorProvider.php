<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Svea\SveaPayment\Api\PartPaymentCalculator\CalculatorProviderInterface;
use Svea\SveaPayment\Api\PartPaymentCalculator\ModifierInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Source\CalculatorPlacement;
use Svea\SveaPayment\Model\Source\CommunicationEndpoint;
use function explode;
use function in_array;

class CalculatorProvider implements CalculatorProviderInterface
{
    public const PART_PAYMENT_METHOD_CODE = 'FI71';

    const PART_PAYMENT_PLANS_CACHE_KEY = 'svea_payment_part_payment_plans';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ModifierInterface
     */
    private ModifierInterface $modifier;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var string
     */
    private string $script = '';

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @param Config $config
     * @param ModifierInterface $modifier
     * @param RequestInterface $request
     * @param Session $checkoutSession
     */
    public function __construct(
        Config            $config,
        ModifierInterface $modifier,
        RequestInterface  $request,
        CacheInterface    $cache,
        Session           $checkoutSession,
    ) {
        $this->config = $config;
        $this->modifier = $modifier;
        $this->request = $request;
        $this->cache = $cache;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritDoc
     */
    public function isEnabledForLocation(): bool
    {
        return $this->isScriptAvailable();
    }

    /**
     * @inheritDoc
     */
    public function isAvailableForMethods(array $methods): bool
    {
        if ($this->config->isCalculatorEnabled()) {
            foreach ($methods as $method) {
                $methodCode = $method['code'] ?? null;
                if ($methodCode === self::PART_PAYMENT_METHOD_CODE) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getCalculatorScript(): ?string
    {
        if (!$this->isScriptAvailable()) {
            return null;
        }
        if (empty($this->script)) {
            $script = $this->config->getCalculatorScript();
            if (!empty($script)) {
                $this->script = $this->modifier->modify($script);
            }
        }

        return $this->script;
    }

    /**
     * @return bool
     */
    private function isScriptAvailable(): bool
    {
        return $this->config->isCalculatorEnabled() &&
            $this->isCalculatorPlacementAvailable($this->request->getFullActionName()) &&
            $this->isAvailableForCart($this->request->getFullActionName());
    }

    /**
     * @param string $layoutName
     *
     * @return bool
     */
    public function isCalculatorPlacementAvailable(string $layoutName): bool
    {
        return in_array($layoutName, explode(',', $this->config->getCalculatorPlacement()));
    }

    /**
     * @param string $layoutName
     *
     * @return bool
     */
    private function isAvailableForCart(string $layoutName): bool
    {
        if ($layoutName === CalculatorPlacement::CART) {
            try {
                $quote = $this->checkoutSession->getQuote();
            } catch (\Exception $e) {
                return false;
            }
            $total = $quote->getBaseGrandTotal();

            return $this->validatePrice($total, CalculatorPlacement::CART);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function validatePrice(float $price, string $location): bool
    {
        if (!$this->config->isCalculatorEnabled() || !$this->isCalculatorPlacementAvailable($location)) {
            return false;
        }

        $plans = $this->getProviderPaymentPlans();

        if (empty($plans)) {
            return false;
        }

        $min = !empty($this->config->getCalculatorMinimumThreshold())
            ? $this->config->getCalculatorMinimumThreshold()
            : $this->getProviderMinimumPaymentPlanFromAmount($plans);

        $max = $this->getProviderMinimumPaymentPlanToAmount($plans);

        return $price >= $min && $price <= $max;
    }

    private function getProviderPaymentPlans(): ?array
    {
        $plans = $this->cache->load(self::PART_PAYMENT_PLANS_CACHE_KEY);

        if (empty($plans)) {
            $plans = $this->doGetProviderPaymentPlans();

            if (!empty($plans)) {
                $this->cache->save($plans, self::PART_PAYMENT_PLANS_CACHE_KEY, ['SVEA'], 60 * 15);
            }
        }

        $decodedPlans = json_decode($plans, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decodedPlans;
    }

    private function getProviderMinimumPaymentPlanFromAmount(array $plans): float|int
    {
        if (!empty($plans) && !empty($plans["campaigns"])) {
            $amounts = array_map(fn($campaign) => (float)$campaign["FromAmount"], $plans["campaigns"]);

            return min($amounts);
        }

        return 0;
    }

    private function getProviderMinimumPaymentPlanToAmount(array $plans): float|int
    {
        if (!empty($plans) && !empty($plans["campaigns"])) {
            $amounts = array_map(fn($campaign) => (float)$campaign["ToAmount"], $plans["campaigns"]);

            return max($amounts);
        }

        return 0;
    }

    private function doGetProviderPaymentPlans(): string
    {
        try {
            $endpoint = $this->config->getCommunicationUrlReal();

            $client = new \GuzzleHttp\Client([
                'base_uri' => $endpoint,
            ]);

            $response = $client->request('GET', $this->config::PAYMENT_PLANS_URN, [
                'query' => [
                    'gpp_sellerid' => $this->config->getSellerId(),
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            }
        } catch (GuzzleException $e) {
            return '';
        }

        return '';
    }
}
