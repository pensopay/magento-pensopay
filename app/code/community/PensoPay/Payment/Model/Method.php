<?php

class PensoPay_Payment_Model_Method extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'pensopay';

    /**
     * Form block type
     *
     * @see PensoPay_Payment_Block_Form for the corresponding class
     * @var string
     */
    protected $_formBlockType = 'pensopay/form';

    /**
     * Info block type
     *
     * @see PensoPay_Payment_Block_Info for the corresponding class
     * @var string
     */
    protected $_infoBlockType = 'pensopay/info';

    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = true;
    protected $_canManageRecurringProfiles  = true;

    /** @var PensoPay_Payment_Model_Api $_api */
    protected $_api;

    /** @var PensoPay_Payment_Helper_Data $_helper */
    protected $_helper;

    public function __construct()
    {
        parent::__construct();
        $this->_api = Mage::getModel('pensopay/api');
        $this->_helper = Mage::helper('pensopay');
    }

    /**
     * Set order status to pending
     *
     * @param string $paymentAction
     * @param object $stateObject
     * @return Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending');
        $stateObject->setIsNotified(false);

        return parent::initialize($paymentAction, $stateObject);
    }

    private function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if ($payment->getInfoInstance()) {
            $order = $payment->getInfoInstance()->getOrder();
        } else {
            $order = $payment->getOrder();
        }

        /** @var PensoPay_Payment_Model_Payment $payment */
        $payment = Mage::getModel('pensopay/payment')->load($order->getIncrementId(), 'order_id');
        if (!$payment->getId())
            throw new Exception($this->_helper->__('Payment not found.'));

        $amountCaptured = $payment->getAmountCaptured();
        if ($payment->getAmount() < ($amountCaptured + $amount)) {
            throw new Exception($this->_helper->__('Trying to capture more than authorized.'));
        }

        try {
            $paymentInfo = $this->_api->capture($payment->getReferenceId(), $amount);
            $payment->importFromRemotePayment($paymentInfo);

            $lastCode = $payment->getLastCode();
            if ($lastCode == PensoPay_Payment_Model_Payment::STATUS_APPROVED) {
                $payment->setAmountCaptured($amountCaptured + $amount);
                $this->createTransaction($order, $payment->getReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                $this->_getSession()->addSuccess($this->_helper->__('Payment captured online.'));
            } else {
                throw new Exception($payment->getLastMessage());
            }
        } catch (Exception $e) {
            Mage::log(sprintf('Capture error for: %s -- %s', $payment->getId(), $e->getMessage()));
        } finally {
            $payment->save();
            $order->save();

            if (!is_null($e))
                throw $e; //rethrow it
        }

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     * @throws Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        /** @var PensoPay_Payment_Model_Payment $payment */
        $payment = Mage::getModel('pensopay/payment')->load($order->getIncrementId(), 'order_id');
        if (!$payment->getId())
            throw new Exception($this->_helper->__('Payment not found.'));

        $amountRefunded = $payment->getAmountRefunded();
        if ($payment->getAmount() < ($amountRefunded + $amount)) {
            throw new Exception($this->_helper->__('Trying to refund more than captured.'));
        }
        try {
            $paymentInfo = $this->_api->refund($payment->getReferenceId(), $amount);
            $payment->importFromRemotePayment($paymentInfo);

            $lastCode = $payment->getLastCode();
            if ($lastCode == PensoPay_Payment_Model_Payment::STATUS_APPROVED) {
                $payment->setAmountRefunded($amountRefunded + $amount);
                $this->createTransaction($order, $payment->getReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
                $this->_getSession()->addSuccess($this->_helper->__('Amount refunded online.'));
            } else {
                throw new Exception($payment->getLastMessage());
            }
        } catch (Exception $e) {
            Mage::log(sprintf('Capture error for: %s -- %s', $payment->getId(), $e->getMessage()));
        } finally {
            $payment->save();
            $order->save();

            if (!is_null($e))
                throw $e; //rethrow it
        }

        return $this;
    }

    /**
     * @param $order
     * @param $transactionId
     * @param $type
     * @return false|Mage_Core_Model_Abstract|Mage_Sales_Model_Order_Payment_Transaction
     * @throws Exception
     */
    public function createTransaction($order, $transactionId, $type)
    {
        $orderPayment = $order->getPayment();
        $orderPayment->setLastTransId($transactionId);
        $orderPayment->save();

        $transaction = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderPaymentObject($orderPayment);

        if (! $transaction = $transaction->loadByTxnId($transactionId)) {
            $transaction = Mage::getModel('sales/order_payment_transaction');
            $transaction->setOrderPaymentObject($orderPayment);
            $transaction->setOrder($order);
        }

        if ($type == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
            $transaction->setIsClosed(false);
        } else {
            $transaction->setIsClosed(true);
        }

        $transaction->setTxnId($transactionId);
        $transaction->setTxnType($type);
        $transaction->save();

        return $transaction;
    }

    /**
     * Get payment methods
     *
     * @return mixed
     */
    public function getPaymentMethods()
    {
        if ($this->getConfigData('payment_method') === 'specified') {
            return $this->getConfigData('payment_method_specified');
        }

        return $this->getConfigData('payment_method');
    }

    /**
     * Get Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('pensopay/payment/redirect', array('_secure' => true));
    }
}