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

abstract class Payin7_Payments_Model_Remote_Platform_Abstract extends Mage_Core_Model_Abstract
{
    const DATA_REFRESH_TIMEOUT = 300; // in seconds
    const UPDATE_CONNECT_TIMEOUT = 5; // fast!

    private static $remote_update_processed_once = array();

    abstract public function getConfigKey();

    abstract public function getApiMethod();

    public function getLastUpdated()
    {
        return $this->getData('last_updated');
    }

    public function loadData($force_remote_update = false)
    {
        $data_key = $this->getConfigKey();

        $this->loadDataInternal();

        $last_updated = $this->getLastUpdated();

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $refresh_timeout = $phelper->getDebugModeEnabled() ? 3 : self::DATA_REFRESH_TIMEOUT;
        $needs_update = $phelper->getDebugModeEnabled() || $force_remote_update || !$last_updated || !$this->getData() ||
            ($last_updated && $last_updated + $refresh_timeout < time());

        if ($needs_update && !isset(self::$remote_update_processed_once[$data_key])) {
            self::$remote_update_processed_once[$data_key] = true;

            // update the data
            if (!$new_data = $this->updatePlatformData($data_key)) {
                return null;
            }

            $this->setData($new_data);
        }
    }

    protected function loadDataInternal()
    {
        $data_key = $this->getConfigKey();

        /** @var Payin7_Payments_Model_Payin7data $model */
        $model = Mage::getModel('payin7payments/payin7data');
        $ret = $model->getPlatformData($data_key);

        $data = isset($ret['data']) ? $ret['data'] : null;
        $this->setData($data);
    }

    protected function updatePlatformData($data_key)
    {
        /** @var Payin7_Payments_Helper_Log $logger */
        $logger = Mage::helper('payin7payments/log');

        $logger->logDebug('Updating platform data: ' . $data_key);

        $api_method = $this->getApiMethod();

        /** @var Payin7_Payments_Model_Remote_Client $client */
        $client = Mage::getModel('payin7payments/remote_client');
        $client->getApi()->setConnectTimeout(self::UPDATE_CONNECT_TIMEOUT);

        $config = null;

        try {
            $config = $client->$api_method();
        } catch (Exception $e) {

            $logger->logError('Could not fetch remote platform data: ' . $data_key . ': ' . $e->getMessage());
            return null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $config = $config ? $config->toArray() : null;

        if ($config) {
            /** @var Payin7_Payments_Model_Payin7data $model */
            $model = Mage::getModel('payin7payments/payin7data');
            $model->updatePlatformData($data_key, $config);

            $logger->logDebug('Platform data updated: ' . $data_key . ': ' . print_r($config, true));
        }

        return $config;
    }

}