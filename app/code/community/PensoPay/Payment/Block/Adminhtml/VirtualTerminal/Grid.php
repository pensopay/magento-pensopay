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
        $collection->addFieldToFilter('is_virtualterminal', 1);
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
            'index' => 'amount',
            'width' => '50px',
            'renderer'  => 'PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Amount',
        ));

        $this->addColumn('customer_name', array(
            'header' => $this->__('Customer Name'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'customer_name'
        ));

        $this->addColumn('customer_email', array(
            'header' => $this->__('Customer Email'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'customer_email'
        ));

        $this->addColumn('state', array(
            'header' => $this->__('State'),
            'align' => 'center',
            'width' => '50px',
            'index' => 'state',
            'renderer'  => 'PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Status',
        ));

        $this->addColumn('created_at', array(
            'index' => 'created_at',
            'align' => 'center',
            'type' => 'datetime',
            'header' => $this->__('Created At'),
            'width' => '250px'
        ));

        $this->addColumn('updated_at', array(
            'index' => 'updated_at',
            'align' => 'center',
            'type' => 'datetime',
            'header' => $this->__("Updated At"),
            'width' => '250px'
        ));

        $this->addColumn('link', array(
            'index' => 'link',
            'align' => 'center',
            'width' => '20px',
            'header' => $this->__('Payment Link'),
            'renderer'  => 'PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Link'
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($item)
    {
        return $this->getUrl('*/*/edit', array('id' => $item->getId()));
    }
}