<?php
/** @var $installer Mage_Api2_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('pensopay/payments'), 'acquirer', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 50,
    'comment' => 'Acquirer',
    'nullable' => false,
));

$installer->endSetup();