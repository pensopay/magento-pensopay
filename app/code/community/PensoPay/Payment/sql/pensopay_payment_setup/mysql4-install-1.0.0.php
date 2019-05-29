<?php

$installer = $this;
$installer->startSetup();

$_tblPayments = $installer->getTable('pensopay/payments');

$tblPayments = $installer->getConnection()
    ->newTable($_tblPayments)
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => false,
        'unsigned' => true,
        'primary'  => true,
        'identity' => true
    ), 'Increment ID')
    ->addColumn('reference_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable' => false,
        'unsigned' => true
    ), 'Reference ID')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array('nullable' => false, 'unsigned' => true), 'Order ID')
    ->addColumn('accepted', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Accepted by provider')
    ->addColumn('currency', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Currency')
    ->addColumn('state', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'State')
    ->addColumn('link', Varien_Db_Ddl_Table::TYPE_TEXT, 65534, array('nullable' => false), 'Payment Link')
    ->addColumn('amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, 255, array('nullable' => false), 'Amount')
    ->addColumn('locale_code', Varien_Db_Ddl_Table::TYPE_TEXT, 65534, array('nullable' => false), 'Language')
    ->addColumn('autocapture', Varien_Db_Ddl_Table::TYPE_BOOLEAN, 1, array('nullable' => false), 'Autocapture')

    ->addColumn('customer_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Customer Name')
    ->addColumn('customer_email', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Customer Email')
    ->addColumn('customer_street', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Customer Street')
    ->addColumn('customer_zipcode', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Customer Zipcode')
    ->addColumn('customer_city', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Customer City')

    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(), 'Created At')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(), 'Updated At')
    ->addIndex(
        $installer->getIdxName('pensopay/payments', array('id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->setComment('PensoPay Virtual Terminal Payments');
$installer->getConnection()->createTable($tblPayments);

$installer->endSetup();
