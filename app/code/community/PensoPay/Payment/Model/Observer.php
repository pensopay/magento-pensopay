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

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        /** @var PensoPay_Payment_Model_Payment $payment */
        foreach ($collection as $payment) {
            if ($payment->getReferenceId() && !in_array($payment->getState(), PensoPay_Payment_Model_Payment::FINALIZED_STATES)) {
                $paymentIncrementId = $payment->getId();
                $paymentInfo = $api->getPayment($payment->getReferenceId());
                $paymentInfoAsArray = json_decode(json_encode($paymentInfo), true);

                unset($paymentInfoAsArray['id']);
                $payment->addData($paymentInfoAsArray);
                if (is_array($paymentInfoAsArray['link'])) {
                    $payment->setLink($paymentInfoAsArray['link']['url']);
                }
                $payment->save();
            }
        }
        return $this;
    }
}