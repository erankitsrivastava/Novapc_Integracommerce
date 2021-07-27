<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Novapc\Integracommerce\Model\UpdateFactory;

class Delete extends AbstractReport
{
    /**
     * @var UpdateFactory
     */
    protected $modelUpdateFactory;

    public function __construct(Context $context, 
        UpdateFactory $modelUpdateFactory)
    {
        $this->modelUpdateFactory = $modelUpdateFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try
            {
                $errorQueue = $this->modelUpdateFactory->create()->load($this->getRequest()->getParam('id'), 'product_id');
                $errorQueue->delete();
                $this->messageManager->addSuccess('Item excluido com sucesso.');
                $this->_redirect('*/*/');
            }
            catch (\Exception $e)
            {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }

        $this->_redirect('*/*/');
    }
}
