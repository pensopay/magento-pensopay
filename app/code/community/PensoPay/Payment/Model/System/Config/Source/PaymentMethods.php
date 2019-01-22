<?php
class PensoPay_Payment_Model_System_Config_Source_PaymentMethods
{
    const METHOD_SPECIFIED = 'specified';
    const METHOD_CREDITCARDS = 'creditcard';

    /**
     * Get available payment methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => Mage::helper('pensopay')->__('All Payment Methods')
            ],
            [
                'value' => self::METHOD_CREDITCARDS,
                'label' => Mage::helper('pensopay')->__('All Credit Cards')
            ],
            [
                'value' => self::METHOD_SPECIFIED,
                'label' => Mage::helper('pensopay')->__('As Specified')
            ],
        ];
    }
}
