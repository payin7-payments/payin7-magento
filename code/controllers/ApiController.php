<?php

class Payin7_Payments_ApiController extends Mage_Core_Controller_Front_Action
{
    const RESP_REDIRECT_URL_DATA_KEY = 'redirect_url';

    const RESP_SYSTEM_ERR = 10;
    const RESP_REQUEST_ERR = 20;
    const RESP_ACCESS_DENIED = 30;
    const RESP_ACCESS_DENIED_NO_CUST_MATCH = 31;
    const RESP_INVALID_ORDER_ERR = 50;
    const RESP_ORDER_SUBMIT_ERR = 60;

    /** @var Payin7_Payments_Helper_Data */
    protected $_phelper;

    protected function _getCustomerId()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');
        return ($session->isLoggedIn() ? $session->getCustomer()->getId() : null);
    }

    /**
     * This method is used only for incomplete orders of authenticated users
     */
    public function markcompleteAction()
    {
        $is_admin_area = Mage::app()->getStore()->isAdmin();

        // check user
        $customer_id = $this->_getCustomerId();

        if (!$customer_id) {
            $this->_sendErrorResponse($this->__('Access Denied'), self::RESP_ACCESS_DENIED);
        }

        $order_id = $this->getRequest()->getParam('order_id');

        if (!$order_id) {
            $this->_sendErrorResponse($this->__('Invalid params'), self::RESP_REQUEST_ERR);
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('payin7_order_identifier', $order_id)
            ->getFirstItem();

        if (!$order) {
            $this->_sendErrorResponse($this->__('Invalid Order'), self::RESP_INVALID_ORDER_ERR);
        }

        // validate the owner, allow to admin area at all times
        if ($customer_id != $order->getCustomerId() && !$is_admin_area) {
            $this->_sendErrorResponse($this->__('Access Denied'), self::RESP_ACCESS_DENIED_NO_CUST_MATCH);
        }

        $updated = false;

        if (!$order->getData('payin7_order_accepted')) {
            // just update it locally as 'customer sent'
            // the background notifications will change the actual order state when necessarry
            $order->setData('payin7_order_accepted', true);
            $order->save();
            $updated = true;
        }

        $this->_sendDataResponse(array(
            'updated' => $updated
        ));
    }

    public function submitorderAction()
    {
        $is_admin_area = Mage::app()->getStore()->isAdmin();

        // check user
        $customer_id = $this->_getCustomerId();

        if (!$customer_id) {
            $this->_sendErrorResponse($this->__('Access Denied'), self::RESP_ACCESS_DENIED);
        }

        $order_id = $this->getRequest()->getParam('order_id');

        if (!$order_id) {
            $this->_sendErrorResponse($this->__('Invalid params'), self::RESP_REQUEST_ERR);
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('payin7_order_identifier', $order_id)
            ->getFirstItem();

        if (!$order) {
            $this->_sendErrorResponse($this->__('Invalid Order'), self::RESP_INVALID_ORDER_ERR);
        }

        // validate the owner, allow to admin area at all times
        if ($customer_id != $order->getCustomerId() && !$is_admin_area) {
            $this->_sendErrorResponse($this->__('Access Denied'), self::RESP_ACCESS_DENIED_NO_CUST_MATCH);
        }

        // not submitted - submit it

        /** @var Mage_Core_Helper_Http $core_http */
        $core_http = Mage::helper('core/http');

        $source = ($is_admin_area ? 'backend' : 'frontend');
        $ordered_by_ip_address = $core_http->getRemoteAddr();
        $locale = Mage::app()->getLocale()->getLocaleCode();

        /** @var Payin7_Payments_Model_Remote_Order_Submit $order_submit */
        $order_submit = Mage::getModel('payin7payments/remote_order_submit');
        $order_submit->setSysinfo($this->_phelper->getSysinfo());
        $order_submit->setOrderedByIpAddress($ordered_by_ip_address);
        $order_submit->setSource($source);
        $order_submit->setOrder($order);
        $order_submit->setLanguageCode($locale);

        try {
            $submit_status = $order_submit->submitOrder(true);

            if (!$submit_status) {
                throw new Exception(Mage::helper('payin7payments')->__('Operation could not be completed'));
            }

            // save any order changes introduced by the submitter
            $order->save();

        } catch (\Payin7Payments\Exception\ClientErrorResponseException $e) {
            Mage::throwException($e->getFullServerErrorMessage());
        } catch (Exception $e) {
            $this->_phelper->getLogger()->logError('Submit order failure: ' . $e);
            $this->_sendErrorResponse($this->__('Operation could not be completed'), self::RESP_ORDER_SUBMIT_ERR);
        }

        $is_secure = Mage::app()->getStore()->isCurrentlySecure();
        $is_secure = $is_secure ? true : null;

        $order_url = $this->_phelper->getFrontendOrderCompleteUrl($order, $is_secure);

        $this->_sendDataResponse(array(
            'submitted' => true,
            'orderUrl' => $order_url
        ));
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->_validateApiActionAndStop();

        $this->_phelper = Mage::helper('payin7payments');
    }

    private function _validateApiActionAndStop()
    {
        $request = $this->getRequest();

        /** @var Payin7_Payments_Helper_Data $phelper */
        $phelper = Mage::helper('payin7payments');

        if (!$phelper->getDebugModeEnabled()) {
            if (!$request->isPost() || !$request->isXmlHttpRequest()) {
                $this->_sendErrorResponse($this->__('Invalid Request'));
            }
        }
    }

    private function _sendResponse($success, array $err = null, array $response_data = null, array $extra_data = null)
    {
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/json');

        $data = array_filter(array_merge(array(
            'success' => $success,
            'error' => $err,
            'response' => $response_data
        ), (array)$extra_data));

        if ($data) {
            $response->setBody(json_encode($data));
        }

        $response->sendResponse();
        exit(0);
    }

    private function _sendDataResponse(array $data = null)
    {
        $this->_sendResponse(true, null, $data);
    }

    private function _sendErrorResponse($message, $code = 0, array $extra_data = null)
    {
        $this->_sendResponse(false,
            array('message' => $message, 'code' => $code),
            null,
            $extra_data
        );
    }
}
