<?php

class PensoPay_Payment_Model_Feed extends Mage_AdminNotification_Model_Feed
{
    /**
     * @return string
     */
    public function getFeedUrl()
    {
        return 'https://pensopay.com/magento-feed.xml';
    }

    /**
     * Get last update timestamp
     */
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('pensopay_feed_notifications_lastcheck');
    }

    /**
     * Set last update timestamp
     *
     * @return $this|Mage_AdminNotification_Model_Feed
     */
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'pensopay_feed_notifications_lastcheck');

        return $this;
    }
}