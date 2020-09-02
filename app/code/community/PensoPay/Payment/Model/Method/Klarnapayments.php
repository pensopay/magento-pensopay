<?php

class PensoPay_Payment_Model_Method_Klarnapayments extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_klarnapayments';
    protected $_formBlockType = 'pensopay/form_klarnapayments';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    public function getPaymentMethods()
    {
        return 'klarna-payments';
    }
}