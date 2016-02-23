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

class Payin7_Payments_Model_Observer
{
    const QUICK_HISTORY_SEND_TIMEOUT = 5;

    /** @var Mage_Sales_Model_Order */
    protected $_temp_order_before;

    /**
     * @return $this
     * @internal param Varien_Event_Observer $observer
     */
    public function beforeControllerFrontInit()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $utilsd = Mage::getBaseDir('lib') . DS . 'Payin7' . DS . 'utils';

        if ($phelper->getDebugModeEnabled()) {
            // include generic utils
            /** @noinspection PhpIncludeInspection */
            require_once($utilsd . DS . 'utils.php');
        }

        require_once($utilsd . DS . 'StringUtils.php');
        require_once($utilsd . DS . 'Unicode.php');
        require_once($utilsd . DS . 'Dirs.php');

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderSaveBefore($observer)
    {
        $event = $observer->getEvent();
        /** @var Mage_Sales_Model_Order $order_after */
        /** @noinspection PhpUndefinedMethodInspection */
        $order_after = $event->getOrder();
        $this->_temp_order_before = Mage::getModel('sales/order')->load($order_after->getId());
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderSaveAfter($observer)
    {
        $event = $observer->getEvent();
        /** @noinspection PhpUndefinedMethodInspection */
        /** @var Mage_Sales_Model_Order $order_after */
        $order_after = $event->getOrder();

        if ($order_after && $this->_temp_order_before &&
            $order_after->getId() == $this->_temp_order_before->getId()
        ) {
            $order_before = $this->_temp_order_before;

            if ($order_after && $order_before) {
                $status_before = $order_before->getStatus();
                $status_after = $order_after->getStatus();

                $state_before = $order_before->getState();
                $state_after = $order_after->getState();

                $state_changed = (($status_before != $status_after) ||
                    ($state_before != $state_after));

                /** @var Payin7_Payments_Model_Payin7orderhistory $mh */
                $mh = Mage::getModel('payin7payments/payin7orderhistory');

                if ($state_changed) {
                    $mh->markOrderStateChanged($order_after);
                } else {
                    $mh->markOrderUpdated($order_after);
                }

                $this->_flushFastOrderHistory();
            }

            $this->_temp_order_before = null;
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderInvoiceSaveAfter($observer)
    {
        $event = $observer->getEvent();

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var Mage_Sales_Block_Order_Invoice $invoice_after */
        $invoice_after = $event->getInvoice();

        if ($invoice_after) {
            /** @var Payin7_Payments_Model_Payin7orderhistory $mh */
            $mh = Mage::getModel('payin7payments/payin7orderhistory');
            $mh->markOrderDocumentUpdated($invoice_after->getOrder(),
                Payin7_Payments_Model_Payin7orderhistory::DOC_TYPE_INVOICE,
                $invoice_after);

            $this->_flushFastOrderHistory();
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderShipmentSaveAfter($observer)
    {
        $event = $observer->getEvent();

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var Mage_Sales_Block_Order_Shipment $shipment_after */
        $shipment_after = $event->getShipment();

        if ($shipment_after) {
            /** @var Payin7_Payments_Model_Payin7orderhistory $mh */
            $mh = Mage::getModel('payin7payments/payin7orderhistory');
            $mh->markOrderDocumentUpdated($shipment_after->getOrder(),
                Payin7_Payments_Model_Payin7orderhistory::DOC_TYPE_SHIPMENT,
                $shipment_after);

            $this->_flushFastOrderHistory();
        }

        return $this;
    }

    protected function _flushFastOrderHistory()
    {
        // send right away if possible, if not - handle it later with cron
        /** @var Payin7_Payments_Model_Remote_Order_History $history_sender */
        $history_sender = Mage::getModel('payin7payments/remote_order_history');
        $history_sender->setClientTimeout(self::QUICK_HISTORY_SEND_TIMEOUT);
        $history_sender->sendPendingOrderHistory();
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function salesOrderCreditmemoSaveAfter($observer)
    {
        $event = $observer->getEvent();

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var Mage_Sales_Block_Order_Creditmemo $credit_memo_after */
        $credit_memo_after = $event->getCreditmemo();

        if ($credit_memo_after) {
            /** @var Payin7_Payments_Model_Payin7orderhistory $mh */
            $mh = Mage::getModel('payin7payments/payin7orderhistory');
            $mh->markOrderDocumentUpdated($credit_memo_after->getOrder(),
                Payin7_Payments_Model_Payin7orderhistory::DOC_TYPE_CREDIT_MEMO,
                $credit_memo_after);

            $this->_flushFastOrderHistory();
        }

        return $this;
    }
}
