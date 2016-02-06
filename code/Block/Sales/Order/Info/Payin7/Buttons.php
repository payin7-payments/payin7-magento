<?php

class Payin7_Payments_Block_Sales_Order_Info_Payin7_Buttons extends Mage_Sales_Block_Order_Info_Buttons
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('payin7payments/sales/order/payin7_buttons.phtml');
    }
}
