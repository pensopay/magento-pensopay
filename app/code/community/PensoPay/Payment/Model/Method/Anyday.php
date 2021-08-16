<?php

class PensoPay_Payment_Model_Method_Anyday extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_anyday';
    protected $_formBlockType = 'pensopay/form_anyday';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    public function getPaymentMethods()
    {
        return 'anyday-split';
    }

    public function canUseForCurrency($currencyCode)
    {
        return $currencyCode === 'DKK'; //Anyday-split currently only has DKK available
    }
}