<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    /** @var PensoPay_Payment_Helper_Data $_helper */
    protected $_helper;

    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->setId('pensopay_virtualterminal_payment');
        $this->_helper = Mage::helper('pensopay');
        $this->setTitle($this->_helper->__('New Payment'));
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $paymentFieldset = $form->addFieldset('payment_fieldset', array('legend'    => $this->helper('pensopay')->__('Payment Information')));

        $orderId = $paymentFieldset->addField('order_id', 'text', array(
            'label'                 => $this->_helper->__('Order ID'),
            'title'                 => $this->_helper->__('Order ID'),
            'name'                  => 'order_id',
            'class'                 => 'validate-length maximum-length-20 minimum-length-4',
            'required'              => true
        ));

        $amount = $paymentFieldset->addField('amount', 'text', array(
            'label'                 => $this->_helper->__('Amount'),
            'title'                 => $this->_helper->__('Amount'),
            'name'                  => 'amount',
            'required'              => true,
            'class'                 => 'validate-number'
        ));

        $countrySelect = $paymentFieldset->addField('locale_code', 'select', array(
            'label'                 => $this->_helper->__('Language'),
            'title'                 => $this->_helper->__('Language'),
            'name'                  => 'locale_code',
            'required'              => true,
            'options'               => $this->_getAllLocales(),
            'value'                 => 'da_DK',
            'after_element_html'    => sprintf('<p style="font-size:11px">%s</p>', $this->_helper->__('Used as payment language'))
        ));

        $currencySelect = $paymentFieldset->addField('currency_code', 'select', array(
            'label'                 => $this->_helper->__('Currency'),
            'title'                 => $this->_helper->__('Currency'),
            'name'                  => 'currency_code',
            'required'              => true,
            'options'               => $this->_getAllCurrencies(),
            'value'                 => 'DKK',
            'after_element_html'    => sprintf('<p style="font-size:11px">%s</p>', $this->_helper->__('Payment currency code'))
        ));

        $captureSelect = $paymentFieldset->addField('autocapture', 'select', array(
            'label'                 => $this->_helper->__('Autocapture'),
            'title'                 => $this->_helper->__('Autocapture'),
            'name'                  => 'autocapture',
            'required'              => true,
            'options'               => array(0 => $this->_helper->__('No'), 1 => $this->_helper->__('Yes')),
            'value'                 => Mage::getStoreConfigFlag('payment/pensopay/auto_capture'),
            'after_element_html'    => sprintf('<p style="font-size:11px">%s</p>', $this->_helper->__('Capture payment immediately.'))
        ));

        $autofeeSelect = $paymentFieldset->addField('autofee', 'select', array(
            'label'                 => $this->_helper->__('Autofee'),
            'title'                 => $this->_helper->__('Autofee'),
            'name'                  => 'autofee',
            'required'              => true,
            'options'               => array(0 => $this->_helper->__('No'), 1 => $this->_helper->__('Yes')),
            'value'                 => Mage::getStoreConfigFlag('payment/pensopay/auto_fee'),
            'after_element_html'    => sprintf('<p style="font-size:11px">%s</p>', $this->_helper->__('Charge transaction fee.'))
        ));

        $customerFieldset = $form->addFieldset('customer_fieldset', array('legend'    => $this->_helper->__('Customer Information')));

        $name = $customerFieldset->addField('customer_name', 'text', array(
            'label'                 => $this->_helper->__('Name'),
            'title'                 => $this->_helper->__('Name'),
            'name'                  => 'customer_name',
            'required'              => false
        ));

        $email = $customerFieldset->addField('customer_email', 'text', array(
            'label'                 => $this->_helper->__('Email'),
            'title'                 => $this->_helper->__('Email'),
            'name'                  => 'customer_email',
            'class'                 => 'validate-email',
            'required'              => false
        ));

        $street = $customerFieldset->addField('customer_street', 'text', array(
            'label'                 => $this->_helper->__('Street'),
            'title'                 => $this->_helper->__('Street'),
            'name'                  => 'customer_street',
            'required'              => false,
        ));

        $zipCode = $customerFieldset->addField('customer_zipcode', 'text', array(
            'label'                 => $this->_helper->__('Zip Code'),
            'title'                 => $this->_helper->__('Zip Code'),
            'name'                  => 'customer_zipcode',
            'required'              => false
        ));

        $city = $customerFieldset->addField('customer_city', 'text', array(
            'label'                 => $this->_helper->__('City'),
            'title'                 => $this->_helper->__('City'),
            'name'                  => 'customer_zipcode',
            'required'              => false
        ));

        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setMethod('post');
        $form->setAction($this->getUrl('*/*/save'));

        $id = Mage::app()->getRequest()->getParam('id');
        if ($id) {
            $payment = Mage::getModel('pensopay/payment')->load($id);
            if ($payment->getId()) {
                $form->addValues($payment->getData());
                $form->getElement('order_id')->setDisabled(true);
                $form->getElement('currency_code')->setDisabled(true);

                if ($payment->getState() !== PensoPay_Payment_Model_Payment::STATE_INITIAL) {
                    foreach ($customerFieldset->getElements() as $element) {
                        $element->setDisabled(true);
                    }

                    foreach ($paymentFieldset->getElements() as $element) {
                        $element->setDisabled(true);
                    }
                }

                $incId = $paymentFieldset->addField('id', 'hidden', array(
                    'label'                 => $this->_helper->__('Increment ID'),
                    'title'                 => $this->_helper->__('Increment ID'),
                    'name'                  => 'id',
                    'value'                 => $id,
                    'required'              => true
                ), 'order_id');

                $paymentFieldset->addType('payment_state', 'PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit_Renderer_Status');
                $paymentState = $paymentFieldset->addField('state', 'payment_state', array(
                    'label'                 => $this->_helper->__('State'),
                    'title'                 => $this->_helper->__('State'),
                    'name'                  => 'state',
                    'required'              => true,
                    'value'                 => $payment->getId()
                ), 'id');

                $transactionLogFieldset = $form->addFieldset('log_fieldset', array('legend'    => $this->_helper->__('Transaction Log')));

                $transactionLogFieldset->addType('operations', 'PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit_Renderer_Operations');
                $transactionLog = $transactionLogFieldset->addField('transaction_log', 'operations', array(
                    'name'                  => 'operations',
                    'value'                 => $payment->getOperations(),
                    'required'              => false
                ));
            }
        }

        $this->setForm($form);
    }


    protected function _getAllLocales()
    {
        $options = Mage::app()->getLocale()->getOptionLocales();
        $locales = array();
        foreach ($options as $option) {
            $locales[$option['value']] = $option['label'];
        }
        return $locales;
    }

    protected function _getAllCurrencies()
    {
        $currencyModel = Mage::getModel('adminhtml/system_config_source_currency');
        $options = $currencyModel->toOptionArray(false);
        $currencies = array();
        foreach ($options as $option) {
            $currencies[$option['value']] = sprintf('%s - %s', $option['value'], $option['label']);
        }
        return $currencies;
    }

    protected function _getAllIso3Countries()
    {
        $countryCollection = Mage::getResourceModel('directory/country_collection');
        $countries = array();
        foreach ($countryCollection as $country) {
            $countries[$country->getIso2Code()] = $country->getName();
        }
        return $countries;
    }
}