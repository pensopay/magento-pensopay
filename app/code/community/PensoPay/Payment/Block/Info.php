<?php

class PensoPay_Payment_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pensopay/info/default.phtml');
    }

    /**
     * Get order payment
     *
     * @return string
     */
    public function getPayment()
    {
        if ($this->getInfo()->getOrder()) {
            /** @var PensoPay_Payment_Model_Payment $payment */
            $payment = Mage::getModel('pensopay/payment');
            $payment->load($this->getInfo()->getOrder()->getIncrementId(), 'order_id');
            if ($payment->getId()) {
                $firstOp = $payment->getFirstOperation();
                if (!empty($firstOp)) {
                    if ($firstOp['type'] == PensoPay_Payment_Model_Payment::OPERATION_AUTHORIZE && $firstOp['code'] == PensoPay_Payment_Model_Payment::STATUS_APPROVED) {
                        return $payment;
                    }
                }
            }
        }
        return null;
    }
}
