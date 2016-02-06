<?php

class Payin7_Payments_Model_Config_Shipping_Methods
{
    public function toOptionArray()
    {
        $methods = array(array('value' => null, 'label' => null));

        /** @var Mage_Shipping_Model_Config $cfg */
        $cfg = Mage::getSingleton('shipping/config');
        $carriers = $cfg->getAllCarriers();

        foreach ($carriers as $carrierCode => $carrierModel) {
            $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');

            $methods[$carrierCode] = array(
                'label' => $carrierTitle,
                'value' => $carrierCode,
            );
        }

        return $methods;
    }
}
