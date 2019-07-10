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

    const OPERATION_CAPTURE = 'capture';
    const OPERATION_AUTHORIZE = 'authorize';
    const OPERATION_CANCEL = 'cancel';
    const OPERATION_REFUND = 'refund';

    const FRAUD_PROBABILITY_HIGH = 'high';
    const FRAUD_PROBABILITY_NONE = 'none';

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
        if ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_CAPTURE) {
            $status = $this->_helper->__('Captured');
        } else if ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_CANCEL) {
            $status = $this->_helper->__('Cancelled');
        } else if ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_REFUND) {
            $status = $this->_helper->__('Refunded');
        } else if (!empty(self::STATUS_CODES[$lastCode])) {
            $status = self::STATUS_CODES[$lastCode];
        }
        return sprintf('%s (%s)', $status, $this->getState());
    }

    public function getMetadata()
    {
        if (!empty($this->getData('metadata'))) {
            return json_decode($this->getData('metadata'), true);
        }
        return [];
    }

    public function getFirstOperation()
    {
        if (!empty($this->getOperations())) {
            $operations = json_decode($this->getOperations(), true);
            if (!empty($operations) && is_array($operations)) {
                $firstOp = array_shift($operations);
                if (!empty($firstOp) && is_array($firstOp)) {
                    return [
                        'type' => $firstOp['type'],
                        'code' => $firstOp['qp_status_code'],
                        'msg'  => $firstOp['qp_status_msg']
                    ];
                }
            }
        }
        return [];
    }

    public function getLastOperation()
    {
        if (empty($this->_lastOperation)) {
            if (!empty($this->getOperations())) {
                $operations = json_decode($this->getOperations(), true);
                if (!empty($operations) && is_array($operations)) {
                    $lastOp = array_pop($operations);
                    if (!empty($lastOp) && is_array($lastOp)) {
                        $this->_lastOperation = [
                            'type' => $lastOp['type'],
                            'code' => $lastOp['qp_status_code'],
                            'msg'  => $lastOp['qp_status_msg']
                        ];
                    }
                }
            }
        }
        return $this->_lastOperation;
    }

    public function getLastMessage()
    {
        return $this->getLastOperation()['msg'];
    }

    public function getLastType()
    {
        return $this->getLastOperation()['type'];
    }

    public function getLastCode()
    {
        return $this->getLastOperation()['code'];
    }

    /**
     * @param stdClass $payment
     */
    public function importFromRemotePayment($payment)
    {
        if (!Mage::getStoreConfigFlag(PensoPay_Payment_Model_Config::XML_PATH_TESTMODE_ENABLED) && $payment->test_mode) {
            $this->setState(self::STATE_REJECTED);
            return;
        }

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
        if (!empty($paymentAsArray['metadata']) && is_array($paymentAsArray['metadata'])) {
            $this->setFraudProbability($paymentAsArray['metadata']['fraud_suspected'] || $paymentAsArray['metadata']['fraud_reported'] ? self::FRAUD_PROBABILITY_HIGH : self::FRAUD_PROBABILITY_NONE);
        }
        $this->setOperations(json_encode($paymentAsArray['operations']));
        $this->setMetadata(json_encode($paymentAsArray['metadata']));
        $this->setHash(md5($this->getReferenceId() . $this->getLink() . $this->getAmount()));

        if (!empty($payment->operations)) {
            $amountCaptured = 0;
            $amountRefunded = 0;
            foreach ($payment->operations as $operation) {
                if ($operation->type == 'capture') {
                    $amountCaptured += $operation->amount;
                } else if ($operation->type == 'refund') {
                    $amountRefunded += $operation->amount;
                }
            }
            $this->setAmountCaptured($amountCaptured / 100);
            $this->setAmountRefunded($amountRefunded / 100);
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        if ($order->getId()) {
            $status = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ORDER_STATUS_AFTERPAYMENT);
            if ($order->getStatus() != $status) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status);
                $order->save();
            }
        }
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
        $this->importFromRemotePayment($paymentInfo);
        $this->save();
    }

    public function canCapture()
    {
        return $this->getState() === self::STATE_NEW;
    }

    public function canCancel()
    {
        return $this->getState() === self::STATE_NEW;
    }

    public function canRefund()
    {
        return ($this->getState() === self::STATE_PROCESSED && ($this->getAmount() !== $this->getAmountRefunded()));
    }
}