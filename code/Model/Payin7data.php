<?php

class Payin7_Payments_Model_Payin7data extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('payin7payments/payin7data');
    }

    public function loadByDataKey($data_key)
    {
        $matches = $this->getResourceCollection()
            ->addFieldToFilter('data_key', $data_key);

        foreach ($matches as $match) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->load($match->getId());
        }

        return $this->setData('data_key', $data_key);
    }

    public function updatePlatformData($key, array $data = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->loadByDataKey($key)
            ->setData('data', serialize($data))
            ->setData('last_updated', time())
            ->save();
    }

    public function getPlatformData($key)
    {
        $collection = Mage::getModel('payin7payments/payin7data')->getCollection()
            ->addFieldToFilter('data_key', $key);

        if (!count($collection)) {
            return null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $rd = $collection->getFirstItem();

        /** @noinspection PhpUndefinedMethodInspection */
        $last_updated = strtotime($rd->getLastUpdated());

        /** @noinspection PhpUndefinedMethodInspection */
        $data = $rd->getData('data');
        $data = @unserialize($data);

        return array(
            'last_updated' => $last_updated,
            'data' => $data
        );
    }
}