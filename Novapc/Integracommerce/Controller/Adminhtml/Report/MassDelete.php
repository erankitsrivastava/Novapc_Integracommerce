<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Novapc\Integracommerce\Model\UpdateFactory;

class MassDelete extends AbstractReport
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

    protected function massDeleteAction()
    {
        $itensIds = (array) $this->getRequest()->getParam('integracommerce_report');

        $collection = $this->modelUpdateFactory->create()->getCollection()
            ->addFieldToFilter('entity_id', ['in' => $itensIds]);

        foreach ($collection as $item) {
            $item->delete();
        }

        $this->_redirect('*/*/');
    }
}
