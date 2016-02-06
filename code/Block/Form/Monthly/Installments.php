<?php

class Payin7_Payments_Block_Form_Monthly_Installments extends Payin7_Payments_Block_Form_Payin7
{
    public function getCheckoutImageUrl()
    {
        $url = $this->getRemoteApiMethodLogoUrl();
        $url = $url ? $url : $this->getSkinUrl('images/payin7/es/payin7_days_logo.png');
        return $url;
    }
}
