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

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;

class Payin7_Payments_Model_Remote_Order_History extends Mage_Core_Model_Abstract
{
    protected $_client_timeout;

    const MAX_CRON_ORDERS = 10;

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
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

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
            $phelper->getLogger()->logInfo('Remote history sending failed, data: ' . print_r($response, true));

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