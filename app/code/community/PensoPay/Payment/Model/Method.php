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

    /**
     * Capture payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        return $this;
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