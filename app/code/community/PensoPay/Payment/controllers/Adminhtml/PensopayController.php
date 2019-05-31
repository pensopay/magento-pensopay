<?php

class PensoPay_Payment_Adminhtml_PensopayController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->_redirectToTerminal();
    }


    /**
     * Show virtual terminal
     */
    public function terminalAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    private function _redirectToTerminal($error = '')
    {
        if (!empty($error)) {
            $this->_getSession()->addError($error);
        }
        return $this->_redirect('adminhtml/pensopay/terminal');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');

        if (!empty($id)) {
            $payment = Mage::getModel('pensopay/payment')->load($id);
            if (!$payment->getId()) {
                return $this->_redirectToTerminal($this->__('Payment not found.'));
            }
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * @param $postData
     * @param null $payment
     * @return false|Mage_Core_Model_Abstract|Mage_Sales_Model_Order
     */
    private function _getOrderObject($postData, $payment = null)
    {
        $order = Mage::getModel('sales/order');

        if ($payment) {
            $order->setId($payment->getOrderId());
            $order->setIncrementId($payment->getOrderId());
            $order->setReferenceId($payment->getReferenceId());
        } else {
            $order->setId($postData['order_id']);
            $order->setIncrementId($postData['order_id']);
        }

        $order->setIsVirtualTerminal(true);

        $order->setGrandTotal($postData['amount']);
        $order->setOrderCurrencyCode($postData['currency_code']);
        $order->setLocaleCode($postData['locale_code']);
        $order->setAutocapture($postData['autocapture']);
        $order->setAutofee($postData['autofee']);

        $order->setCustomerEmail($postData['customer_email']);
        $order->setCustomerName($postData['customer_name']);
        $order->setCustomerStreet($postData['customer_name']);
        $order->setCustomerZipcode($postData['customer_name']);

        return $order;
    }

    private function _updatePaymentLink($sendEmail) {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        $postData = $request->getPost();

        $incId = $request->getParam('id');
        $paymentModel = Mage::getModel('pensopay/payment');
        if (!empty($incId)) { //Existing payment
            $paymentModel->load($incId);
            if (!$paymentModel->getId())
                return false;
        }

        $order = $this->_getOrderObject($postData, $paymentModel);

        if (!$order) {
            $this->_getSession()->addError($this->__('Could not create payment object.'));
            return false;
        }

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        $postData = $request->getPost();

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        try {
//            $api->deletePaymentLink($order->getReferenceId()); //Currently not accepted
            $payment = $api->updatePayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);

            $this->_getSession()->setPaymentLink($paymentLink);
            $this->_getSession()->addSuccess($paymentLink);

            $paymentModel->addData($postData);
            $paymentModel->importFromRemotePayment($payment);
            $paymentModel->setLink($paymentLink);
            $paymentModel->save();

            if ($sendEmail) {
                /** @var PensoPay_Payment_Helper_Data $helper */
                $helper = Mage::helper('pensopay');
                $helper->sendEmail($postData['customer_email'], $postData['customer_name'] ?: '', $paymentModel->getAmount(), $paymentModel->getCurrencyCode(), $paymentLink);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        return true;
    }

    /**
     * @param $sendEmail
     * @return bool
     */
    private function _createPaymentLink($sendEmail)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();
        $postData = $request->getPost();

        $order = $this->_getOrderObject($postData);

        if (!$order) {
            $this->_getSession()->addError($this->__('Could not create payment object.'));
            return false;
        }

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        try {
            $payment = $api->createPayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);
            $this->_getSession()->setPaymentLink($paymentLink);
            $this->_getSession()->addSuccess($paymentLink);

            /** @var PensoPay_Payment_Model_Payment $newPayment */
            $newPayment = Mage::getModel('pensopay/payment');

            $newPayment->setData($postData);
            $newPayment->importFromRemotePayment($payment);
            $newPayment->setLink($paymentLink);
            $newPayment->setIsVirtualterminal(true);
            $newPayment->setData('id', null);
            $newPayment->save();

            if ($sendEmail) {
                /** @var PensoPay_Payment_Helper_Data $helper */
                $helper = Mage::helper('pensopay');
                $helper->sendEmail($postData['customer_email'], $postData['customer_name'] ?: '', $newPayment->getAmount(), $newPayment->getCurrencyCode(), $paymentLink);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        return true;
    }

    public function saveAndPayAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_createPaymentLink(false)) {
                $this->_getSession()->setPaymentLinkAutovisit(true);
            }
        }
        return $this->_redirectToTerminal();
    }

    public function saveAndSendAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->_createPaymentLink(true);
        }
        return $this->_redirectToTerminal();
    }

    public function updateAndPayAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_updatePaymentLink(false)) {
                $this->_getSession()->setPaymentLinkAutovisit(true);
            }
        }
        return $this->_redirectToTerminal();
    }

    public function updateAndSendAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->_updatePaymentLink(true);
        }
        return $this->_redirectToTerminal();
    }

    public function updatePaymentStatusAction()
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();

        $incId = $request->getParam('id');

        /** @var PensoPay_Payment_Model_Payment $paymentModel */
        $paymentModel = Mage::getModel('pensopay/payment');
        if (empty($incId)) {
            return $this->_redirectToTerminal($this->__('Payment not found.'));
        }
        try {
            $paymentModel->load($incId);
            $paymentModel->updatePaymentRemote();
            $this->_getSession()->addSuccess($this->__('Payment updated successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/*/edit', array('id' => $paymentModel->getId()));
    }

    public function cancelPaymentAction()
    {
        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $this->getRequest();

        $incId = $request->getParam('id');

        /** @var PensoPay_Payment_Model_Payment $paymentModel */
        $paymentModel = Mage::getModel('pensopay/payment');

        if (!empty($incId)) { //Existing payment
            $paymentModel->load($incId);
            if (!$paymentModel->getId()) {
                return $this->_redirectToTerminal($this->__('Payment not found.'));
            }

            try {
                $payment = $api->cancelPayment($paymentModel->getReferenceId());
                $this->_getSession()->addSuccess($this->__('Payment cancelled.'));

                $paymentModel->importFromRemotePayment($payment);
                $paymentModel->save();
            } catch (Exception $e) {
                return $this->_redirectToTerminal($e->getMessage());
            }
        } else {
            $this->_getSession()->addError($this->__('No payment id specified.'));
        }
        return $this->_redirectToTerminal();
    }
}