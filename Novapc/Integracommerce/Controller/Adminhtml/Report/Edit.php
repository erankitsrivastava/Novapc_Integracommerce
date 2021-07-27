<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Novapc\Integracommerce\Model\UpdateFactory;

class Edit extends AbstractReport
{
    /**
     * @var UpdateFactory
     */
    protected $modelUpdateFactory;

    /**
     * @var Registry
     */
    protected $frameworkRegistry;

    /**
     * @var LayoutFactory
     */
    protected $viewLayoutFactory;

    public function __construct(Context $context, 
        UpdateFactory $modelUpdateFactory, 
        Registry $frameworkRegistry, 
        LayoutFactory $viewLayoutFactory)
    {
        $this->modelUpdateFactory = $modelUpdateFactory;
        $this->frameworkRegistry = $frameworkRegistry;
        $this->viewLayoutFactory = $viewLayoutFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $productQueueId = $this->getRequest()->getParam('id');
        $queueModel = $this->modelUpdateFactory->create()->load($productQueueId, 'product_id');
        $this->frameworkRegistry->register('report_data', $queueModel);
        $this->loadLayout();
        $this->_addContent(
            $this->viewLayoutFactory->create()
            ->createBlock('')
        )
            ->_addLeft(
                $this->viewLayoutFactory->create()
                ->createBlock('')
            );
        $this->renderLayout();
    }
}
