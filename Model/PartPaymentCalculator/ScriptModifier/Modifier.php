<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier;

use Magento\Framework\Simplexml\Element;
use function dom_import_simplexml;
use function simplexml_load_string;
use function substr_replace;

class Modifier
{
    /**
     * @param string $script
     * @param string $attributeName
     * @param string $value
     *
     * @return string
     */
    public function setAttribute(string $script, string $attributeName, string $value): string
    {
        $xml = simplexml_load_string($script, Element::class);
        if ($xml) {
            $xml->setAttribute($attributeName, $value);
            $domXml = dom_import_simplexml($xml);
            // Get result without xml tags
            $script = $domXml->ownerDocument->saveXML($domXml->ownerDocument->documentElement);
            $script = $this->modifyClosingTag($script);
        }

        return $script;
    }

    /**
     * Modify closing /> to ></script>
     *
     * @param string $script
     *
     * @return string
     */
    private function modifyClosingTag(string $script): string
    {
        return substr_replace($script, '></script>', -2);
    }
}
