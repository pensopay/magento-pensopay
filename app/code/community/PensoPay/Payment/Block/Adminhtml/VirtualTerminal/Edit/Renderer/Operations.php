<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit_Renderer_Operations extends Varien_Data_Form_Element_Abstract
{
    public function getElementHtml()
    {
        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');

        $value = $this->getValue();
        $operationsArray = json_decode($value, true);

        if (!empty($operationsArray)) {
            $html = '<table class="operations">';
            $html .= sprintf('<tr><th>%s</th><th>%s</th><th>%s</th></tr>', $helper->__('Type'), $helper->__('Result'), $helper->__('Time'));
            foreach ($operationsArray as $operation) {
                $html .= sprintf('<tr class="%s"><td>%s</td><td>%s: %s</td><td>%s</td></tr>', $helper->getStatusColorCode($operation['qp_status_code']), $operation['type'], $operation['qp_status_code'], $operation['qp_status_msg'], strftime('%d-%m-%Y %H:%M:%S', strtotime($operation['created_at'])));
            }
            $html .= '</table>';
            return $html;
        }
        return $helper->__('Error during operations rendering.');
    }
}