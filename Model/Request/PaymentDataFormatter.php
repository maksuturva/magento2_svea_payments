<?php

namespace Svea\SveaPayment\Model\Request;

use Svea\SveaPayment\Gateway\Request\PaymentConfigBuilder;
use Svea\SveaPayment\Gateway\Request\RowDataBuilder;

use function in_array;
use function mb_convert_encoding;

class PaymentDataFormatter
{
    /**
     * @var PaymentDataSpecification
     */
    private $dataSpecification;

    public function __construct(
        PaymentDataSpecification $dataSpecification
    ) {
        $this->dataSpecification = $dataSpecification;
    }

    /**
     * Validates the data and formats the row data fields in the request
     *
     * @param array $requestData
     * @return array
     */
    public function format(array $requestData) : array
    {
        $charsetHttp = $requestData[PaymentConfigBuilder::CHARSET_HTTP];

        $formFields = [];

        foreach ($requestData as $key => $data) {
            if ($key === RowDataBuilder::ROWS_DATA) {
                $rowCount = 1;
                foreach ($data as $rowData) {
                    foreach ($rowData as $rowKey => $rowInnerData) {
                        $rowInnerData = $this->processLenghts($rowKey, $rowInnerData);
                        $formFields[mb_convert_encoding($rowKey . $rowCount, $charsetHttp)]
                            = mb_convert_encoding($rowInnerData, $charsetHttp);
                    }
                    $rowCount++;
                }
                continue;
            }
            $data = $this->processLenghts($key, $data);
            $formFields[mb_convert_encoding($key, $charsetHttp)] = mb_convert_encoding($data, $charsetHttp);
        }

        return $formFields;
    }

    /**
     * @param string $field
     * @param string $data
     *
     * @return string
     * @throws \Exception
     */
    private function processLenghts($field, $data)
    {
        if (!\array_key_exists($field, $this->dataSpecification->getFieldLengths())) {
            return $data;
        }

        $lengthSpec = $this->dataSpecification->getFieldLengths()[$field];
        $length = \mb_strlen($data);
        $isCompulsory = in_array($field, $this->dataSpecification->getCompulsoryFields())
            || in_array($field, $this->dataSpecification->getCompulsoryRowFields());
        $isOptional = in_array($field, $this->dataSpecification->getOptionalFields())
            || in_array($field, $this->dataSpecification->getOptionalRowFields());

        if ($isCompulsory || ($length > 0 && $isOptional)) {
            $data = $this->processLength($field, $data, $length, $lengthSpec[0], $lengthSpec[1]);
        }

        return $data;
    }

    /**
     * @param string $field
     * @param string $data
     * @param int $length
     * @param int $min
     * @param int $max
     *
     * @return string
     * @throws \Exception
     */
    private function processLength($field, $data, $length, $min, $max)
    {
        if ($length < $min) {
            throw new \Exception(\sprintf('Field "%s" should be at least %d characters long', $field, $min));
        } elseif ($length > $max) {
            $data = \mb_substr($data, 0, $max);
        }

        return $data;
    }
}
