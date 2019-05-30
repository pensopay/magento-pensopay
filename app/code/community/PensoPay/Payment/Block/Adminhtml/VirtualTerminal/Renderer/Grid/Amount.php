<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Amount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $currency = $row->getData('currency');
        return sprintf('%s %s', $currency, $value);
    }
}