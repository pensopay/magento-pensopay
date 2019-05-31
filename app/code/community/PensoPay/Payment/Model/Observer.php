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
}