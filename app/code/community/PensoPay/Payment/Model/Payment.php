<?php

class PensoPay_Payment_Model_Payment extends Mage_Core_Model_Abstract {
    /** @var PensoPay_Payment_Helper_Data $_helper*/
    protected $_helper;

    const STATE_INITIAL = 'initial';
    const STATE_NEW     = 'new';
    const STATE_PROCESSED = 'processed';
    const STATE_PENDING = 'pending';
    const STATE_REJECTED = 'rejected';

    /**
     * States in which the payment can't be updated anymore
     * Used for cron.
     */
    const FINALIZED_STATES = [
        self::STATE_REJECTED,
        self::STATE_PROCESSED
    ];

    public function __construct() {
        parent::__construct();
        $this->_init('pensopay/payment');
        $this->_helper = Mage::helper('pensopay');
    }
}