<?php

class Payin7_Payments_Model_MonthlyInstallmentsPaymentMethod extends Payin7_Payments_Model_Payin7daysPaymentMethod
{
    const METHOD_CODE = 'payin7paymentinstallments';
    const REMOTE_CODE = 'installments';

    protected $_code = self::METHOD_CODE;

    protected $_formBlockType = 'payin7payments/form_monthly_installments';

    protected $_livePaymentRequired = true;

    public function getRemoteApiPaymentMethodCode()
    {
        return self::REMOTE_CODE;
    }
}