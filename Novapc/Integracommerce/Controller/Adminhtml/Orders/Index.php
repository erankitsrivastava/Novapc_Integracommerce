<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Orders;



class Index extends AbstractOrders
{
    public function execute() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }
}
