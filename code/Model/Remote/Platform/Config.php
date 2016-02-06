<?php

class Payin7_Payments_Model_Remote_Platform_Config extends Payin7_Payments_Model_Remote_Platform_Abstract
{
    public function getConfigKey()
    {
        return 'platform_config';
    }

    public function getApiMethod()
    {
        return 'getPlatformConfig';
    }

    public function getPaymentMethodsConfig()
    {
        return $this->getData('payment_methods');
    }

    public function getPaymentMethodConfig($remote_method_code)
    {
        $methods = $this->getData('payment_methods');
        return (isset($methods[$remote_method_code]) ? $methods[$remote_method_code] : null);
    }
}