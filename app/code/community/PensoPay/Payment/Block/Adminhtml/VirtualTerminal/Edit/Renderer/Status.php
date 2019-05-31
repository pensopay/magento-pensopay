<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit_Renderer_Status extends Varien_Data_Form_Element_Abstract
{
    public function getElementHtml()
    {
        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');

        //value in this case is the payment id
        $value = $this->getEscapedValue();

        /** @var PensoPay_Payment_Model_Payment $payment */
        $payment = Mage::getModel('pensopay/payment')->load($value);

        $extraClass = $helper->getStatusColorCode($payment->getLastCode());
        $html = "
            <div class='payment-status {$extraClass}'>
                {$payment->getDisplayStatus()}
            </div>
        ";
        return $html;
    }
}