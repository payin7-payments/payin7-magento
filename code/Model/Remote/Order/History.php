<?php

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;

class Payin7_Payments_Model_Remote_Order_History extends Mage_Core_Model_Abstract
{
    protected $_client_timeout;

    const MAX_CRON_ORDERS = 10;

    /**
     * @return array
     */
    protected function _prepareOrderData()
    {
        $data = array_filter(array(
            'currency_code' => $this->_order->getOrderCurrencyCode(),
            'shipping_method_code' => $this->_order->getShippingMethod(true)->getData('carrier_code'),
            'shipping_method_title' => $this->_order->getShippingDescription(),
            'created_on' => $this->_order->getCreatedAt(),
            'updated_on' => $this->_order->getUpdatedAt(),
            'state' => $this->_order->getState(),
            'status' => $this->_order->getStatus(),
            'is_gift' => ($this->_order->getGiftMessageId() != null),
            'ref_quote_id' => $this->_order->getQuoteId(),
            'order_subtotal_with_tax' => $this->_order->getSubtotalInclTax(),
            'order_subtotal' => $this->_order->getSubtotal(),
            'order_tax' => $this->_order->getTaxAmount(),
            'order_hidden_tax' => $this->_order->getHiddenTaxAmount(),
            'order_shipping_with_tax' => $this->_order->getShippingInclTax(),
            'order_shipping' => $this->_order->getShippingAmount(),
            'order_discount' => $this->_order->getDiscountAmount(),
            'order_shipping_discount' => $this->_order->getShippingDiscountAmount(),
            'order_total' => $this->_order->getGrandTotal(),
            'order_total_items' => $this->_order->getTotalItemCount()
        ));
        return $data;
    }

    public function sendPendingOrderHistory()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $data = Mage::getModel('payin7payments/payin7orderhistory')->getCollection()
            ->setPageSize(self::MAX_CRON_ORDERS)
            ->setCurPage(1)
            ->setOrder('history_id', 'ASC');
        $cd = count($data);

        if ($cd) {
            $phelper->getLogger()->logInfo('Order history sending started - sending ' . $cd . ' updates...');

            try {
                /** @noinspection PhpParamsInspection */
                $success = $this->submit($data);
                $phelper->getLogger()->logInfo('Order history sending completed, status: ' . ($success ? 'SUCCESS' : 'ERROR') . ' - ' . $cd);
            } catch (Exception $e) {
                $phelper->getLogger()->logError('Order history could not be sent - unhandled exception: ' . $e->getMessage() . ', file: ' .
                    $e->getFile() . ', line: ' . $e->getLine() . ', trace: ' . $e->getTraceAsString());
                throw $e;
            }
        }
    }

    public function setClientTimeout($timeout)
    {
        $this->_client_timeout = $timeout;
    }

    protected function submit(Payin7_Payments_Model_Mysql4_Payin7orderhistory_Collection $data)
    {
        $data_out = array();

        /** @var Varien_Object $history_el */
        foreach ($data as $history_el) {
            $data_out[] = array(
                'order_id' => $history_el->getData('order_id'),
                'history_id' => $history_el->getData('history_id'),
                'order_unique_id' => $history_el->getData('order_unique_id'),
                'created_on' => $history_el->getData('created_on'),
                'change_type' => $history_el->getData('change_type'),
                'data' => @unserialize($history_el->getData('data')),
            );
            unset($history_el);
        }


        /** @var Payin7_Payments_Model_Remote_Client $client */
        $client = Mage::getModel('payin7payments/remote_client');

        if ($this->_client_timeout) {
            $client->getApi()->setConnectTimeout($this->_client_timeout);
            $client->getApi()->setTimeout($this->_client_timeout);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $response = $client->postOrderHistory(array(
            'history' => json_encode($data_out)
        ));

        if (!is_array($response) || !$response['success']) {
            return false;
        }

        foreach ($data as $history_el) {
            /** @noinspection PhpUndefinedMethodInspection */
            $history_el->delete();
            unset($history_el);
        }

        return true;
    }
}