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

class Payin7_Payments_Block_Adminhtml_Sales_Order_Payment extends Mage_Adminhtml_Block_Sales_Order_Payment
{
    public function setPayment($payment)
    {
        parent::setPayment($payment);

        /** @var Mage_Sales_Model_Order $order */
        /** @noinspection PhpUndefinedMethodInspection */
        $order = $this->getParentBlock()->getOrder();

        /** @var Payin7_Payments_Model_Payin7daysPaymentMethod $payment_method */
        $payment = $order->getPayment();
        $payment_method = $payment->getMethodInstance();
        $payment_method_code = $payment_method->getCode();

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        if ($phelper->getIsPayin7PaymentMethod($payment_method_code)) {

            // add the bootstrap js
            $this->setChild('payin7_js_bootstrap', $this->getLayout()->createBlock('payin7payments/js_bootstrap'));

            /** @var Payin7_Payments_Block_Adminhtml_Sales_Order_Payin7_Info $payin7InfoBlock */
            $payin7InfoBlock = $this->getLayout()->createBlock('payin7payments/adminhtml_sales_order_payin7_info');
            $payin7InfoBlock->setOrder($order);

            $this->setChild('payin7_info', $payin7InfoBlock);
        }

        return $this;
    }

    protected function _toHtml()
    {
        return parent::_toHtml() .
        $this->getChildHtml('payin7_js_bootstrap') .
        $this->getChildHtml('payin7_info');
    }
}
