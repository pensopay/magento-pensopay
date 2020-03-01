<?php

class PensoPay_Payment_Model_Observer
{
    /**
     * Check for feed updates
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            /** @var PensoPay_Payment_Model_Feed $feedModel */
            $feedModel  = Mage::getModel('pensopay/feed');

            $feedModel->checkUpdate();
        }
    }

    /**
     * Add fraud probability to order grid
     *
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function onBlockHtmlBefore(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (! isset($block)) return;

        if ($block->getType() === 'adminhtml/sales_order_grid') {
            $massAction = $block->getMassactionBlock();
            $massAction->addItem('pensopay_mass_capture', array(
                'label' => 'Capture with PensoPay',
                'url' => $block->getUrl('adminhtml/pensopay/orderMassCapture')
            ));
        }
    }

    /**
     * Disable stock subtraction if configured to do so
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkoutTypeOnepageSaveOrder(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag(PensoPay_Payment_Model_Config::XML_PATH_SUBTRACT_STOCK_ON_PROCESSING)) {
            $quote = $observer->getEvent()->getQuote();
            $quote->setInventoryProcessed(true);
        }
    }

    public function saveOrder($observer)
    {
        $session = Mage::getSingleton('adminhtml/session');

        try {
            $order = $observer->getEvent()->getOrder();
            $payment = $order->getPayment()->getMethodInstance();
            if ($payment instanceof PensoPay_Payment_Model_Payment) {
                $order->setStatus(Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_ORDER_STATUS_BEFOREPAYMENT));
            }

        } catch (Exception $e) {
            $session->addException($e, Mage::helper('pensopay')->__("Can't change status of order", $e->getMessage()));
        }

        return $this;
    }

    public function addViabillPricetag(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        /** @var PensoPay_Payment_Helper_Data $pensopayHelper */
        $pensopayHelper = Mage::helper('pensopay');

        if ($pensopayHelper->isViabillEnabled()) {
            /** @var Mage_Core_Model_Layout $layout */
            $layout = Mage::app()->getLayout();

            /** @var Varien_Object $transport */
            $transport = $observer->getTransport();

            $html = $transport->getHtml();

            if ($block instanceof Mage_Catalog_Block_Product_Price && !$block->getViabillSet()) {
                /** @var Mage_Catalog_Model_Product $product */
                $product = $block->getProduct();

                /** @var Mage_Core_Block_Template $viabillTagBlock */
                $viabillTagBlock = $layout->createBlock('core/template');
                $viabillTagBlock->setTemplate('pensopay/viabill-tag.phtml')->setProduct($product);
                if (in_array('catalog_product_view', $layout->getUpdate()->getHandles())) {
                    $viabillTagBlock->setView('product');
                } else {
                    $viabillTagBlock->setView('list');
                }

                $html .= $viabillTagBlock->toHtml();
                $transport->setHtml($html);
                $block->setViabillSet(true);
            } else if ($block instanceof Mage_Tax_Block_Checkout_Grandtotal) {
                /** @var Mage_Core_Block_Template $viabillTagBlock */
                $viabillTagBlock = $layout->createBlock('core/template');
                $viabillTagBlock->setTemplate('pensopay/viabill-basket.phtml');

                $html .= $viabillTagBlock->toHtml();
                $transport->setHtml($html);
            }
        }
    }

    public function updateVirtualterminalPaymentStatus()
    {
        /** @var PensoPay_Payment_Model_Resource_Payment_Collection $collection */
        $collection = Mage::getResourceModel('pensopay/payment_collection');
        $collection->addFieldToFilter('state', array('nin' => PensoPay_Payment_Model_Payment::FINALIZED_STATES));
        $collection->addFieldToFilter('reference_id', array('notnull' => true));
        $collection->addFieldToFilter('is_virtualterminal', 1);

        /** @var PensoPay_Payment_Model_Payment $payment */
        foreach ($collection as $payment) {
            try {
                $payment->updatePaymentRemote();
            } catch (Exception $e) {
                Mage::log('CRON: Could not update payment remotely. Exception: ' . $e->getMessage(), LOG_WARNING, PensoPay_Payment_Helper_Data::LOG_FILENAME);
            }
        }
        return $this;
    }

    public function checkoutSubmitAllAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethodInstance() instanceof PensoPay_Payment_Model_Method) {
            /** @var PensoPay_Payment_Model_Api $api */
            $api = Mage::getModel('pensopay/api');

            /** @var PensoPay_Payment_Helper_Data $helper */
            $helper = Mage::helper('pensopay');

            $payment = $api->createPayment($order);
            $paymentLink = $api->createPaymentLink($order, $payment->id);

            /** @var PensoPay_Payment_Model_Payment $newPayment */
            $newPayment = Mage::getModel('pensopay/payment');

            $newPayment->importFromRemotePayment($payment);
            $newPayment->setLink($paymentLink);
            $newPayment->setIsVirtualterminal(false);
            $newPayment->save();

            $order->addStatusToHistory($order->getStatus(), $helper->__('Payment link:') . ' ' . $paymentLink, false);
            $order->save();

            /**
             * This is an admin panel order. This means, that the usual payment link will get the user to pay
             * and then redirect him to checkout/page/success, but since no quote will be loaded in the session
             * the user will just see the empty cart page. This way, we redirect the user through our site first
             * to load the quote properly and then send them off to the payment gateway, so he can properly see
             * the success page afterwards.
             */
            $truePaymentLink = $order->getStore()->getUrl('pensopay/payment/email', array('hash' => $newPayment->getHash()));

            $helper->sendEmail(
                $order->getBillingAddress()->getEmail(),
                $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname(),
                $order->getTotalDue(), $order->getOrderCurrencyCode(),
                $truePaymentLink
            );
        }
    }
}