<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class Grid extends AbstractOrders
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $viewLayoutFactory;

    public function __construct(Context $context, 
        RawFactory $resultRawFactory, 
        LayoutFactory $viewLayoutFactory)
    {
        $this->resultRawFactory = $resultRawFactory;
        $this->viewLayoutFactory = $viewLayoutFactory;

        parent::__construct($context);
    }

    /**
     * Product grid for AJAX request
     */
    public function execute()
    {
        $this->loadLayout();
        $this->resultRawFactory->create()->setContents(
            $this->viewLayoutFactory->create()->createBlock('')->toHtml()
        );
    }
}
