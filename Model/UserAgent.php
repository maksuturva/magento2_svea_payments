<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Composer\ComposerInformation;
use function mb_convert_encoding;
use function php_uname;
use function phpversion;
use function sprintf;

class UserAgent
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ComposerInformation
     */
    private $composerInformation;

    public function __construct(
        ProductMetadataInterface $productMetadata,
        ComposerInformation      $composerInformation
    ) {
        $this->productMetadata = $productMetadata;
        $this->composerInformation = $composerInformation;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return mb_convert_encoding(
            sprintf(
                '%s (%s %s %s)',
                $this->getPaymentModuleVersion(),
                $this->getOperatingSystem(),
                $this->getMagentoVersion(),
                $this->getPHPVersion()
            ),
            'ASCII'
        );
    }

    /**
     * @return string
     */
    private function getPaymentModuleVersion()
    {
        return sprintf('Svea Payments for Magento %s', $this->getSveaPaymentModuleVersion());
    }

    /**
     * @return string
     */
    private function getSveaPaymentModuleVersion()
    {
        $packages = $this->composerInformation->getInstalledMagentoPackages();
        $sveaPayment = $packages['svea/payment'] ?? null;
        if ($sveaPayment) {
            return $sveaPayment['version'] ?? '';
        }

        return '';
    }

    /**
     * @return string
     */
    private function getOperatingSystem()
    {
        return sprintf('%s/%s', php_uname('s'), php_uname('r'));
    }

    /**
     * @return string
     */
    private function getMagentoVersion()
    {
        return sprintf(
            '%s%s/%s',
            $this->productMetadata->getName(),
            $this->productMetadata->getEdition(),
            $this->productMetadata->getVersion()
        );
    }

    /**
     * @return string
     */
    private function getPHPVersion()
    {
        return sprintf('PHP/%s', phpversion());
    }
}
