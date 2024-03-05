<?php
namespace Svea\SveaPayment\Model\Order;

class ReferenceNumberProvider
{
    /**
     * Form the reference number using the 7, 3, 1 method
     * @param int $number
     * @return string
     */
    public function getPmtReferenceNumber(int $number): string
    {
        $tmpMultip = [7, 3, 1];
        $tmpStr = (string)$number;
        $tmpSum = 0;
        $tmpIndex = 0;
        for ($i = \strlen($tmpStr) - 1; $i >= 0; $i--) {
            $tmpSum += (int)$tmpStr[$i] * $tmpMultip[$tmpIndex % 3];
            $tmpIndex++;
        }

        $nextTen = \ceil((int)$tmpSum / 10) * 10;

        return $tmpStr . (\abs($nextTen - $tmpSum));
    }
}
