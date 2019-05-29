<?php

class PensoPay_Payment_PaymentController extends Mage_Core_Controller_Front_Action
{
    const FRAUD_PROBABILITY_HIGH = 'high';
    const FRAUD_PROBABILITY_NONE = 'none';

    /**
     * Redirect to gateway
     */
    public function redirectAction()
    {
        /** @var PensoPay_Payment_Helper_Checkout $pensopayCheckoutHelper */
        $pensopayCheckoutHelper = Mage::helper('pensopay/checkout');

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        $order = $pensopayCheckoutHelper->getCheckoutSession()->getLastRealOrder();
        try {

            $payment = $api->createPayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);
        } catch (Exception $e) {
            //Restore quote and redirect to cart
            $pensopayCheckoutHelper->restoreQuote();

            $pensopayCheckoutHelper->getCoreSession()->addError($e->getMessage());
            $this->_redirect('checkout/cart');
        }

        if ($pensopayCheckoutHelper->isCheckoutIframe()) {
            $pensopayCheckoutHelper->getCoreSession()->setPaymentWindowUrl($paymentLink);
            $this->_redirect('*/*/iframe');
        } else {
            $this->_redirectUrl($paymentLink);
        }
    }

    /**
     *
     */
    public function successAction()
    {
        /** @var PensoPay_Payment_Helper_Checkout $pensopayCheckoutHelper */
        $pensopayCheckoutHelper = Mage::helper('pensopay/checkout');

        $order = $pensopayCheckoutHelper->getCheckoutSession()->getLastRealOrder();

//        $payment = Mage::getModel('quickpaypayment/payment');

//        $quoteID = Mage::getSingleton('checkout/cart')->getQuote()->getId();

//        if ($quoteID) {
//            $quote = Mage::getModel('sales/quote')->load($quoteID);
//            $quote->setIsActive(false)->save();
//        }

        // CREATES INVOICE if payment instantcapture is ON
//        if ((int)$payment->getConfigData('instantcapture') == 1 && (int)$payment->getConfigData('instantinvoice') == 1) {
//            if ($order->canInvoice()) {
//                $invoice = $order->prepareInvoice();
//                $invoice->register();
//                $invoice->setEmailSent(true);
//                $invoice->getOrder()->setCustomerNoteNotify(true);
//                $invoice->sendEmail(true, '');
//                Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
//
//                $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
//                $order->save();
//            }
//        } else {
//            if (((int)$payment->getConfigData('sendmailorderconfirmationbefore')) == 1) {
//                $this->sendEmail($order);
//            }
//        }

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Handle callback
     *
     * @return $this
     */
    public function callbackAction()
    {
        $requestBody = $this->getRequest()->getRawBody();
        $request = json_decode($requestBody);

        $checksum = hash_hmac("sha256", $requestBody, $this->getPrivateKey());

        if ($checksum === $this->getRequest()->getServer('HTTP_QUICKPAY_CHECKSUM_SHA256')) {
            $operation = end($request->operations);
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($request->order_id);

            if (! Mage::getStoreConfigFlag(PensoPay_Payment_Model_Config::XML_PATH_TESTMODE_ENABLED) && $request->test_mode) {
                //Cancel order
                if ($order->canCancel()) {
                    try {
                        $order->cancel();
                        $order->addStatusToHistory($order->getStatus(), "Order placed with test card.");
                        $order->save();
                    } catch (Exception $e) {
                        Mage::log('Failed to cancel testmode order #' . $order->getIncrementId(), null, 'qp_debug.log');
                    }
                }

                $this->getResponse()->setBody(json_encode([
                    'error' => 'Attempted to pay with test card but testmode is disabled',
                ]));

                return $this;
            }

            if ($request->accepted && $operation->type == 'authorize') {
                $metadata = $request->metadata;
                $fraudSuspected = $metadata->fraud_suspected;

                $fraudProbability = self::FRAUD_PROBABILITY_HIGH;

                if ($fraudSuspected) {
                    $fraudProbability = self::FRAUD_PROBABILITY_HIGH;
                } else {
                    $fraudProbability = self::FRAUD_PROBABILITY_NONE;
                }
            }
        }
//        $payment = $order->getPayment();
//        $txnId = $transactionResponse->transaction->id;
//        $payment->setTransactionId($txnId);
//        $payment->setIsTransactionClosed(false);
//        $payment->setAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE, $txnId);
//        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
//        $payment->setCcType($transactionResponse->transaction->information->paymentTypes[0]->displayName);
    }

    /**
     * Show payment iframe
     */
    public function iframeAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Handle customer cancelling payment
     */
    public function cancelAction()
    {
        /** @var PensoPay_Payment_Helper_Checkout $pensopayCheckoutHelper */
        $pensopayCheckoutHelper = Mage::helper('pensopay/checkout');

        //Read quote id from session and attempt to restore

        /** @var Mage_Checkout_Model_Session $session */
        $session = $pensopayCheckoutHelper->getCheckoutSession();

        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }

            Mage::helper('pensopay/checkout')->restoreQuote();
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Get private key
     *
     * @return mixed
     */
    private function getPrivateKey()
    {
        return Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_PRIVATE_KEY);
    }
}