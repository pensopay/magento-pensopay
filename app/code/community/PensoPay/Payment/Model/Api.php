<?php

class PensoPay_Payment_Model_Api
{
    /**
     * @var string
     */
    protected $baseurl = "https://api.quickpay.net";

    /**
     * Create payment for order
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function createPayment(Mage_Sales_Model_Order $order)
    {
        $request = new Varien_Object();

        $request->setOrderId($order->getIncrementId());
        $request->setCurrency($order->getOrderCurrency()->ToString());

        if ($textOnStatement = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_TEXT_ON_STATEMENT)) {
            $request->setTextOnStatement($textOnStatement);
        }

        $request->setVariables([
            'order_id' => $order->getId()
        ]);

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        if ($order->getIsVirtual()) {
            //Re-use billing address as shipping address
            $shippingAddress = $billingAddress;
        }

        //Add billing address
        if ($billingAddress) {
            $address = [];

            $address['name'] = $billingAddress->getName();
            $address['street'] = $billingAddress->getStreetFull();
            $address['city'] = $billingAddress->getCity();
            $address['zip_code'] = $billingAddress->getPostcode();
            $address['region'] = $billingAddress->getRegion();
            $address['country_code'] = Mage::app()->getLocale()->getTranslation($billingAddress->getCountryId(), 'Alpha3ToTerritory');
            $address['phone_number'] = $billingAddress->getTelephone();
            $address['email'] = $billingAddress->getEmail();

            $request->setBillingAddress($address);
        }

        //Add shipping_address
        if ($shippingAddress) {
            $address = [];

            $address['name'] = $shippingAddress->getName();
            $address['street'] = $shippingAddress->getStreetFull();
            $address['city'] = $shippingAddress->getCity();
            $address['zip_code'] = $shippingAddress->getPostcode();
            $address['region'] = $shippingAddress->getRegion();
            $address['country_code'] = Mage::app()->getLocale()->getTranslation($shippingAddress->getCountryId(), 'Alpha3ToTerritory');
            $address['phone_number'] = $shippingAddress->getTelephone();
            $address['email'] = $shippingAddress->getEmail();

            $request->setShippingAddress($address);
        }

        $basket = [];

        //Add order items to basket array
        foreach ($order->getAllVisibleItems() as $item) {
            $product = array(
                'qty'        => (int) $item->getQtyOrdered(),
                'item_no'    => $item->getSku(),
                'item_name'  => $item->getName(),
                'item_price' => (int) ($item->getBasePriceInclTax() * 100),
                'vat_rate'   => $item->getTaxPercent() / 100,
            );

            $basket[] = $product;
        }

        $request->setBasket($basket);

        //Set shipping information
        $shipping = [];
        $shipping['method'] = 'pick_up_point';
        $shipping['amount'] = (int) ($order->getShippingInclTax() * 100);

        $request->setShipping($shipping);

        Mage::dispatchEvent('pensopay_create_payment_before', ['request' => $request]);

        //Create payment via API
        $payment = $this->request('payments?synchronized', $request->toArray());

//        Mage::log(var_export($payment, true), null, 'request.log');

//        Mage::throwException('Failed to create payment');

        return json_decode($payment);
    }

    /**
     * Create payment link
     *
     * @param Mage_Sales_Model_Order $order
     * @param $paymentId
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function createPaymentLink(Mage_Sales_Model_Order $order, $paymentId)
    {
        Mage::log($paymentId, null, 'pensopay.log');

        $request = new Varien_Object();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID));
        $request->setAmount($order->getTotalDue() * 100);
        $request->setContinueurl($this->getContinueUrl());
        $request->setCancelurl($this->getCancelUrl());
        $request->setCallbackurl($this->getCallbackUrl());
        $request->setLanguage($this->getLanguageFromLocale(Mage::app()->getLocale()->getLocaleCode()));
        $request->setAutocapture(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AUTO_CAPTURE));
        $request->setAutofee(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AUTO_FEE));
        $request->setPaymentMethods($order->getPayment()->getMethodInstance()->getPaymentMethods());
//        $request->setBrandingId($brandingId);
//        $request->setGoogleAnalyticsTrackingId($payment->getConfigData('googleanalyticstracking'));
//        $request->setGoogleAnalyticsClientId($payment->getConfigData('googleanalyticsclientid'));
        $request->setCustomerEmail($order->getCustomerEmail() ?: '');

        $endpoint = sprintf('payments/%s/link', $paymentId);
        $link = $this->request($endpoint, $request->toArray(), Zend_Http_Client::PUT);

        Mage::log(var_export($link, true), null, 'request.log');
        return json_decode($link)->url;
    }

    /**
     * Perform a API request
     *
     * @param $resource
     * @param array $data
     * @param string $method
     * @return string
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function request($resource, $data = [], $method = Zend_Http_Client::POST)
    {
        $client = new Zend_Http_Client();

        $url = $this->baseurl . "/" . $resource;

        $client->setUri($url);

        $headers = [
            'Authorization'  => 'Basic ' . base64_encode(":" . $this->getApiKey()),
            'Accept-Version' => 'v10',
            'Accept'         => 'application/json',
            'Content-Type'   => 'application/json',
            'Content-Length' => strlen(json_encode($data))
        ];

        $client->setHeaders($headers);
        $client->setMethod($method);
        $client->setRawData(json_encode($data));

        $request = $client->request();

        if (! in_array($request->getStatus(), [200, 201, 202])) {
            Mage::log($request->getBody(), null, 'pensopay.log');
            Mage::throwException($request->getBody());
        }

        return $request->getBody();
    }

    /**
     * Get API key
     *
     * @return mixed
     */
    private function getApiKey()
    {
        return Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_API_KEY);
    }

    /**
     * Get language string from locale code
     *
     * @param $locale
     * @return mixed
     */
    private function getLanguageFromLocale($locale)
    {
        $languageMap = array(
            'nb' => 'no',
            'nn' => 'no'
        );

        $parts = explode('_', $locale);
        $language = $parts[0];

        if (isset($languageMap[$language])) {
            return $languageMap[$language];
        }

        return $language;
    }

    /**
     * Get continue url
     *
     * @return string
     */
    private function getContinueUrl()
    {
        return Mage::getUrl('pensopay/payment/success');
    }

    /**
     * Get cancel url
     *
     * @return string
     */
    private function getCancelUrl()
    {
        return Mage::getUrl('pensopay/payment/cancel');
    }

    /**
     * Get callback url
     *
     * @return string
     */
    private function getCallbackUrl()
    {
        return Mage::getUrl('pensopay/payment/callback');
    }
}