<?php

class Payin7_Payments_Block_Order_Final extends Mage_Core_Block_Template
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('payin7payments/checkout/final.phtml');
    }

    protected function getOrder()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        /** @noinspection PhpUndefinedMethodInspection */
        $order->loadByIncrementId($session->getLastRealOrderId());

        return $order;
    }
}
