<?php

/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */
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

    protected function redirectErr($is_checkout = false)
    {
        if ($is_checkout) {
            $this->_redirect('checkout/cart');
            return;
        } else {
            $this->_redirect('/');
            return;
        }
    }

    protected function verifyHashCheck(array $order_data, $hash_check)
    {
        return ($hash_check && $order_data && $hash_check == sha1(implode('', $order_data)));
    }

    protected function getVerifyOrder()
    {
        $request = $this->getRequest();

        $req_order_id = $request->get('order_id');
        $req_secure_key = $request->get('secure_key');

        $order_verified = $request->get('verified');
        $order_rejected = $request->get('rejected');
        $order_cancelled = $request->get('cancelled');
        $order_paid = $request->get('paid');
        $order_state = $request->get('state');

        $verify_hash = $request->get('hash');

        $is_saved_order = (bool)$request->get('saved_order');
        $is_checkout = !$is_saved_order;

        if (!$is_checkout && !$request->isPost()) {
            return null;
        }

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $order = null;

        if ($is_checkout) {
            $order = $phelper->getLastOrder();
        } elseif ($req_order_id && $req_secure_key) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');

            /** @noinspection PhpUndefinedMethodInspection */
            $order->loadByAttribute('payin7_order_identifier', $req_order_id);

            // verify the secure key
            $key_verified = $phelper->verifyOrderSecureKey($order, $req_secure_key);

            if (!$key_verified) {
                return null;
            }
        }

        if (!$order || !$order->getId()) {
            return null;
        }

        // validate the hash
        if (!$is_saved_order) {
            $order_api_key = $phelper->getApiKeyForOrder($order);

            if (!$this->verifyHashCheck(array(
                $req_secure_key,
                $req_order_id,
                (int)$order_verified,
                (int)$order_rejected,
                (int)$order_cancelled,
                (int)$order_paid,
                $order_state,
                $order_api_key
            ), $verify_hash)
            ) {
                return null;
            }
        }

        // verify if the order is really a payin7 order
        $payment = $order->getPayment();
        $payment_method = $payment->getMethodInstance();
        $payment_method_code = $payment_method->getCode();

        if (!$phelper->getIsPayin7PaymentMethod($payment_method_code)) {
            return null;
        }

        return $order;
    }

    public function completeAction()
    {
        $request = $this->getRequest();
        $is_saved_order = $request->get('saved_order');
        $is_checkout = !$is_saved_order;

        $order_verified = $request->get('verified');
        $order_paid = $request->get('paid');

        if (!$is_saved_order && !$request->isPost()) {
            $this->redirectErr($is_checkout);
        }

        if (!$order = $this->getVerifyOrder()) {
            $this->redirectErr($is_checkout);
        }

        $session = null;
        $lastOrderId = null;

        if ($is_checkout) {
            /** @var Mage_Checkout_Model_Type_Onepage $onepage */
            $onepage = Mage::getSingleton('checkout/type_onepage');
            $session = $onepage->getCheckout();
        }

        if ($order_verified && $order_paid) {
            if (!$order->getData('payin7_order_accepted')) {
                // inform
                Mage::dispatchEvent('payin7_order_complete_before', array('order' => $order));

                // mark as order accepted
                $order->setData('payin7_order_accepted', true);

                // set it to processing
                $order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING);
                $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
                $history = $order->addStatusHistoryComment('Order fully completed by customer', false);
                /** @noinspection PhpUndefinedMethodInspection */
                $history->setIsCustomerNotified(false);

                $order->save();

                // inform
                Mage::dispatchEvent('payin7_order_complete_after', array('order' => $order));
            }
        }

        if ($is_checkout) {
            $this->loadLayout();

            if ($session) {
                $session->clear();
            }

            $this->_initLayoutMessages('checkout/session');
            Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
            $this->renderLayout();
        } else {
            $this->_redirect('sales/order/view', array('order_id' => $order->getId()));
        }
    }

    public function cancelAction()
    {
        $request = $this->getRequest();
        $is_saved_order = $request->get('saved_order');
        $order_rejected = $request->get('rejected');
        $is_checkout = !$is_saved_order;

        if (!$is_saved_order && !$request->isPost()) {
            $this->redirectErr($is_checkout);
        }

        if (!$order = $this->getVerifyOrder()) {
            $this->redirectErr($is_checkout);
        }

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        // if rejected flag has been set - set the permanent reject cookie
        if ($order_rejected) {
            $phelper->setRejectVerificationCookie(true);
        }

        if ($order->canUnhold() || $order->canCancel()) {
            $phelper->getLogger()->logInfo('Cancelling order: ' . $order->getData('payin7_order_identifier'));

            // inform
            Mage::dispatchEvent('payin7_order_cancel_before', array('order' => $order));

            if ($order->canUnhold()) {
                $order->unhold()->save();
            }

            if ($order->canCancel()) {
                $order->cancel()->save();
            }

            // inform
            Mage::dispatchEvent('payin7_order_cancel_after', array('order' => $order));
        }

        if ($is_checkout) {
            // restore the cart with the last order's items
            $phelper->restoreCardWithOrder($order);

            // redirect back to cart / checkout
            $this->_redirect($phelper->getCheckoutRedirectUrl());
        } else {
            $this->_redirect('sales/order/view', array('order_id' => $order->getId()));
        }
    }
}
