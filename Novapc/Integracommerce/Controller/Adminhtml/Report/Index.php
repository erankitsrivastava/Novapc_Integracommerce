<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Report;



class Index extends AbstractReport
{
    public function execute() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }
}
