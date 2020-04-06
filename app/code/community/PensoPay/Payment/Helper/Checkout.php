<?php

class PensoPay_Payment_Helper_Checkout extends Mage_Core_Helper_Abstract
{
    /**
     * Restore last active quote based on checkout session
     *
     * @return bool True if quote restored successfully, false otherwise
     */
    public function restoreQuote()
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();

        if ($order && $order->getId()) {
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $this->getCheckoutSession()
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();

                return true;
            }
        }

        return false;
    }

    public function getPaymentConfig($value)
    {
        return Mage::getStoreConfig('payment/pensopay/' . $value, Mage::app()->getStore());
    }

    public function isCheckoutIframe()
    {
        $checkoutMethod = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_CHECKOUT_METHOD);
        if ($checkoutMethod === PensoPay_Payment_Model_System_Config_Source_CheckoutMethods::METHOD_IFRAME) {
            return true;
        }
        return false;
    }

    public function isCheckoutEmbedded()
    {
        $checkoutMethod = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_CHECKOUT_METHOD);
        if ($checkoutMethod === PensoPay_Payment_Model_System_Config_Source_CheckoutMethods::METHOD_EMBEDDED) {
            return true;
        }
        return false;
    }

    /**
     * Return checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }
}
