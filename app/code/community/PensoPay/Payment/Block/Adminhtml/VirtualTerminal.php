<?php

class PensoPay_Payment_Block_Adminhtml_Virtualterminal extends Mage_Adminhtml_Block_Widget_Grid_Container {
    public function __construct()
    {
        parent::__construct();

        $this->_removeButton('add');

        $this->_addButtonLabel = $this->__('Create Payment');
        $this->_addButton('edit', array(
            'label'     => $this->getAddButtonLabel(),
            'onclick'   => 'setLocation(\'' . $this->getCreateUrl() .'\')',
            'class'     => 'add',
        ));
    }

    public function getCreateUrl()
    {
        return $this->getUrl('*/*/edit');
    }

    public function _toHtml()
    {
        $html = "";
        $session = Mage::getSingleton('adminhtml/session');
        if ($session->getPaymentLink()) {
            $paymentBlock = $this->getChild('payment_additional');
            $paymentBlock->setPaymentLink($session->getData('payment_link', true)); //Avoid repeat popups
            $paymentBlock->setPaymentLinkAutovisit($session->getData('payment_link_autovisit', true)); // ^
            $html = $this->getChildHtml('payment_additional');
        }
        $html .= parent::_toHtml();
        return $html;
    }

    public function _construct() {
        $this->_blockGroup = 'pensopay';
        $this->_controller = 'adminhtml_virtualTerminal';
        $this->_headerText = $this->__('PensoPay Payments');
        parent::_construct();
    }
}