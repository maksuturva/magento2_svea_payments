<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifiers;

use Magento\Framework\Locale\ResolverInterface;
use Svea\SveaPayment\Api\PartPaymentCalculator\ModifierInterface;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifier;
use function explode;
use function in_array;

class DataLocale implements ModifierInterface
{
    private const DATA_LOCALE_ATTRIBUTE = 'data-locale';
    private const DEFAULT_LOCALE = 'en';
    private const SVEA_LOCALES = [
        'en',
        'fi',
        'sv'
    ];

    /**
     * @var Modifier
     */
    private Modifier $modifier;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $localeResolver;

    /**
     * @param Modifier $modifier
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Modifier          $modifier,
        ResolverInterface $localeResolver
    ) {
        $this->modifier = $modifier;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritDoc
     */
    public function modify(string $script): string
    {
        $locale = $this->localeResolver->getLocale();
        $locale = explode('_', $locale)[0];
        if (!in_array($locale, self::SVEA_LOCALES)) {
            $locale = self::DEFAULT_LOCALE;
        }

        return $this->modifier->setAttribute(
            $script,
            self::DATA_LOCALE_ATTRIBUTE,
            $locale
        );
    }
}
