<?php

class Payin7_Payments_Model_Mysql4_Payin7data_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('payin7payments/payin7data');
    }
}