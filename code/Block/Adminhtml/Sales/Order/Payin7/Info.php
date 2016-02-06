<?php

class Payin7_Payments_Block_Adminhtml_Sales_Order_Payin7_Info extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('payin7payments/order/payment_info.phtml');
    }

    public function setOrder($order)
    {
        $this->_order = $order;
    }

    public function getOrder()
    {
        return $this->_order;
    }
}
