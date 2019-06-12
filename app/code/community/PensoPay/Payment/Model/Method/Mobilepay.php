<?php

class PensoPay_Payment_Model_Method_Mobilepay extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_mobilepay';
    protected $_formBlockType = 'pensopay/form_mobilepay';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    public function getPaymentMethods()
    {
        return 'mobilepay';
    }
}