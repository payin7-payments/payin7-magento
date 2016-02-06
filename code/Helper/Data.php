<?php

class Payin7_Payments_Helper_Data extends Mage_Checkout_Helper_Data
{
    const DEFAULT_HOSTNAME = 'api.payin7.com';
    const DEFAULT_API_VERSION = 'v1';

    const MODULE_NAME = 'Payin7_Payments';

    const JS_BOOTSTRAP_VER = 1;

    const SERVICE_API = 1;
    const SERVICE_BACKEND = 2;
    const SERVICE_FRONTEND = 3;
    const SERVICE_RES = 4;

    private $_service_subdomains = array(
        self::SERVICE_API => 'api',
        self::SERVICE_BACKEND => 'clients',
        self::SERVICE_FRONTEND => 'stores',
        self::SERVICE_RES => 'res'
    );

    private $_payment_methods_remote_mappings = array(
        Payin7_Payments_Model_Payin7daysPaymentMethod::METHOD_CODE => Payin7_Payments_Model_Payin7daysPaymentMethod::REMOTE_CODE,
        Payin7_Payments_Model_MonthlyInstallmentsPaymentMethod::METHOD_CODE => Payin7_Payments_Model_MonthlyInstallmentsPaymentMethod::REMOTE_CODE,
    );

    /**
     * @var Payin7_Payments_Helper_Log
     */
    private $_logger;

    public function getLogger()
    {
        if (!$this->_logger) {
            $this->_logger = Mage::helper('payin7payments/log');
        }
        return $this->_logger;
    }

    public function getPaymentMethodRemoteCode($payin7_method_code)
    {
        return (isset($this->_payment_methods_remote_mappings[$payin7_method_code]) ?
            $this->_payment_methods_remote_mappings[$payin7_method_code] : null);
    }

    public function getIsPayin7PaymentMethod($method_code)
    {
        return ($method_code == Payin7_Payments_Model_MonthlyInstallmentsPaymentMethod::METHOD_CODE ||
            $method_code == Payin7_Payments_Model_Payin7daysPaymentMethod::METHOD_CODE
        );
    }

    public function getJsConfig()
    {
        return array(
            'payin7ApiUrl' => $this->getJsonApiUrl(),
            'localApiUrl' => Mage::getUrl('payin7/api', array('_secure' => true)),
            'debug' => $this->getDebugModeEnabled(),
            'sandbox' => $this->getApiSandboxEnabled(),
            'orders' => array(
                'frameTitle' => $this->__('Complete Operation')
            )
        );
    }

    public function getApiServerHostname()
    {
        $ret = Mage::getStoreConfig('payin7payments/integration/server_hostname');
        $ret = $ret ? $ret : self::DEFAULT_HOSTNAME;
        return $ret;
    }

    public function getApiServerPort()
    {
        return Mage::getStoreConfig('payin7payments/integration/server_port');
    }

    public function getApiVersion()
    {
        $ret = Mage::getStoreConfig('payin7payments/integration/api_version');
        $ret = $ret ? $ret : self::DEFAULT_API_VERSION;
        return $ret;
    }

    public function getDebugModeEnabled()
    {
        return Mage::getStoreConfig('payin7payments/developer/debugging_mode');
    }

    public function getApiSandboxEnabled()
    {
        return Mage::getStoreConfig('payin7payments/integration/sandbox_enabled');
    }

    public function getApiUseSecureConnection()
    {
        return Mage::getStoreConfig('payin7payments/integration/use_secure_connection');
    }

    public function getApiIntegrationIdentifier()
    {
        return Mage::getStoreConfig('payin7payments/integration/integration_identifier');
    }

    public function getApiKey()
    {
        return ($this->getApiSandboxEnabled() ? $this->getApiSandboxKey() : $this->getApiProductionKey());
    }

    public function getApiSigningKey()
    {
        return Mage::getStoreConfig('payin7payments/integration/signing_key');
    }

    public function getApiSandboxKey()
    {
        return Mage::getStoreConfig('payin7payments/integration/sandbox_api_key');
    }

    public function getApiProductionKey()
    {
        return Mage::getStoreConfig('payin7payments/integration/production_api_key');
    }

    public function getApiIntegrationKey()
    {
        if ($this->getApiSandboxEnabled()) {
            return $this->getApiSandboxKey();
        } else {
            return $this->getApiProductionKey();
        }
    }

    public function getVersion()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return (string)Mage::getConfig()->getModuleConfig(self::MODULE_NAME)->version;
    }

    public function getBackendViewOrderUrl(Mage_Sales_Model_Order $order, $secure = null)
    {
        $identifier = $order->getData('payin7_order_identifier');

        if (!$identifier) {
            return null;
        }

        $sandbox_order = (bool)$order->getData('payin7_sandbox_order');

        return $this->getServiceUrl(self::SERVICE_BACKEND, '/orders/view/' . $identifier, null, $secure, false, $sandbox_order);
    }

    public function getFrontendAccessKey()
    {
        // TODO: Check with Prestashop
        return sha1(
            $this->getApiIntegrationIdentifier() .
            $this->getApiKey()
        );
    }

    public function getFrontendOrderCompleteUrl(Mage_Sales_Model_Order $order, $secure = null)
    {
        $identifier = $order->getData('payin7_order_identifier');
        $access_token = $order->getData('payin7_access_token');

        if (!$identifier || !$access_token) {
            return null;
        }

        $sandbox_order = (bool)$order->getData('payin7_sandbox_order');

        return $this->getServiceUrl(self::SERVICE_FRONTEND, '/orders/complete/' . urlencode($identifier),
            array(
                'key' => $this->getFrontendAccessKey(),
                'ac' => $access_token
            ), $secure, false, $sandbox_order);
    }

    public function getBackendUrl($path = null, array $query_params = null, $secure = null)
    {
        return $this->getServiceUrl(self::SERVICE_BACKEND, $path, $query_params, $secure);
    }

    public function getJsApiUrl($path, array $query_params = null)
    {
        return $this->getServiceUrl(self::SERVICE_RES, $path, $query_params, null, true);
    }

    public function getJsonApiUrl($path = null, array $query_params = null, $secure = null)
    {
        return $this->getServiceUrl(self::SERVICE_API, $path, $query_params, $secure);
    }

    public function getServiceUrl($service_type, $path = null, array $query_params = null, $secure = null, $noproto = false, $sandbox = null)
    {
        $service_subdomain = isset($this->_service_subdomains[$service_type]) ? $this->_service_subdomains[$service_type] : null;

        if (!$service_subdomain) {
            return null;
        }

        $server_port = $this->getApiServerPort();
        $api_ver = ($service_type == self::SERVICE_API ? $this->getApiVersion() : null);
        $sandbox_enabled = isset($sandbox) ? $sandbox : $this->getApiSandboxEnabled();
        $hostname = $this->getApiServerHostname();

        $secure = !isset($secure) ? $this->getApiUseSecureConnection() : $secure;

        $params = (array)$query_params;
        $params['locale'] = isset($params['locale']) ? $params['locale'] :
            Mage::app()->getLocale()->getLocaleCode();

        $url = ($noproto ? '//' : ($secure ? 'https://' : 'http://')) .
            ($sandbox_enabled && $service_type != self::SERVICE_API ? 'sandbox.' : null) .
            ($service_subdomain ? $service_subdomain . '.' : null) .
            $hostname . ($server_port ? ':' . $server_port : null) .
            ($api_ver ? '/' . $api_ver : null);

        if (!$path) {
            $ret = $url . ($params ? '?' . http_build_query($params) : null);
            return $ret;
        }

        $url .= $path . ($params ? '?' . http_build_query($params) : null);
        return $url;
    }

    public function getLastOrder()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');

        /** @noinspection PhpUndefinedMethodInspection */
        $order->loadByIncrementId($session->getLastRealOrderId());

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function _getMySQLVersion()
    {
        /** @var Mage_Core_Model_Resource $res */
        $res = Mage::getSingleton('core/resource');

        $sql = "SELECT version() AS version";
        $result = $res->getConnection('core_read')->query($sql);
        $row = $result->fetch();
        return $row['version'];
    }

    public function getSysinfo()
    {
        /** @var Mage_Core_Helper_Http $core_http */
        $core_http = Mage::helper('core/http');

        /** @var Mage_Core_Model_Config_Element $config */
        $config = (array)Mage::getConfig()->getResourceConnectionConfig("default_setup");
        $db_type = (isset($config['type']) ? $config['type'] : null);
        $db_ver = null;

        if (strstr($db_type, 'mysql')) {
            $db_ver = $this->_getMySQLVersion();
        }

        $sysinfo = array_filter(array(
            'platform_ident' => 'magento',
            'platform_version' => Mage::getVersion(),
            'plugin_version' => $this->getVersion(),
            'preprocessor_version' => phpversion(),
            'preprocessor_sapi' => php_sapi_name() . ' (' . implode(', ', (array)get_loaded_extensions()) . ')',
            'os_ident' => php_uname(),
            'os_version' => php_uname('v'),
            'db_ident' => $db_type,
            'db_version' => $db_ver,
            'client_user_agent' => $core_http->getHttpUserAgent(),
            'client_env' => json_encode($_SERVER)
        ));

        return $sysinfo;
    }

    public function getCheckoutRedirectUrl()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');

        // redirect to cart / checkout
        $is_authenticated = $session->isLoggedIn();

        $redirect_url = $is_authenticated ? 'checkout/onepage' : 'checkout/cart';
        return $redirect_url;
    }

    public function clearCart()
    {
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getSingleton('checkout/cart');

        $items = $cart->getItems();

        foreach ($items as $item) {
            /** @noinspection PhpUndefinedMethodInspection */
            $cart->removeItem($item->getItemId())->save();
        }

        $this->getLogger()->logInfo('Cart cleared');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function restoreCardWithOrder($order)
    {
        $this->clearCart();

        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getSingleton('checkout/cart');

        $items = $order->getItemsCollection();

        if ($items) {
            foreach ($items as $item) {
                try {
                    $cart->addOrderItem($item);
                } catch (Exception $e) {
                    $this->getLogger()->logError('Could not restore item in order: ' . $e->getMessage());
                }
            }
        }

        $cart->save();

        $this->getLogger()->logInfo('Restored order ' . $order->getId() . ' to cart');
    }
}
