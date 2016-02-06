<?php

class Payin7_Payments_Model_Remote_Platform_Status extends Payin7_Payments_Model_Remote_Platform_Abstract
{
    public function getConfigKey()
    {
        return 'platform_status';
    }

    public function getApiMethod()
    {
        return 'getPlatformStatus';
    }

    public function getIsPlatformAvailable()
    {
        $platform_status = $this->getData('platform');
        $integration_status = $this->getData('integration');

        $platform_is_available =
            isset($platform_status['state']) && $platform_status['state'] &&
            isset($integration_status['state']) && $integration_status['state'];
        return $platform_is_available;
    }

    public function getIsPaymentMethodAvailable($remote_method_code)
    {
        $payment_methods_status = $this->getData('payment_methods');
        return isset($payment_methods_status[$remote_method_code]) && $payment_methods_status[$remote_method_code];
    }
}