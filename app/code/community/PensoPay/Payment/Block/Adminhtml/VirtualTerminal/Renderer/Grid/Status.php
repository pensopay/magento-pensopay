<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());

        $extraClass = '';

        switch ($value) {
            case PensoPay_Payment_Model_Payment::STATE_INITIAL:
                $extraClass = 'yellow';
                break;
            case PensoPay_Payment_Model_Payment::STATE_NEW:
            case PensoPay_Payment_Model_Payment::STATE_PENDING:
                $extraClass = 'orange';
                break;
            case PensoPay_Payment_Model_Payment::STATE_REJECTED:
                $extraClass = 'red';
                break;
            case PensoPay_Payment_Model_Payment::STATE_PROCESSED:
            default:
                $extraClass = 'green';
        }

        $html = "
            <div class='payment-status {$extraClass}'>
                {$value}
            </div>
        ";

        return $html;
    }
}