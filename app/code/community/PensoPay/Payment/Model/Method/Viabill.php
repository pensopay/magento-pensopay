<?php

class PensoPay_Payment_Model_Method_Viabill extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_viabill';
    protected $_formBlockType = 'pensopay/form_viabill';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    public function getPaymentMethods()
    {
        return 'viabill';
    }
}