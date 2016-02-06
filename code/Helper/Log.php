<?php

class Payin7_Payments_Helper_Log extends Mage_Core_Helper_Abstract
{
    const MY_LOGFILE = 'payin7payments.log';

    public function logWarn($message = null)
    {
        Mage::log(
            $message,
            Zend_Log::WARN,
            self::MY_LOGFILE
        );
    }

    public function logError($message = null)
    {
        Mage::log(
            $message,
            Zend_Log::ERR,
            self::MY_LOGFILE
        );
    }

    public function logDebug($message = null)
    {
        Mage::log(
            $message,
            Zend_Log::DEBUG,
            self::MY_LOGFILE
        );
    }

    public function logInfo($message = null)
    {
        Mage::log(
            $message,
            Zend_Log::INFO,
            self::MY_LOGFILE
        );
    }

}
