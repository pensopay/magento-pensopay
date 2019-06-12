<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    /** @var mixed $_objId */
    protected $_objId;

    public function __construct() {
        $this->_blockGroup = 'pensopay';
        $this->_controller = 'adminhtml_virtualTerminal';

        parent::__construct();

        $this->removeButton('save');
        $this->removeButton('delete');

        $this->_objId = $this->getRequest()->getParam($this->_objectId);

        if (!$this->_objId) {
            $saveAndSendUrl = $this->getUrl('*/*/saveAndSend');
            $payNowUrl = $this->getUrl('*/*/saveAndPay');
            $this->_addButton('saveAndSend', array(
                'label'     => $this->__('Send Payment Link'),
                'onclick'   => sprintf("
                        $('customer_email').classList.add('required-entry');
                        editForm.submit('%s');", $saveAndSendUrl),
                'class'     => 'save',
            ), 1);

            /**
             * Onclick explanation
             *
             * First it removes any required-entry error message that might have been stuck from hitting saveAndSend first.
             * Then it removes the required-entry for the email since Pay Now does not require it.
             * Then submits.
             */
            $this->_addButton('saveAndPay', array(
                'label'     => $this->__('Pay Now'),
                'onclick'   => sprintf("
                        var emElem = $('advice-required-entry-customer_email');
                        if (emElem) emElem.parentNode.removeChild(emElem);
                        $('customer_email').classList.remove('required-entry');
                        editForm.submit('%s');", $payNowUrl),
                'class'     => 'save',
            ), 2);
        } else { //Updating payment
            $updateAndSend = $this->getUrl('*/*/updateAndSend');
            $updateAndPay = $this->getUrl('*/*/updateAndPay');
            $cancelUrl = $this->getUrl('*/*/cancelPayment', array('id' => $this->_objId));
            $captureUrl = $this->getUrl('*/*/capturePayment', array('id' => $this->_objId));
            $refundUrl = $this->getUrl('*/*/refundPayment', array('id' => $this->_objId));
            $updateStatusUrl = $this->getUrl('*/*/updatePaymentStatus', array('id' => $this->_objId));

            $this->_addButton('updateStatus', array(
                'label' => $this->__('Get Payment Status'),
                'onclick' => sprintf("
                        editForm.submit('%s');", $updateStatusUrl),
                'class' => 'save',
            ), 1, 0);

            /**
             * We could be doing a check for order validity here, but we're doing this on the controller
             * By now it can be assumed the order id is specified and it exists in the database.
             */
            /** @var PensoPay_Payment_Model_Payment $payment */
            $payment = Mage::getModel('pensopay/payment')->load($this->_objId);
            if ($payment->canCancel()) {
                $this->_addButton('cancel', array(
                    'label' => $this->__('Cancel'),
                    'class' => 'delete',
                    'onclick' => 'deleteConfirm(\''
                        . Mage::helper('core')->jsQuoteEscape(
                            $this->__('Are you sure you want to do this?')
                        )
                        . '\', \''
                        . $cancelUrl
                        . '\')',
                ), 1, 4);
            }

            if ($payment->canCapture()) {
                $this->_addButton('Capture', array(
                    'label' => $this->__('Capture'),
                    'class' => 'save',
                    'onclick' => 'deleteConfirm(\''
                        . Mage::helper('core')->jsQuoteEscape(
                            $this->__('Are you sure you want to do this?')
                        )
                        . '\', \''
                        . $captureUrl
                        . '\')',
                ), 1, 5);
            }

            if ($payment->canRefund()) {
                $this->_addButton('Refund', array(
                    'label' => $this->__('Refund'),
                    'class' => 'cancel',
                    'onclick' => 'deleteConfirm(\''
                        . Mage::helper('core')->jsQuoteEscape(
                            $this->__('Are you sure you want to do this?')
                        )
                        . '\', \''
                        . $refundUrl
                        . '\')',
                ), 1, 6);
            }

            if ($payment->getState() === PensoPay_Payment_Model_Payment::STATE_INITIAL) {
                $this->_addButton('updateAndSend', array(
                    'label' => $this->__('Update Payment and Send Link'),
                    'onclick' => sprintf("
                        $('customer_email').classList.add('required-entry');
                        editForm.submit('%s');", $updateAndSend),
                    'class' => 'save',
                ), 1, 7);

                /**
                 * Onclick explanation
                 *
                 * First it removes any required-entry error message that might have been stuck from hitting saveAndSend first.
                 * Then it removes the required-entry for the email since Pay Now does not require it.
                 * Then submits.
                 */
                $this->_addButton('updateAndPay', array(
                    'label' => $this->__('Update & Pay Now'),
                    'onclick' => sprintf("
                        var emElem = $('advice-required-entry-customer_email');
                        if (emElem) emElem.parentNode.removeChild(emElem);
                        $('customer_email').classList.remove('required-entry');
                        editForm.submit('%s');", $updateAndPay),
                    'class' => 'save',
                ), 1, 8);
            }
        }
    }

    public function getHeaderText() {
        if ($this->_objId)
            return $this->__('Edit Payment');
        else
            return $this->__('New Payment');
    }
}