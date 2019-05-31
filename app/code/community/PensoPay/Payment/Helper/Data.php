<?php

class PensoPay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    const LOG_FILENAME = 'pensopay.log';

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
}