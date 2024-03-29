<?php

class PensoPay_Payment_Block_Form_Paypal extends Mage_Payment_Block_Form
{
    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;

    protected function _construct()
    {
        $this->setTemplate('pensopay/payment/form.phtml');
        parent::_construct();
    }

    public function getConfigData($key)
    {
        return $this->getMethod()->getConfigData($key);
    }

    /**
     * Append logo on payment selection form
     *
     * @return string
     */
    public function getMethodLabelAfterHtml()
    {
        return sprintf('<img src="%s" height="%s" alt="%s"/>', $this->getSkinUrl('images/pensopaypayment/paypal.png'), Mage::getStoreConfig('payment/pensopay/cardlogos_size'), 'Paypal');
    }
}