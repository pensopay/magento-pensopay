<?php
class PensoPay_Payment_MobilepayController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction() {
        $params = $this->getRequest()->getParams();
        $error = false;

        if (empty($params['shipping'])) {
            $error = Mage::helper('pensopay')->__('Please specify a shipping method.');
        } else {
            $shippingData = Mage::getModel('pensopay/carrier_shipping')->getMethodByCode($params['shipping']);
            if (empty($shippingData)) {
                $error = Mage::helper('pensopay')->__('Please specify a shipping method.');
            }
        }

        if ($error) {
            Mage::getSingleton('core/session')->addError($error);
            $this->_redirectReferer();
            return;
        }

        //Create order from quote
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $session = $this->_getSession();
        $items = $quote->getAllVisibleItems();

        /** @var PensoPay_Payment_Helper_Checkout $pensopayCheckoutHelper */
        $pensopayCheckoutHelper = Mage::helper('pensopay/checkout');

        /** @var PensoPay_Payment_Helper_Data $pensoPayHelper */
        $pensoPayHelper = Mage::helper('pensopay');

        try {
            if (!$quote->getCustomerId() && !$quote->getCustomerEmail()) {
                $quote->setCustomerEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
                $quote->setCustomerIsGuest(1);
            }

            $defaultValue = 'DNK';
            $defaultCountry = Mage::getStoreConfig('general/country/default', Mage::app()->getStore()->getStoreId());

            $defaultAddress = [
                'firstname' => $defaultValue,
                'lastname' => $defaultValue,
                'street' => $defaultValue,
                'city' => $defaultValue,
                'country_id' => $defaultCountry,
                'region' => $defaultValue,
                'postcode' => $defaultValue,
                'telephone' => $defaultValue,
                'vat_id' => '',
                'save_in_address_book' => 0
            ];

            $quote->getBillingAddress()->addData($defaultAddress);
            $quote->getShippingAddress()->addData($defaultAddress);

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('pensopay_mobilepay_pensopay_mobilepay');

            // Set Sales Order Payment
            $quote->getPayment()->importData(['method' => 'pensopay_mobilepay']);

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

            // Create Order From Quote
            $order = $service->getOrder();

            $shippingPrice = $shippingData['price'];
            $grandTotal = $order->getGrandTotal() + $shippingPrice;
            $order->setShippingAmount($shippingPrice);
            $order->setBaseShippingAmount($shippingPrice);
            $order->setShippingDescription('MobilePay - '.$shippingData['title']);
            $order->setGrandTotal($grandTotal);
            $order->setBaseGrandTotal($grandTotal);
            $order->save();

            $quote->setIsActive(0)->save();

            if ($order->getId()) {
                $session
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastQuoteId($quote->getId())
                    ->setLastOrderId($order->getId());
            }

            $order->setCustomShippingCode($params['shipping']);

            //Save quote id in session for retrieval later
            $session->setPensoPayQuoteId($session->getQuoteId());

            $session->unsQuoteId();
            $session->unsRedirectUrl();

            $payment = Mage::getModel('pensopay/payment');
            $pensopay_state = Mage::getSingleton('core/session')->getPensoPayState();

            /** @var PensoPay_Payment_Model_Api $api */
            $api = Mage::getModel('pensopay/api');

            $payment = $api->createPayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id, true);

            /** @var PensoPay_Payment_Model_Payment $newPayment */
            $newPayment = Mage::getModel('pensopay/payment');
            $newPayment->setStore($order->getStore());
            $pensoPayHelper->setTransactionStoreId($order->getStoreId());
            $newPayment->importFromRemotePayment($payment);
            $newPayment->setLink($paymentLink);
            $newPayment->setIsVirtualterminal(false);
            $newPayment->save();

            $this->_redirectUrl($paymentLink);

        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            //Restore quote and redirect to cart
            $pensopayCheckoutHelper->restoreQuote();
            $pensopayCheckoutHelper->getCoreSession()->addError($e->getMessage());
            $this->_redirect('checkout/cart');
            return;
        }
    }

    /**
     * Retrieve checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}