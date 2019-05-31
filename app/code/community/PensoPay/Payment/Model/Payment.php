<?php

class PensoPay_Payment_Model_Payment extends Mage_Core_Model_Abstract {
    /** @var PensoPay_Payment_Helper_Data $_helper*/
    protected $_helper;

    const STATE_INITIAL = 'initial';
    const STATE_NEW     = 'new';
    const STATE_PROCESSED = 'processed';
    const STATE_PENDING = 'pending';
    const STATE_REJECTED = 'rejected';

    const STATUS_APPROVED = 20000;
    const STATUS_WAITING_APPROVAL = 20200;
    const STATUS_3D_SECURE_REQUIRED = 30100;
    const STATUS_REJECTED_BY_ACQUIRER = 40000;
    const STATUS_REQUEST_DATA_ERROR = 40001;
    const STATUS_AUTHORIZATION_EXPIRED = 40002;
    const STATUS_ABORTED = 40003;
    const STATUS_GATEWAY_ERROR = 50000;
    const COMMUNICATIONS_ERROR_ACQUIRER = 50300;

    protected $_lastOperation = array();

    const STATUS_CODES =
    [
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_WAITING_APPROVAL => 'Waiting approval',
        self::STATUS_3D_SECURE_REQUIRED => '3D Secure is required',
        self::STATUS_REJECTED_BY_ACQUIRER => 'Rejected By Acquirer',
        self::STATUS_REQUEST_DATA_ERROR => 'Request Data Error',
        self::STATUS_AUTHORIZATION_EXPIRED => 'Authorization expired',
        self::STATUS_ABORTED => 'Aborted',
        self::STATUS_GATEWAY_ERROR => 'Gateway Error',
        self::COMMUNICATIONS_ERROR_ACQUIRER => 'Communications Error (with Acquirer)'
    ];

    /**
     * States in which the payment can't be updated anymore
     * Used for cron.
     */
    const FINALIZED_STATES =
    [
        self::STATE_REJECTED,
        self::STATE_PROCESSED
    ];

    public function __construct()
    {
        parent::__construct();
        $this->_init('pensopay/payment');
        $this->_helper = Mage::helper('pensopay');
    }

    public function getDisplayStatus()
    {
        $lastCode = $this->getLastCode();

        $status = '';
        if ($lastCode == self::STATUS_APPROVED && $this->getLastType() == 'capture') {
            $status = $this->_helper->__('Captured');
        } else if (!empty(self::STATUS_CODES[$lastCode])) {
            $status = self::STATUS_CODES[$lastCode];
        }
        return sprintf('%s (%s)', $status, $this->getState());
    }

    protected function _getLastOperation()
    {
        if (empty($this->_lastOperation)) {
            if (!empty($this->getOperations())) {
                $operations = json_decode($this->getOperations(), true);
                if (!empty($operations) && is_array($operations)) {
                    $lastOp = array_pop($operations);
                    if (!empty($lastOp) && is_array($lastOp)) {
                        $this->_lastOperation = [
                            'type' => $lastOp['type'],
                            'code' => $lastOp['qp_status_code']
                        ];
                    }
                }
            }
        }
        return $this->_lastOperation;
    }

    public function getLastType()
    {
        return $this->_getLastOperation()['type'];
    }

    public function getLastCode()
    {
        return $this->_getLastOperation()['code'];
    }

    /**
     * @param stdClass $payment
     */
    public function importFromRemotePayment($payment)
    {
        $paymentAsArray = json_decode(json_encode($payment), true);
        $this->setReferenceId($paymentAsArray['id']);
        unset($paymentAsArray['id']); //We don't want to override the object id with the remote id
        $this->addData($paymentAsArray);
        if (isset($paymentAsArray['link']) && !empty($paymentAsArray['link'])) {
            if (is_array($paymentAsArray['link'])) {
                $this->setLink($paymentAsArray['link']['url']);
            } else {
                $this->setLink($paymentAsArray['link']);
            }
        }
        $this->setAmount($paymentAsArray['basket'][0]['item_price']);
        $this->setCurrencyCode($paymentAsArray['currency']);
        $this->setOperations(json_encode($paymentAsArray['operations']));
    }

    /**
     * Updates payment data from remote gateway.
     *
     * @throws Exception
     */
    public function updatePaymentRemote()
    {
        if (!$this->getId()) {
            throw new Exception($this->_helper->__('Payment not loaded.'));
        }

        if (!$this->getReferenceId()) {
            throw new Exception($this->_helper->__('Reference id not found.'));
        }

        /** @var PensoPay_Payment_Model_Api $api */
        $api = Mage::getModel('pensopay/api');

        $paymentInfo = $api->getPayment($this->getReferenceId());
        $paymentInfoAsArray = json_decode(json_encode($paymentInfo), true);

        unset($paymentInfoAsArray['id']);
        $this->addData($paymentInfoAsArray);
        if (is_array($paymentInfoAsArray['link'])) {
            $this->setLink($paymentInfoAsArray['link']['url']);
        }
        $this->setOperations(json_encode($paymentInfoAsArray['operations']));
        $this->save();
    }
}