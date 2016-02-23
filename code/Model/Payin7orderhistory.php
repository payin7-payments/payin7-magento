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

class Payin7_Payments_Model_Payin7orderhistory extends Mage_Core_Model_Abstract
{
    const ORDER_STATE_CHANGED = 'order_state_changed';
    const ORDER_UPDATED = 'order_updated';
    const DOC_UPDATED = 'doc_updated';

    const DOC_TYPE_SHIPMENT = 'shipment';
    const DOC_TYPE_INVOICE = 'invoice';
    const DOC_TYPE_CREDIT_MEMO = 'creditmemo';

    public function _construct()
    {
        parent::_construct();
        $this->_init('payin7payments/payin7orderhistory');
    }

    protected function _filterModelSimpleData(array $data = null)
    {
        $out_data = array();

        if ($data) {
            foreach ($data as $k => $v) {
                if (is_string($v) || is_numeric($v) || is_array($v)) {
                    $out_data[$k] = $v;
                }
                unset($k, $v);
            }
        }

        return $out_data;
    }

    public function markOrderDocumentUpdated(Mage_Sales_Model_Order $order, $document_type, Varien_Object $document_object = null)
    {
        return $this->_saveOrderHistory($order, self::DOC_UPDATED, array(
            'document_type' => $document_type,
            'document_data' => ($document_object ? $this->_filterModelSimpleData($document_object->getData()) : null)
        ));
    }

    public function markOrderUpdated(Mage_Sales_Model_Order $order)
    {
        return $this->_saveOrderHistory($order, self::ORDER_UPDATED, $this->_filterModelSimpleData($order->getData()));
    }

    public function markOrderStateChanged(Mage_Sales_Model_Order $order)
    {
        return $this->_saveOrderHistory($order, self::ORDER_STATE_CHANGED, array(
            'state' => $order->getState(),
            'status' => $order->getStatus()
        ));
    }

    protected function _saveOrderHistory(Mage_Sales_Model_Order $order, $change_type, $data = null)
    {
        $uid = $order->getData('payin7_order_identifier');
        $order_sent = $order->getData('payin7_order_sent');

        if (!$uid || !$order_sent) {
            return false;
        }

        $m = Mage::getModel('payin7payments/payin7orderhistory');
        $m->setData(array(
            'order_id' => $order->getId(),
            'order_unique_id' => $uid,
            'created_on' => date('Y-m-d H:i:s'),
            'change_type' => $change_type,
            'data' => ($data ? @serialize($data) : null)
        ));
        $m->save();

        return $this;
    }
}