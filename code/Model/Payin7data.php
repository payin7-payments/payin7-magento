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