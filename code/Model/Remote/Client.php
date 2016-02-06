<?php

class Payin7_Payments_Model_Remote_Client
{
    const API_CLIENT_CONNECT_TIMEOUT = 10; // in seconds
    const API_CLIENT_TIMEOUT = 60; // in seconds

    const ERR_SERVER_CODE = 20;
    const ERR_CLIENT_CODE = 30;
    const ERR_SYSTEM_CODE = 40;

    /** @var Payin7Payments\Payin7PaymentsClient */
    protected $_api_client;

    /** @var Payin7_Payments_Helper_Data */
    protected $_pphelper;

    /** @var Payin7_Payments_Helper_Log */
    protected $_logger;

    public function __construct()
    {
        /** @var Payin7_Payments_Helper_Data $phelper */
        $this->_pphelper = Mage::helper('payin7payments');
        $this->_logger = $this->_pphelper->getLogger();

        $payin7_php_dir = Mage::getBaseDir('lib') . DS . 'Payin7' . DS . 'payin7-php';
        /** @noinspection PhpIncludeInspection */
        require_once($payin7_php_dir . DS . 'vendor' . DS . 'autoload.php');

        $this->_configureDefaults();
    }

    public function getApi()
    {
        return $this->_api_client;
    }

    protected function _configureDefaults()
    {
        $client = Payin7Payments\Payin7PaymentsClient::getInstance(array(
            'integration_id' => $this->_pphelper->getApiIntegrationIdentifier(),
            'integration_key' => $this->_pphelper->getApiIntegrationKey(),

            'timeout' => self::API_CLIENT_TIMEOUT,
            'connect_timeout' => self::API_CLIENT_CONNECT_TIMEOUT
        ));

        // disable SSL checks under sandbox
        if ($this->_pphelper->getApiSandboxEnabled()) {
            $client->setSslVerification(false, false);
        }

        $client->setBaseUrl($this->_pphelper->getJsonApiUrl());
        $this->_api_client = $client;
    }

    protected function _logResponseException(Exception $e)
    {
        if ($e instanceof \Payin7Payments\Exception\Payin7APIException) {
            $response = $e->getResponse();
            $code = $response->getStatusCode();
            $body = $response->getBody();

            $this->_logger->logError("[API SERVER ERROR] Status Code: {$code} | Body: {$body}");
        } else {
            $this->_logger->logError("[API SERVER ERROR] " . $e->getMessage() . ', Code: ' . $e->getCode());
        }
    }

    protected function _callApi($api_method, array $data = null)
    {
        try {
            return $this->_api_client->$api_method($data);
        } catch (Exception $e) {
            $this->_logResponseException($e);
            throw $e;
        }
    }

    public function getPlatformStatus()
    {
        return $this->_callApi(__FUNCTION__);
    }

    public function getPlatformConfig()
    {
        return $this->_callApi(__FUNCTION__);
    }

    public function postOrderHistory(array $data)
    {
        return $this->_callApi(__FUNCTION__, $data);
    }

    public function postOrder(array $data)
    {
        return $this->_callApi(__FUNCTION__, $data);
    }
}