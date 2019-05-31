<?php

class PensoPay_Payment_Model_Observer
{
    /**
     * Check for feed updates
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            /** @var PensoPay_Payment_Model_Feed $feedModel */
            $feedModel  = Mage::getModel('pensopay/feed');

            $feedModel->checkUpdate();
        }
    }

    public function updateVirtualterminalPaymentStatus()
    {
        /** @var PensoPay_Payment_Model_Resource_Payment_Collection $collection */
        $collection = Mage::getResourceModel('pensopay/payment_collection');
        $collection->addFieldToFilter('state', array('nin' => PensoPay_Payment_Model_Payment::FINALIZED_STATES));
        $collection->addFieldToFilter('reference_id', array('notnull' => true));
        $collection->addFieldToFilter('is_virtualterminal', 1);

        /** @var PensoPay_Payment_Model_Payment $payment */
        foreach ($collection as $payment) {
            try {
                $payment->updatePaymentRemote();
            } catch (Exception $e) {
                Mage::log('CRON: Could not update payment remotely. Exception: ' . $e->getMessage(), LOG_WARNING, PensoPay_Payment_Helper_Data::LOG_FILENAME);
            }
        }
        return $this;
    }

    public function checkoutSubmitAllAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethodInstance() instanceof PensoPay_Payment_Model_Method) {
            /** @var PensoPay_Payment_Model_Api $api */
            $api = Mage::getModel('pensopay/api');

            /** @var PensoPay_Payment_Helper_Data $helper */
            $helper = Mage::helper('pensopay');

            $payment = $api->createPayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);

            /** @var PensoPay_Payment_Model_Payment $newPayment */
            $newPayment = Mage::getModel('pensopay/payment');

            $newPayment->importFromRemotePayment($payment);
            $newPayment->setLink($paymentLink);
            $newPayment->setIsVirtualterminal(false);
            $newPayment->save();

            $order->addStatusToHistory($order->getStatus(), $helper->__('Payment link:') . ' ' . $paymentLink, false);
            $order->save();

            /**
             * This is an admin panel order. This means, that the usual payment link will get the user to pay
             * and then redirect him to checkout/page/success, but since no quote will be loaded in the session
             * the user will just see the empty cart page. This way, we redirect the user through our site first
             * to load the quote properly and then send them off to the payment gateway, so he can properly see
             * the success page afterwards.
             */
            $truePaymentLink = $order->getStore()->getUrl('pensopay/payment/email', array('hash' => $newPayment->getHash()));

            $helper->sendEmail(
                $order->getBillingAddress()->getEmail(),
                $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname(),
                $order->getTotalDue(), $order->getOrderCurrencyCode(),
                $truePaymentLink
            );
        }
    }
}