<?php

class PensoPay_Payment_Model_System_Config_Source_Cardlogos
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'dankort',
                'label' => Mage::helper('pensopay')->__('Dankort')
            ),
            array(
                'value' => 'forbrugsforeningen',
                'label' => Mage::helper('pensopay')->__('Forbrugsforeningen')
            ),
            array(
                'value' => 'visa',
                'label' => Mage::helper('pensopay')->__('VISA')
            ),
            array(
                'value' => 'visaelectron',
                'label' => Mage::helper('pensopay')->__('VISA Electron')
            ),
            array(
                'value' => 'mastercard',
                'label' => Mage::helper('pensopay')->__('MasterCard')
            ),
            array(
                'value' => 'maestro',
                'label' => Mage::helper('pensopay')->__('Maestro')
            ),
            array(
                'value' => 'jcb',
                'label' => Mage::helper('pensopay')->__('JCB')
            ),
            array(
                'value' => 'diners',
                'label' => Mage::helper('pensopay')->__('Diners Club')
            ),
            array(
                'value' => 'amex',
                'label' => Mage::helper('pensopay')->__('AMEX')
            ),
            array(
                'value' => 'sofort',
                'label' => Mage::helper('pensopay')->__('Sofort')
            ),
            array(
                'value' => 'viabill',
                'label' => Mage::helper('pensopay')->__('ViaBill')
            ),
            array(
                'value' => 'mobilepay',
                'label' => Mage::helper('pensopay')->__('MobilePay')
            ),
            array(
                'value' => 'applepay',
                'label' => Mage::helper('pensopay')->__('ApplePay')
            )
        );
    }

    /**
     * Get label for card
     *
     * @param  string $value
     *
     * @return string
     */
    public function getFrontendLabel($value)
    {
        foreach ($this->toOptionArray() as $option) {
            if ($value = $option['value']) {
                return $option['label'];
            }
        }

        return '';
    }
}