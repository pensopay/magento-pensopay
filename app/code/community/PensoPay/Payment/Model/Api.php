<?php

class PensoPay_Payment_Model_Api
{
    /**
     * @var string
     */
    protected $baseurl = "https://api.quickpay.net";

    protected function _setupRequest(&$request, $order) {
        $request->setOrderId($order->getIncrementId());
        $request->setCurrency($order->getOrderCurrencyCode());

        if ($textOnStatement = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_TEXT_ON_STATEMENT)) {
            $request->setTextOnStatement($textOnStatement);
        }

        $request->setVariables([
            'order_id' => $order->getId()
        ]);

        if (!$order->getIsVirtualTerminal()) {
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

            //order is arbitrary

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

            //Set shipping information
            $shipping = [];
            $shipping['method'] = 'pick_up_point';
            $shipping['amount'] = (int) ($order->getShippingInclTax() * 100);

            $request->setShipping($shipping);
        } else { //Order is from virtual terminal
            $basket = array(
                array(
                    'qty'        => 1,
                    'item_no'    => 'virtualterminal',
                    'item_name'  => 'Products',
                    'item_price' => $order->getGrandTotal(),
                    'vat_rate'   => 0.25, //TODO
                )
            );
        }

        $request->setBasket($basket);
    }

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

        $this->_setupRequest($request, $order);

        Mage::dispatchEvent('pensopay_create_payment_before', ['request' => $request]);

        //TODO: save payment?
        //Create payment via API
        $payment = $this->request('payments', $request->toArray());

        //Mage::log(var_export($payment, true), null, 'request.log');

        return json_decode($payment);
    }

    /**
     * Update payment
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function updatePayment(Mage_Sales_Model_Order $order)
    {
        $request = new Varien_Object();

        $this->_setupRequest($request, $order);

        if ($order->getIsVirtualTerminal()) {
            $request->setId($order->getReferenceId());
        } //TODO: If order is not virtual terminal, build logic for getting the payment(s?) from the order to update

        Mage::dispatchEvent('pensopay_update_payment_before', ['request' => $request]);

        //TODO: save payment?
        //Update payment via API
        $endpoint = sprintf('payments/%s', $order->getReferenceId());
        $payment = $this->request($endpoint, $request->toArray(), Zend_Http_Client::PATCH, [200]);

        return json_decode($payment);
    }

    public function cancelPayment($paymentId)
    {
        $request = new Varien_Object();
        $request->setId($paymentId);

        Mage::dispatchEvent('pensopay_cancel_payment_before', ['request' => $request]);

        //TODO: save payment?
        //Update payment via API
        $endpoint = sprintf('payments/%s/cancel?synchronized', $paymentId);
        $payment = $this->request($endpoint, $request->toArray(), Zend_Http_Client::POST, [200, 202]);

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
        Mage::log($paymentId, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);

        $request = new Varien_Object();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID));

        if (!$order->getIsVirtualTerminal()) {
            $request->setAmount($order->getTotalDue() * 100);
            $request->setContinueurl($this->getContinueUrl());
            $request->setCancelurl($this->getCancelUrl());
            $request->setCallbackurl($this->getCallbackUrl());
            $request->setLanguage($this->getLanguageFromLocale(Mage::app()->getLocale()->getLocaleCode()));
            $request->setAutocapture(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AUTO_CAPTURE));
            $request->setAutofee(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AUTO_FEE));
        } else { //Virtual Terminal order
            $request->setAmount($order->getGrandTotal() * 100);
            $request->setLanguage($this->getLanguageFromLocale($order->getLocaleCode()));
            $request->setAutocapture($order->getAutocapture());
            $request->setAutofee($order->getAutofee());
        }
        $request->setPaymentMethods(Mage::getModel('pensopay/method')->getPaymentMethods());
//        $request->setBrandingId($brandingId);
//        $request->setGoogleAnalyticsTrackingId($payment->getConfigData('googleanalyticstracking'));
//        $request->setGoogleAnalyticsClientId($payment->getConfigData('googleanalyticsclientid'));
        $request->setCustomerEmail($order->getCustomerEmail() ?: '');

        /** @var PensoPay_Payment_Helper_Checkout $pensopayCheckoutHelper */
        $pensopayCheckoutHelper = Mage::helper('pensopay/checkout');

        if ($pensopayCheckoutHelper->isCheckoutIframe() && !$order->getIsVirtualTerminal()) {
            $request->setFramed(true);
        }

        $endpoint = sprintf('payments/%s/link', $paymentId);
        $link = $this->request($endpoint, $request->toArray(), Zend_Http_Client::PUT);

        Mage::log(var_export($link, true), null, 'request.log');
        return json_decode($link)->url;
    }

    /**
     * Request the deletion of the link for a specific payment.
     *
     * @param $paymentId
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function deletePaymentLink($paymentId)
    {
        Mage::log('Deleting payment link for ' . $paymentId, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);

        $request = new Varien_Object();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID));

        $endpoint = sprintf('payments/%s/link', $paymentId);
        $link = $this->request($endpoint, $request->toArray(), Zend_Http_Client::DELETE, [204]); //No content returned for this

        return json_decode($link)->url;
    }

    public function getPayment($paymentId)
    {
        Mage::log('Updating payment state for ' . $paymentId, null, PensoPay_Payment_Helper_Data::LOG_FILENAME);

        $request = new Varien_Object();
        $request->setAgreementId(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_AGREEMENT_ID));

        $endpoint = sprintf('payments/%s', $paymentId);
        $payment = $this->request($endpoint, $request->toArray(), Zend_Http_Client::GET);

        return json_decode($payment);
    }

    /**
     * Perform a API request
     *
     * @param $resource
     * @param array $data
     * @param string $method
     * @param array $expectedResponseCodes
     * @return string
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function request($resource, $data = [], $method = Zend_Http_Client::POST, $expectedResponseCodes = [200, 201, 202])
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

        if (! in_array($request->getStatus(), $expectedResponseCodes)) {
            Mage::log($request->getBody(), null, PensoPay_Payment_Helper_Data::LOG_FILENAME);
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