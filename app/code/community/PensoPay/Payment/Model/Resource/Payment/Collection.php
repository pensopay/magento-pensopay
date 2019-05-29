<?php

class PensoPay_Payment_Model_Resource_Payment_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    public function _construct() {
        parent::_construct();
        $this->_init('pensopay/payment');
    }
}