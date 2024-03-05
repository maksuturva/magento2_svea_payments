<?php
namespace Svea\SveaPayment\Gateway\Response\Payment;

class CallbackState
{
    /**
     * @param array $params
     *
     * @return bool
     */
    public function resolveIsCallback(array $params): bool
    {
        return ($params['X-Svea-Callback'] ?? null) === "true";
    }
}
