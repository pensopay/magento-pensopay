<?php

class PensoPay_Payment_Block_Mpo_Link extends Mage_Core_Block_Template
{
    /**
     * @return bool
     */
    public function isMobilePayCheckoutEnabled()
    {
        return Mage::getStoreConfigFlag('payment/pensopay_mobilepay/checkout_enabled');
    }

    /**
     * Get MobilePay Checkout URL
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout/mobilepay', array('_secure'=>true));
    }
}