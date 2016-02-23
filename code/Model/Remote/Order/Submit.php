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

class Payin7_Payments_Model_Remote_Order_Submit extends Mage_Core_Model_Abstract
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * @var string
     */
    protected $_device_type;

    /**
     * @var string
     */
    protected $_source;

    /**
     * @var array
     */
    protected $_sysinfo;

    /**
     * @var string
     */
    protected $_ordered_by_ip_address;

    /**
     * @var string
     */
    protected $_language_code;

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
    }

    public function setDeviceType($device_type = null)
    {
        $this->_device_type = $device_type;
    }

    public function setLanguageCode($lang_code = null)
    {
        $this->_language_code = $lang_code;
    }

    public function setSource($source = null)
    {
        $this->_source = $source;
    }

    public function setSysinfo(array $sysinfo = null)
    {
        $this->_sysinfo = $sysinfo;
    }

    public function setOrderedByIpAddress($ip_address = null)
    {
        $this->_ordered_by_ip_address = $ip_address;
    }

    /**
     * @return array
     */
    protected function _prepareOrderData()
    {
        $data = array_filter(array(
            'currency_code' => $this->_order->getOrderCurrencyCode(),
            'shipping_method_code' => $this->_order->getShippingMethod(true)->getData('carrier_code'),
            'shipping_method_title' => $this->_order->getShippingDescription(),
            'created_on' => $this->_order->getCreatedAt(),
            'updated_on' => $this->_order->getUpdatedAt(),
            'state' => $this->_order->getState(),
            'status' => $this->_order->getStatus(),
            'is_gift' => ($this->_order->getGiftMessageId() != null),
            'ref_quote_id' => $this->_order->getQuoteId(),
            'order_subtotal_with_tax' => $this->_order->getSubtotalInclTax(),
            'order_subtotal' => $this->_order->getSubtotal(),
            'order_tax' => $this->_order->getTaxAmount(),
            'order_hidden_tax' => $this->_order->getHiddenTaxAmount(),
            'order_shipping_with_tax' => $this->_order->getShippingInclTax(),
            'order_shipping' => $this->_order->getShippingAmount(),
            'order_discount' => $this->_order->getDiscountAmount(),
            'order_shipping_discount' => $this->_order->getShippingDiscountAmount(),
            'order_total' => $this->_order->getGrandTotal(),
            'order_total_items' => $this->_order->getTotalItemCount()
        ));
        return $data;
    }

    /**
     * @return array
     */
    protected function _prepareOrderAddresses()
    {
        /** @var Mage_Sales_Model_Order_Address[] $addresses */
        $addresses = $this->_order->getAddressesCollection();
        $data = array();

        if ($addresses) {
            foreach ($addresses as $address) {
                $address_data = array_filter(array(
                    'store_address_id' => $address->getCustomerAddressId(),
                    'type' => $address->getAddressType(),
                    'title' => '',
                    'prefix' => $address->getPrefix(),
                    'suffix' => $address->getSuffix(),
                    'first_name' => $address->getFirstname(),
                    'middle_name' => $address->getMiddlename(),
                    'last_name' => $address->getLastname(),
                    'company_name' => $address->getCompany(),
                    'street_address_1' => $address->getStreet1(),
                    'street_address_2' => $address->getStreet2(),
                    'street_address_3' => $address->getStreet3(),
                    'street_address_4' => $address->getStreet4(),
                    'city' => $address->getCity(),
                    'country_code' => $address->getCountry(),
                    'state' => '',
                    'region' => $address->getRegion(),
                    'region_code' => $address->getRegionCode(),
                    'zip_code' => $address->getPostcode(),
                    'telephone1' => $address->getTelephone(),
                    'telephone2' => '',
                    'telephone3' => '',
                    'fax' => $address->getFax(),
                    'vat_number' => $address->getData('vat_id')
                ));
                $data[] = $address_data;
                unset($address);
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    protected function _prepareOrderItems()
    {
        /** @var Mage_Sales_Model_Order_Item[] $items */
        $items = $this->_order->getAllVisibleItems();
        $data = array();

        /** @var Mage_Catalog_Model_Product_Media_Config $productMediaConfig */
        $productMediaConfig = Mage::getModel('catalog/product_media_config');

        if ($items) {
            foreach ($items as $item) {
                /** @var Mage_Catalog_Model_Product $product */
                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                $smallImageUrl = $productMediaConfig->getMediaUrl($product->getData('small_image'));

                $item_data = array_filter(array(
                    'item_id' => $item->getId(),
                    'product_id' => $item->getProductId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'url' => $product->getProductUrl(),
                    'image_url' => $smallImageUrl,
                    'details' => $product->getData('short_description'),
                    'details_full' => $product->getData('description'),
                    'is_virtual' => $item->getIsVirtual(),
                    'quantity' => $item->getQtyOrdered(),
                    'quantity_is_decimal' => $item->getIsQtyDecimal(),
                    'item_subtotal_with_tax' => $item->getPriceInclTax(),
                    'item_subtotal' => $item->getPrice(),
                    'item_tax' => $item->getTaxAmount(),
                    'item_hidden_tax' => $item->getHiddenTaxAmount(),
                    'item_tax_before_discount' => $item->getTaxBeforeDiscount(),
                    'item_shipping_with_tax' => null,
                    'item_shipping' => null,
                    'item_discount' => $item->getDiscountAmount(),
                    'item_discount_with_tax' => null,
                    'item_total' => $item->getRowTotal(),
                    'item_total_with_tax' => $item->getRowTotalInclTax(),
                ));
                $data[] = $item_data;
                unset($item);
            }
        }
        return $data;
    }

    protected function getCustomerIsConfirmedStatus(Mage_Customer_Model_Customer $customer)
    {
        // TODO: Verify this
        if (!$customer->getData('confirmation')) {
            return true;
        }

        if ($customer->isConfirmationRequired()) {
            return false;
        }

        return null;
    }

    /**
     * @return array
     */
    protected function _prepareCustomerData()
    {
        $customer_id = null;
        $customer = null;
        $customer_log = null;
        $billing_address = $this->_order->getBillingAddress();
        $customer_verified = false;
        $customer_orders_count = 0;
        $gender = $this->_order->getCustomerGender();

        if (!$this->_order->getCustomerIsGuest()) {
            $customer_id = $this->_order->getCustomerId();

            if ($customer_id) {
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel("customer/customer");
                $customer->load($customer_id);

                /** @var Mage_Log_Model_Customer $customer_log */
                $customer_log = Mage::getModel('log/customer')->load($customer_id);
            }

            $customer_verified = $this->getCustomerIsConfirmedStatus($customer);

            $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customer_id);
            /** @noinspection PhpUndefinedMethodInspection */
            $customer_orders_count = $orders->count();
        }

        $data = array_filter(array(
            'customer_id' => $customer_id,
            'customer_is_guest' => $this->_order->getCustomerIsGuest(),
            'verified' => $customer_verified,
            'language_code' => $this->_language_code,
            'last_login_on' => ($customer_log ? $customer_log->getLoginAt() : null),
            'created_on' => ($customer ? $customer->getData('created_at') : null),
            'updated_on' => ($customer ? $customer->getData('updated_at') : null),
            'birthdate' => $this->_order->getCustomerDob(),
            'email' => $this->_order->getCustomerEmail(),
            'title' => '',
            'prefix' => $this->_order->getCustomerPrefix(),
            'suffix' => $this->_order->getCustomerSuffix(),
            'first_name' => $this->_order->getCustomerFirstname(),
            'middle_name' => $this->_order->getCustomerMiddlename(),
            'last_name' => $this->_order->getCustomerLastname(),
            'company_name' => ($billing_address ? $billing_address->getCompany() : null),
            'gender' => ($gender == 1 ? 'male' : ($gender == 2 ? 'female' : null)),
            'telephone1' => ($billing_address ? $billing_address->getTelephone() : null),
            'telephone2' => '',
            'telephone3' => '',
            'fax' => ($billing_address ? $billing_address->getFax() : null),
            'vat_number' => $this->_order->getCustomerTaxvat(),
            'reg_ip_address' => '',
            'customer_orders_count' => $customer_orders_count
        ));
        return $data;
    }

    public function getPayin7OrderIdentifier()
    {
        return ($this->_order ? $this->_order->getData('payin7_order_identifier') : null);
    }

    public function getPayin7IsOrderSent()
    {
        return ($this->_order ? $this->_order->getData('payin7_order_sent') : null);
    }

    public function submitOrder($force = false)
    {
        if (!$this->_order) {
            return false;
        }

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');
        $logger = $phelper->getLogger();

        $logger->logInfo('Will submit payment to Payin7, ID: ' . $this->_order->getId());

        $already_sent = $this->getPayin7IsOrderSent();

        // check if already submitted
        if ($already_sent && !$force) {
            $logger->logWarn('Payment already submitted - not doing anything');
            return $this->getPayin7OrderIdentifier();
        }

        $payment = $this->_order->getPayment();
        $payment_method = $payment->getMethod();
        $payment_method_remote_code = $phelper->getPaymentMethodRemoteCode($payment_method);

        if (!$payment_method_remote_code) {
            return false;
        }

        // begin submitting

        $data = array(
            'order_id' => $this->_order->getId(),
            'payment_method' => $payment_method_remote_code,
            'device_type' => $this->_device_type,
            'ordered_by_ip_address' => $this->_ordered_by_ip_address,
            'source' => $this->_source,
            'order' => json_encode($this->_prepareOrderData()),
            'items' => json_encode($this->_prepareOrderItems()),
            'addresses' => json_encode($this->_prepareOrderAddresses()),
            'customer' => json_encode($this->_prepareCustomerData()),
            'sysinfo' => json_encode($this->_sysinfo)
        );

        $logger->logInfo(($already_sent ? 'RE-' : null) . 'Submitting order to Payin7: ' . print_r(array(
                'order_id' => $this->_order->getId(),
                'payment_method' => $payment_method_remote_code
            ), true));

        /** @var Payin7_Payments_Model_Remote_Client $client */
        $client = Mage::getModel('payin7payments/remote_client');

        $response = $client->postOrder($data);

        if (!$response['payin7_order_id']) {
            return false;
        }

        // update the order
        $this->_order->setData('payin7_order_identifier', $response['payin7_order_id']);
        $this->_order->setData('payin7_order_sent', true);
        $this->_order->setData('payin7_order_accepted', (bool)$response['is_accepted']);
        $this->_order->setData('payin7_access_token', $response['access_token']);

        $logger->logInfo('Payment ' . ($already_sent ? 're' : null) . 'submitted to Payin7, ID: ' .
            $this->_order->getId() . ', data: ' . print_r($response, true));

        if (!$already_sent) {
            $message = Mage::helper('payin7payments')->__('Order submitted to Payin7');
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $message);
        }

        return $this->getPayin7OrderIdentifier();
    }
}