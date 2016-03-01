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

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$connection = $installer->getConnection();
$order_collection_resource = Mage::getResourceModel('sales/order_collection');

// payin7_order_sent
$installer->getConnection()->addColumn($order_collection_resource->getTable('order'), 'payin7_order_sent', 'TINYINT(4) NOT NULL default 0');


// assign default order statuses
$statuses = Mage::getModel('sales/order_status')->getCollection()->addFieldToFilter('status', 'payin7_pending');

/** @noinspection PhpUndefinedMethodInspection */
if (0 == $statuses->count()) {
// custom statuses
    $connection = $installer->getConnection()->insertArray(
        $installer->getTable('sales/order_status'),
        array('status', 'label'),
        array(
            array('payin7_pending', 'Payin7 Pending Order'),
            array('payin7_accepted', 'Payin7 Accepted Order'),
            array('payin7_rejected', 'Payin7 Rejected Order')
        )
    );

    /** @noinspection PhpUndefinedMethodInspection */
    Mage::getModel('sales/order_status')
        ->load('payin7_pending')
        ->assignState(Mage_Sales_Model_Order::STATE_HOLDED, '0');
    /** @noinspection PhpUndefinedMethodInspection */
    Mage::getModel('sales/order_status')
        ->load('payin7_accepted')
        ->assignState(Mage_Sales_Model_Order::STATE_PROCESSING, '0');
    /** @noinspection PhpUndefinedMethodInspection */
    Mage::getModel('sales/order_status')
        ->load('payin7_rejected')
        ->assignState(Mage_Sales_Model_Order::STATE_CANCELED, '0');
}

// payin7_data
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payin7_data')};
CREATE TABLE {$this->getTable('payin7_data')} (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `data_key` varchar(150) NOT NULL DEFAULT '',
  `last_updated` datetime NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`data_id`),
  UNIQUE KEY `config_key` (`data_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// payin7_order_history
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('payin7_order_history')};
CREATE TABLE {$this->getTable('payin7_order_history')} (
  `history_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `order_unique_id` varchar(100) NOT NULL,
  `created_on` datetime NOT NULL,
  `change_type` enum('order_state_changed','order_updated','doc_updated') NOT NULL DEFAULT 'order_state_changed',
  `data` text,
  PRIMARY KEY (`history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// payin7_order_identifier
$installer->getConnection()->addColumn($order_collection_resource->getTable('order'), 'payin7_order_identifier', 'VARCHAR(100)');

// payin7_order_identifier
$installer->getConnection()->addColumn($order_collection_resource->getTable('order'), 'payin7_order_accepted', 'TINYINT(4) NOT NULL DEFAULT \'0\'');

// payin7_sandbox_order
$installer->getConnection()->addColumn($order_collection_resource->getTable('order'), 'payin7_sandbox_order', 'TINYINT(4) NOT NULL DEFAULT \'0\'');

// IDX_SALES_FLAT_ORDER_PAYIN7_ORDER_IDENTIFIER
//$installer->run("ALTER TABLE `" . $order_collection_resource->getTable('order') . "` ADD INDEX IF NOT EXISTS `IDX_SALES_FLAT_ORDER_PAYIN7_ORDER_IDENTIFIER` (`payin7_order_identifier`)");
$installer->getConnection()->addIndex($order_collection_resource->getTable('order'), 'IDX_SALES_FLAT_ORDER_PAYIN7_ORDER_IDENTIFIER', 'payin7_order_identifier');

// payin7_order_sent
$installer->getConnection()->addColumn($order_collection_resource->getTable('order'), 'payin7_access_token', 'VARCHAR(255)');

$installer->endSetup();

