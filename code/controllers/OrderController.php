<?php

class Payin7_Payments_OrderController extends Mage_Core_Controller_Front_Action
{
    public function finalAction()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');
        $order = $phelper->getLastOrder();

        if (!$order) {
            $this->_redirect('checkout/cart');
            return;
        }

        // inform
        Mage::dispatchEvent('payin7_order_final', array('order' => $order));

        $this->loadLayout();
        $this->renderLayout();
    }

    public function completeAction()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');
        $order = $phelper->getLastOrder();

        if (!$order) {
            $this->_redirect('checkout/cart');
            return;
        }

        /** @var Mage_Checkout_Model_Type_Onepage $onepage */
        $onepage = Mage::getSingleton('checkout/type_onepage');
        $session = $onepage->getCheckout();

        /** @noinspection PhpUndefinedMethodInspection */
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $lastQuoteId = $session->getLastQuoteId();
        /** @noinspection PhpUndefinedMethodInspection */
        $lastOrderId = $session->getLastOrderId();
        /** @noinspection PhpUndefinedMethodInspection */
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();

        if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            $this->_redirect('checkout/cart');
            return;
        }

        // mark as order accepted
        $order->setData('payin7_order_accepted', true);
        $order->save();

        // inform
        Mage::dispatchEvent('payin7_order_complete', array('order' => $order));

        $session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
        $this->renderLayout();
    }

    public function cancelAction()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $order = $phelper->getLastOrder();

        if (!$order) {
            $this->_redirect('checkout/cart');
            return;
        }

        // inform
        Mage::dispatchEvent('payin7_order_cancel', array('order' => $order));

        if ($order->canUnhold()) {
            $order->unhold()->save();
        }

        if ($order->canCancel()) {
            $order->cancel()->save();
        }

        // restore the cart with the last order's items
        $phelper->restoreCardWithOrder($order);

        // redirect back to cart / checkout
        $this->_redirect($phelper->getCheckoutRedirectUrl());
    }
}
