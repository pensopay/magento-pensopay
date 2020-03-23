<?php

class PensoPay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    const LOG_FILENAME = 'pensopay.log';

    const XML_PATH_VIABILL_ENABLED = 'payment/pensopay_viabill/active';
    const XML_PATH_VIABILL_SHOPID = 'payment/pensopay_viabill/shop_id';

    const REGISTRY_STORE_KEY = 'penso_store_id';

    public function isViabillEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_VIABILL_ENABLED);
    }

    public function getViabillId()
    {
        return Mage::getStoreConfig(self::XML_PATH_VIABILL_SHOPID);
    }

    public function getStateColorCode($value)
    {
        switch ($value) {
            case PensoPay_Payment_Model_Payment::STATE_INITIAL:
                $colorCode = 'yellow';
                break;
            case PensoPay_Payment_Model_Payment::STATE_NEW:
            case PensoPay_Payment_Model_Payment::STATE_PENDING:
                $colorCode = 'orange';
                break;
            case PensoPay_Payment_Model_Payment::STATE_REJECTED:
                $colorCode = 'red';
                break;
            case PensoPay_Payment_Model_Payment::STATE_PROCESSED:
            default:
                $colorCode = 'green';
        }
        return $colorCode;
    }

   public function getStatusColorCode($value)
   {
       switch ($value) {
           case PensoPay_Payment_Model_Payment::STATUS_WAITING_APPROVAL:
               $colorCode = 'yellow';
               break;
           case PensoPay_Payment_Model_Payment::STATUS_3D_SECURE_REQUIRED:
               $colorCode = 'orange';
               break;
           case PensoPay_Payment_Model_Payment::STATUS_ABORTED:
           case PensoPay_Payment_Model_Payment::STATUS_GATEWAY_ERROR:
           case PensoPay_Payment_Model_Payment::COMMUNICATIONS_ERROR_ACQUIRER:
           case PensoPay_Payment_Model_Payment::STATUS_AUTHORIZATION_EXPIRED:
           case PensoPay_Payment_Model_Payment::STATUS_REJECTED_BY_ACQUIRER:
           case PensoPay_Payment_Model_Payment::STATUS_REQUEST_DATA_ERROR:
               $colorCode = 'red';
               break;
           case PensoPay_Payment_Model_Payment::STATUS_APPROVED:
           default:
               $colorCode = 'green';
       }
       return $colorCode;
   }

    /**
     * @param $email
     * @param $name
     * @param $amount
     * @param $currency
     * @param $link
     * @throws Exception
     */
    public function sendEmail($email, $name, $amount, $currency, $link) {
        $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('pensopay_virtualterminal_link');

        $vars = [
            'currency' => $currency,
            'amount'   => $amount,
            'link'     => $link
        ];

        $salesContact = Mage::getStoreConfig('trans_email/ident_sales');

        if (empty($salesContact)) {
            throw new Exception($this->__('Could not send email. The sales contact is empty.'));
        }

        $emailTemplate->setSenderEmail($salesContact['email']);
        $emailTemplate->setSenderName($salesContact['name']);
        $emailTemplate->setTemplateSubject($this->__('Payment link'));

        if (!$emailTemplate->send($email, $name, $vars)) {
            throw new Exception('Could not send email.');
        }
    }

    /**
     * Sets the registry for the store id - used to make sure we get the right credentials for api use
     * @param $storeId
     * @return PensoPay_Payment_Helper_Data
     * @throws Mage_Core_Exception
     */
    public function setTransactionStoreId($storeId)
    {
        Mage::unregister(self::REGISTRY_STORE_KEY);
        Mage::register(self::REGISTRY_STORE_KEY, $storeId);
        return $this;
    }

    /**
     * Returns 0 if not set
     * @return int
     */
    public function getTransactionStoreId()
    {
        return Mage::registry(self::REGISTRY_STORE_KEY) ?: 0;
    }
}