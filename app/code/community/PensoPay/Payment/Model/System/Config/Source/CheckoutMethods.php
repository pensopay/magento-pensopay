<?php
class PensoPay_Payment_Model_System_Config_Source_CheckoutMethods
{
    const METHOD_REDIRECT = 'redirect';
    const METHOD_IFRAME = 'iframe';

    /**
     * Get available payment methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::METHOD_REDIRECT,
                'label' => Mage::helper('pensopay')->__('Redirect')
            ],
            [
                'value' => self::METHOD_IFRAME,
                'label' => Mage::helper('pensopay')->__('Iframe')
            ],
        ];
    }
}
