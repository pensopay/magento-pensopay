<?php

$installer = $this;
$installer->startSetup();

$_tblPayments = $installer->getTable('pensopay/payments');
$installer->getConnection()->addColumn($_tblPayments, 'operations', 'TEXT NOT NULL DEFAULT ""');

$installer->endSetup();
