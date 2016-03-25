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
class Payin7_Payments_Model_Payin7daysPaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    CONST METHOD_CODE = 'payin7payment7days';
    const REMOTE_CODE = 'seven_days';

    const ORDER_STATUS_PENDING = 'payin7_pending';
    const ORDER_STATUS_ACCEPTED = 'payin7_accepted';
    const ORDER_STATUS_REJECTED = 'payin7_rejected';

    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'payin7payments/form_payin7';

    protected $_isGateway = false;
    protected $_canOrder = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canCreateBillingAgreement = false;
    protected $_canReviewPayment = false;

    protected $_livePaymentRequired = false;

    /**
     * @var Payin7_Payments_Helper_Log
     */
    protected $_logger;

    public function __construct()
    {
        parent::__construct();

        $this->_logger = Mage::helper('payin7payments/log');
    }

    public function getRemoteApiPaymentMethodCode()
    {
        return self::REMOTE_CODE;
    }

    public function getLivePaymentRequired()
    {
        return $this->_livePaymentRequired;
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        // check for a reject cookie
        if ($phelper->isRejectVerificationCookieSet()) {
            return false;
        }

        if ($quote) {
            // validate the platform constraints

            /** @var Payin7_Payments_Model_Remote_Platform_Status $remote_platform_status */
            $remote_platform_status = Mage::getModel('payin7payments/remote_platform_status');
            $remote_platform_status->loadData();

            $remote_method_code = $this->getRemoteApiPaymentMethodCode();

            $platform_is_available = $remote_platform_status->getIsPlatformAvailable() &&
                $remote_platform_status->getIsPaymentMethodAvailable($remote_method_code);

            if (!$platform_is_available) {
                return false;
            }

            $quote_total = (float)$quote->getGrandTotal();

            /** @var Payin7_Payments_Model_Remote_Platform_Config $remote_platform_config */
            $remote_platform_config = Mage::getModel('payin7payments/remote_platform_config');
            $remote_platform_config->loadData();

            // check if the platform constraints are met
            $payment_method_cfg = $remote_platform_config->getPaymentMethodConfig($remote_method_code);

            $is_customer_disabled = isset($payment_method_cfg['is_disabled']) ?
                (bool)$payment_method_cfg['is_disabled'] :
                null;

            if ($is_customer_disabled) {
                return false;
            }

            $min_order_allowed_platform = isset($payment_method_cfg['minimum_amount']) ?
                (double)$payment_method_cfg['minimum_amount'] :
                null;
            $max_order_allowed_platform = isset($payment_method_cfg['maximum_amount']) ?
                (double)$payment_method_cfg['maximum_amount'] :
                null;
            $supported_countries = isset($payment_method_cfg['supported_countries']) ?
                (array)$payment_method_cfg['supported_countries'] :
                array();

            if ($min_order_allowed_platform && $quote_total < $min_order_allowed_platform) {
                $this->_logger->logWarn('Order platform min not within allowed constraints (min platform allowed: ' .
                    $min_order_allowed_platform . ', current quote: ' . $quote_total . ')');
                return false;
            }

            if ($max_order_allowed_platform && $quote_total > $max_order_allowed_platform) {
                $this->_logger->logWarn('Order platform max not within allowed constraints (max platform allowed: ' .
                    $max_order_allowed_platform . ', current quote: ' . $quote_total . ')');
                return false;
            }

            if ($supported_countries) {
                $country = $quote->getBillingAddress()->getCountry();

                if (!in_array($country, $supported_countries)) {
                    $this->_logger->logWarn('Order country not supported, country: ' . $country);
                    return false;
                }
            }

            // verify the minimum / maximum allowed
            $min_order_allowed = (double)$this->getConfigData('min_order_total');
            $max_order_allowed = (double)$this->getConfigData('max_order_total');

            if ($min_order_allowed && $quote_total < $min_order_allowed) {
                $this->_logger->logWarn('Order min not within allowed constraints (min allowed: ' .
                    $min_order_allowed . ', current quote: ' . $quote_total . ')');
                return false;
            }

            if ($max_order_allowed && $quote_total > $max_order_allowed) {
                $this->_logger->logWarn('Order max not within allowed constraints (max allowed: ' .
                    $max_order_allowed . ', current quote: ' . $quote_total . ')');
                return false;
            }

            // verify the shipping methods
            if ($this->getConfigData('specific_shipping_methods') && $quote->getShippingAddress()->getShippingMethod()) {
                $specific_shipping_methods = array_filter(explode(',', $this->getConfigData('specific_shipping_methods')));

                if ($specific_shipping_methods) {
                    $current_shipping_method = explode('_', $quote->getShippingAddress()->getShippingMethod());

                    if (!$current_shipping_method || !is_array($current_shipping_method)) {
                        return false;
                    }

                    $this->_logger->logWarn('Shipping method not supported: ' . $current_shipping_method[0] . ', ' . print_r($specific_shipping_methods, true));
                    return in_array($current_shipping_method[0], $specific_shipping_methods);
                }
            }
        }

        return true;
    }

    public function canUseForCurrency($currencyCode)
    {
        /** @var Payin7_Payments_Model_Remote_Platform_Config $remote_platform_config */
        $remote_platform_config = Mage::getModel('payin7payments/remote_platform_config');
        $remote_platform_config->loadData();

        $remote_method_code = $this->getRemoteApiPaymentMethodCode();
        $payment_method_cfg = $remote_platform_config->getPaymentMethodConfig($remote_method_code);

        $supported_currencies = isset($payment_method_cfg['supported_currencies']) ?
            $payment_method_cfg['supported_currencies'] :
            array();

        return (($supported_currencies && $currencyCode && in_array($currencyCode, $supported_currencies)) || !$supported_currencies);
    }

    public function getOrderPlaceRedirectUrl()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        return Mage::getUrl('payin7/order/final', array('_secure' => $phelper->getShouldUseSecureConnection()));
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Mage_Core_Exception
     * @internal param $amount
     */
    protected function _callPostOrder($order)
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        /** @var Mage_Core_Helper_Http $core_http */
        //$core_http = Mage::helper('core/http');

        // mark as customer NOT notified as really it has not been
        $order->setCustomerNoteNotify(false);

        /** @noinspection PhpUndefinedMethodInspection */
        $order->getPayment()->setSkipOrderProcessing(true);

        $source = (Mage::app()->getStore()->isAdmin() ? 'backend' : 'frontend');
        $ordered_by_ip_address = \Payin7Payments\StringUtils::getIpAddress();

        // magento integrated method does not return the proper IP address when it has been forwarded (e.g. server is behind a proxy gw - haproxy)
        //$core_http->getRemoteAddr();

        $locale = Mage::app()->getLocale()->getLocaleCode();

        /** @var Payin7_Payments_Model_Remote_Order_Submit $order_submit */
        $order_submit = Mage::getModel('payin7payments/remote_order_submit');
        $order_submit->setSysinfo($phelper->getSysinfo());
        $order_submit->setOrderedByIpAddress($ordered_by_ip_address);
        $order_submit->setSource($source);
        $order_submit->setOrder($order);
        $order_submit->setLanguageCode($locale);

        try {
            // force submit it to update any statuses locally
            $status = $order_submit->submitOrder(true);

            if (!$status) {
                throw new Exception(Mage::helper('payin7payments')->__('Payment could not be completed. Please try again later'));
            }

        } catch (\Payin7Payments\Exception\ClientErrorResponseException $e) {
            Mage::throwException($e->getFullServerErrorMessage());
        } catch (Exception $e) {
            $this->_logger->logError('Submit order failure: ' . $e);
            Mage::throwException(Mage::helper('payin7payments')->__('Payment could not be completed. Please try again later'));
        }

        return $this;
    }

    /**
     * Order payment abstract method
     *
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @param float $amount
     * @return Payin7_Payments_Model_Payin7daysPaymentMethod
     */
    public function order(Varien_Object $payment, $amount)
    {
        parent::order($payment, $amount);

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        $order = $payment->getOrder();

        // if it's a sandbox order mark it as such
        $is_sandbox = $phelper->getApiSandboxEnabled();

        if ($is_sandbox) {
            $order->setData('payin7_sandbox_order', true);
        }

        $this->_callPostOrder($order);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        $order = $payment->getOrder();

        if ($order) {
            $this->_callPostOrder($order);
        }

        return $this;
    }
}