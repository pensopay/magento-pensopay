<?php

class PensoPay_Payment_Model_Config
{
    const XML_PATH_API_KEY = 'payment/pensopay/api_key';
    const XML_PATH_PRIVATE_KEY = 'payment/pensopay/private_key';

    const XML_PATH_TESTMODE_ENABLED = 'payment/pensopay/testmode';
    const XML_PATH_TEXT_ON_STATEMENT = 'payment/pensopay_payment/text_on_statement';
    const XML_PATH_AGREEMENT_ID = 'payment/pensopay/agreement_id';
    const XML_PATH_AUTO_CAPTURE = 'payment/pensopay/auto_capture';
    const XML_PATH_AUTO_FEE = 'payment/pensopay/auto_fee';
    const XML_PATH_CHECKOUT_METHOD = 'payment/pensopay/checkout_method';
    const XML_PATH_ORDER_STATUS_AFTERPAYMENT = 'payment/pensopay/order_status_after_payment';
    const XML_PATH_ORDER_STATUS_BEFOREPAYMENT = 'payment/pensopay/order_status';
    const XML_PATH_BRANDING = 'payment/pensopay/brandingid';
    const XML_PATH_SUBTRACT_STOCK_ON_PROCESSING = 'payment/pensopay/subtract_stock_on_processing';
    const XML_PATH_ANALYTICS_TRACKING = 'payment/pensopay/googleanalyticstracking';
    const XML_PATH_ANALYTICS_CLIENT_ID = 'payment/pensopay/googleanalyticsclientid';
}