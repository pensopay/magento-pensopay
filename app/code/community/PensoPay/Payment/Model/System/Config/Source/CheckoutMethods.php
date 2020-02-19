<?php
class PensoPay_Payment_Model_System_Config_Source_CheckoutMethods
{
    const METHOD_REDIRECT = 'redirect';
    const METHOD_IFRAME = 'iframe';
    const METHOD_EMBEDDED = 'embedded';

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
                'value' => self::METHOD_EMBEDDED,
                'label' => Mage::helper('pensopay')->__('Embedded')
            ],
//            [
//                'value' => self::METHOD_IFRAME,
//                'label' => Mage::helper('pensopay')->__('Iframe')
//            ],
        ];
    }
}
