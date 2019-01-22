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
        $order = $this->_getCheckoutSession()->getLastRealOrder();

        if ($order && $order->getId()) {
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $this->_getCheckoutSession()
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();

                return true;
            }
        }

        return false;
    }

    /**
     * Return checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
