<?php

class PensoPay_Payment_Block_Mpo_Link extends Mage_Core_Block_Template
{
    /**
     * @return bool
     */
    public function isMobilePayCheckoutEnabled()
    {
    	return false;
        return Mage::getStoreConfigFlag('payment/pensopay_mobilepay_checkout/active');
    }

    /**
     * Get MobilePay Checkout URL
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('payment/checkout/mobilepay', array('_secure'=>true));
    }
}