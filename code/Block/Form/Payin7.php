<?php

class Payin7_Payments_Block_Form_Payin7 extends Mage_Payment_Block_Form
{
    protected $_template_filename = 'payin7payments/checkout/form/payin7_method.phtml';
    protected $_checkout_option_template_filename = 'payin7payments/checkout/option.phtml';

    public function _construct()
    {
        parent::_construct();

        if ($this->_template_filename) {
            $this->setTemplate($this->_template_filename);
        }
    }

    public function getMethodLabelAfterHtml()
    {
        $opt = $this->getCheckoutOptionBlock();
        $ret = ($opt ? $opt->toHtml() : null);
        return $ret;
    }

    public function hasMethodTitle()
    {
        return true;
    }

    public function getMethodTitle()
    {
        // we will display a custom title in the checkout_option_block method below
        return null;
    }

    protected function getCheckoutOptionBlock()
    {
        if (!$this->_checkout_option_template_filename) {
            return null;
        }

        $cls = Mage::getConfig()->getBlockClassName('core/template');

        /** @var Mage_Core_Block_Template $tpl */
        $tpl = new $cls();
        $tpl->setTemplate($this->_checkout_option_template_filename)
            ->setData(array(
                'checkout_image_url' => $this->getCheckoutImageUrl(),
                'method' => $this->getMethod()
            ));
        return $tpl;
    }

    protected function getRemoteApiMethodLogoUrl()
    {
        /** @var Payin7_Payments_Model_Remote_Platform_Config $remote_platform_config */
        $remote_platform_config = Mage::getModel('payin7payments/remote_platform_config');
        $remote_platform_config->loadData();

        /** @var Payin7_Payments_Model_Payin7daysPaymentMethod $method */
        $method = $this->getMethod();
        $remote_method_code = $method->getRemoteApiPaymentMethodCode();

        $payment_method_cfg = $remote_platform_config->getPaymentMethodConfig($remote_method_code);
        $image_url = isset($payment_method_cfg['logo']) ? $payment_method_cfg['logo'] : null;
        return $image_url;
    }

    public function getCheckoutImageUrl()
    {
        $url = $this->getRemoteApiMethodLogoUrl();
        $url = $url ? $url : $this->getSkinUrl('images/payin7/es/monthly_installments_logo.png');
        return $url;
    }
}
