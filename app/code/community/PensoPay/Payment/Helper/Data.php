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

    public function convertCountryAlphas3To2($code = 'DK') {
        $countries = json_decode('{"AFG":"AF","ALA":"AX","ALB":"AL","DZA":"DZ","ASM":"AS","AND":"AD","AGO":"AO","AIA":"AI","ATA":"AQ","ATG":"AG","ARG":"AR","ARM":"AM","ABW":"AW","AUS":"AU","AUT":"AT","AZE":"AZ","BHS":"BS","BHR":"BH","BGD":"BD","BRB":"BB","BLR":"BY","BEL":"BE","BLZ":"BZ","BEN":"BJ","BMU":"BM","BTN":"BT","BOL":"BO","BIH":"BA","BWA":"BW","BVT":"BV","BRA":"BR","VGB":"VG","IOT":"IO","BRN":"BN","BGR":"BG","BFA":"BF","BDI":"BI","KHM":"KH","CMR":"CM","CAN":"CA","CPV":"CV","CYM":"KY","CAF":"CF","TCD":"TD","CHL":"CL","CHN":"CN","HKG":"HK","MAC":"MO","CXR":"CX","CCK":"CC","COL":"CO","COM":"KM","COG":"CG","COD":"CD","COK":"CK","CRI":"CR","CIV":"CI","HRV":"HR","CUB":"CU","CYP":"CY","CZE":"CZ","DNK":"DK","DKK":"DK","DJI":"DJ","DMA":"DM","DOM":"DO","ECU":"EC","Sal":"El","GNQ":"GQ","ERI":"ER","EST":"EE","ETH":"ET","FLK":"FK","FRO":"FO","FJI":"FJ","FIN":"FI","FRA":"FR","GUF":"GF","PYF":"PF","ATF":"TF","GAB":"GA","GMB":"GM","GEO":"GE","DEU":"DE","GHA":"GH","GIB":"GI","GRC":"GR","GRL":"GL","GRD":"GD","GLP":"GP","GUM":"GU","GTM":"GT","GGY":"GG","GIN":"GN","GNB":"GW","GUY":"GY","HTI":"HT","HMD":"HM","VAT":"VA","HND":"HN","HUN":"HU","ISL":"IS","IND":"IN","IDN":"ID","IRN":"IR","IRQ":"IQ","IRL":"IE","IMN":"IM","ISR":"IL","ITA":"IT","JAM":"JM","JPN":"JP","JEY":"JE","JOR":"JO","KAZ":"KZ","KEN":"KE","KIR":"KI","PRK":"KP","KOR":"KR","KWT":"KW","KGZ":"KG","LAO":"LA","LVA":"LV","LBN":"LB","LSO":"LS","LBR":"LR","LBY":"LY","LIE":"LI","LTU":"LT","LUX":"LU","MKD":"MK","MDG":"MG","MWI":"MW","MYS":"MY","MDV":"MV","MLI":"ML","MLT":"MT","MHL":"MH","MTQ":"MQ","MRT":"MR","MUS":"MU","MYT":"YT","MEX":"MX","FSM":"FM","MDA":"MD","MCO":"MC","MNG":"MN","MNE":"ME","MSR":"MS","MAR":"MA","MOZ":"MZ","MMR":"MM","NAM":"NA","NRU":"NR","NPL":"NP","NLD":"NL","ANT":"AN","NCL":"NC","NZL":"NZ","NIC":"NI","NER":"NE","NGA":"NG","NIU":"NU","NFK":"NF","MNP":"MP","NOR":"NO","OMN":"OM","PAK":"PK","PLW":"PW","PSE":"PS","PAN":"PA","PNG":"PG","PRY":"PY","PER":"PE","PHL":"PH","PCN":"PN","POL":"PL","PRT":"PT","PRI":"PR","QAT":"QA","REU":"RE","ROU":"RO","RUS":"RU","RWA":"RW","BLM":"BL","SHN":"SH","KNA":"KN","LCA":"LC","MAF":"MF","SPM":"PM","VCT":"VC","WSM":"WS","SMR":"SM","STP":"ST","SAU":"SA","SEN":"SN","SRB":"RS","SYC":"SC","SLE":"SL","SGP":"SG","SVK":"SK","SVN":"SI","SLB":"SB","SOM":"SO","ZAF":"ZA","SGS":"GS","SSD":"SS","ESP":"ES","LKA":"LK","SDN":"SD","SUR":"SR","SJM":"SJ","SWZ":"SZ","SWE":"SE","CHE":"CH","SYR":"SY","TWN":"TW","TJK":"TJ","TZA":"TZ","THA":"TH","TLS":"TL","TGO":"TG","TKL":"TK","TON":"TO","TTO":"TT","TUN":"TN","TUR":"TR","TKM":"TM","TCA":"TC","TUV":"TV","UGA":"UG","UKR":"UA","ARE":"AE","GBR":"GB","USA":"US","UMI":"UM","URY":"UY","UZB":"UZ","VUT":"VU","VEN":"VE","VNM":"VN","VIR":"VI","WLF":"WF","ESH":"EH","YEM":"YE","ZMB":"ZM","ZWE":"ZW","GBP":"GB","RUB":"RU","NOK":"NO"}',true);

        if(!isset($countries[$code])){
            $defaultCountry = Mage::getStoreConfig('general/country/default', Mage::app()->getStore()->getStoreId());
            return $defaultCountry;
        } else {
            return $countries[$code];
        }
    }
}