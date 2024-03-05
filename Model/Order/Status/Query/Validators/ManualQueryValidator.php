<?php

namespace Svea\SveaPayment\Model\Order\Status\Query\Validators;

use DateTimeInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Svea\SveaPayment\Gateway\Config\Config;
use function __;
use function date_create;

class ManualQueryValidator
{
    const QUERY_TYPE_SHORT = 'manual_query_check_short';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var Date
     */
    private Date $date;

    /**
     * @var TypeListInterface
     */
    private TypeListInterface $typeList;

    /**
     * @param Config $config
     * @param WriterInterface $writer
     * @param DateTime $dateTime
     * @param Date $date
     * @param TypeListInterface $typeList
     */
    public function __construct(
        Config            $config,
        WriterInterface   $writer,
        DateTime          $dateTime,
        Date              $date,
        TypeListInterface $typeList
    ) {
        $this->config = $config;
        $this->writer = $writer;
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->typeList = $typeList;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool
     * @throws LocalizedException
     */
    public function checkIsAllowed(RequestInterface $request): bool
    {
        $queryType = $request->getParam('query_type');
        if (!$queryType) {
            return true;
        }
        if ($queryType === self::QUERY_TYPE_SHORT) {
            return $this->validateQuery(Config::MANUAL_QUERY_CHECK_SHORT, '+1 minutes', $this->config->getManualQueryCheckShortValue());
        }

        return $this->validateQuery(Config::MANUAL_QUERY_CHECK_LONG, '+30 minutes', $this->config->getManualQueryCheckLongValue());
    }

    /**
     * @param string $configPath
     * @param string $interval
     * @param int|null $lastQueryValue
     *
     * @return bool
     * @throws LocalizedException
     */
    private function validateQuery(string $configPath, string $interval, ?int $lastQueryValue): bool
    {
        $date = date_create($this->dateTime->formatDate($this->date->gmtTimestamp()));
        if ($lastQueryValue === null) {
            $this->saveQueryTimestamp($configPath, $date, $interval);

            return true;
        }
        if (!$this->validateTimestamp($date->getTimestamp(), $lastQueryValue)) {
            throw new LocalizedException(__("The manual status query is not allowed currently. Executed too early."));
        }
        $this->saveQueryTimestamp($configPath, $date, $interval);

        return true;
    }

    /**
     * @param string $path
     * @param DateTimeInterface $date
     * @param string $interval
     *
     * @return void
     */
    private function saveQueryTimestamp(string $path, DateTimeInterface $date, string $interval): void
    {
        $date = $date->modify($interval);
        $this->writer->save($path, $date->getTimestamp());
        $this->typeList->cleanType("config");
    }

    /**
     * @param int $timeStamp
     * @param int $lastQuery
     *
     * @return bool
     */
    private function validateTimestamp(int $timeStamp, int $lastQuery): bool
    {
        return $timeStamp > $lastQuery;
    }
}
