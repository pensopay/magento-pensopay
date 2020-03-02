<?php

class PensoPay_Payment_Block_Form extends Mage_Payment_Block_Form
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
        $cardsConfig = Mage::getStoreConfig('payment/pensopay/cardlogos');
        $cards = explode(',', $cardsConfig);

        $html = '';
        if (count($cards) > 0) {
            $logoConfig = Mage::getModel('pensopay/system_config_source_cardlogos');
            foreach ($cards as $card) {
                $html .= sprintf('<img src="%s" height="%s" alt="%s"/>', $this->getSkinUrl("images/pensopaypayment/{$card}.png"), Mage::getStoreConfig('payment/pensopay/cardlogos_size'), $logoConfig->getFrontendLabel($card));
            }
        }

        return $html;
    }
}