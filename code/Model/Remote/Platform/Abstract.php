<?php

abstract class Payin7_Payments_Model_Remote_Platform_Abstract extends Mage_Core_Model_Abstract
{
    const DATA_REFRESH_TIMEOUT = 60; // in seconds

    abstract public function getConfigKey();

    abstract public function getApiMethod();

    public function loadData()
    {
        $data_key = $this->getConfigKey();

        /** @var Payin7_Payments_Model_Payin7data $model */
        $model = Mage::getModel('payin7payments/payin7data');
        $ret = $model->getPlatformData($data_key);

        $data = isset($ret['data']) ? $ret['data'] : null;
        $this->setData($data);
    }

    public function getLastUpdated()
    {
        return $this->getData('last_updated');
    }

    public function updateData($force = false)
    {
        $data_key = $this->getConfigKey();

        $this->loadData();

        $last_updated = $this->getLastUpdated();

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $needs_update = $phelper->getDebugModeEnabled() || $force || !$last_updated || !$this->getData() ||
            ($last_updated && $last_updated + self::DATA_REFRESH_TIMEOUT < time());

        if ($needs_update) {
            // update the data
            if (!$new_data = $this->updatePlatformData($data_key)) {
                return null;
            }

            $this->setData($new_data);
        }
    }

    protected function updatePlatformData($data_key)
    {
        /** @var Payin7_Payments_Helper_Log $logger */
        $logger = Mage::helper('payin7payments/log');

        $logger->logDebug('Updating platform data: ' . $data_key);

        $api_method = $this->getApiMethod();

        $client = Mage::getModel('payin7payments/remote_client');

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