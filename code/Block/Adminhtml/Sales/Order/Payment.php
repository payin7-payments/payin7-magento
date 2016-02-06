<?php

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
        $this->getChildHtml('payin7_info');
    }
}
