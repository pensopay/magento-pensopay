<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Link extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        if ($value === 'Array' || empty($value) || is_array($value)) { //in case of a corrupted link in the database, show an error
            return $this->__('Error');
        }
        $link = sprintf('<a class="payment-link" href="%s" target="_blank">%s</a>', $value, $this->__('Link'));
        return $link;
    }
}