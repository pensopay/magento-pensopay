<?php

class PensoPay_Payment_Model_Method_Klarna extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_klarna';
    protected $_formBlockType = 'pensopay/form_klarna';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    public function getPaymentMethods()
    {
        return 'klarna';
    }
}