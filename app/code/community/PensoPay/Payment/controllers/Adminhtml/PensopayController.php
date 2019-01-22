<?php

class PensoPay_Payment_Adminhtml_PensopayController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Show virtual terminal
     */
    public function terminalAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}