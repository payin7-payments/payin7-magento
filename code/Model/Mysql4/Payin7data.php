<?php

class Payin7_Payments_Model_Mysql4_Payin7data extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('payin7payments/payin7data', 'data_id');
    }
}