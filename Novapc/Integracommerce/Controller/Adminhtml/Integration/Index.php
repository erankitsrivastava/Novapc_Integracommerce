<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Integration;



class Index extends AbstractIntegration
{
    public function execute() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    }
}
