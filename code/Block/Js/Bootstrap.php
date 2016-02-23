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

class Payin7_Payments_Block_Js_Bootstrap extends Mage_Core_Block_Template
{
    /** @var Payin7_Payments_Helper_Data */
    protected $_helper;

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('payin7payments/js_bootstrap.phtml');

        $this->_helper = Mage::helper('payin7payments');
    }

    public function getJsConfig()
    {
        return $this->_helper->getJsConfig();
    }

    public function getScriptSrc()
    {
        return $this->_helper->getJsApiUrl('/' . Payin7_Payments_Helper_Data::DEFAULT_JSAPI_FNAME);
    }
}
