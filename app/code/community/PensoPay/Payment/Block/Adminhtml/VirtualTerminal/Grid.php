<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        $this->setDefaultSort('id');
        $this->setId('pensopay_virtualterminal_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        /** @var PensoPay_Payment_Model_Resource_Payment_Collection $collection */
        $collection = Mage::getResourceModel('pensopay/payment_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => $this->__('ID'),
            'align' => 'center',
            'width' => '10px',
            'index' => 'id'
        ));

        $this->addColumn('order_id', array(
            'header' => $this->__('Order ID'),
            'align' => 'center',
            'width' => '50px',
            'index' => 'order_id'
        ));

        $this->addColumn('amount', array(
            'header' => $this->__('Amount'),
            'align' => 'center',
            'index' => 'amount'
        ));

        $this->addColumn('customer_email', array(
            'header' => $this->__('Customer Email'),
            'align' => 'center',
            'width' => '50px',
            'index' => 'customer_email'
        ));

        $this->addColumn('state', array(
            'header' => $this->__('State'),
            'align' => 'center',
            'width' => '50px',
            'index' => 'state'
        ));

        $this->addColumn('created_at', array(
            'index' => 'created_at',
            'align' => 'center',
            'type' => 'datetime',
            'header' => $this->__('Created At')
        ));

        $this->addColumn('updated_at', array(
            'index' => 'updated_at',
            'align' => 'center',
            'type' => 'datetime',
            'header' => $this->__("Updated At")
        ));

        //link - edit
//        $this->addColumn('type', array(
//            'index' => 'type',
//            'align' => 'center',
//            'header' => $this->__('Type')
//        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($item)
    {
        return $this->getUrl('*/*/edit', array('id' => $item->getId()));
    }
}