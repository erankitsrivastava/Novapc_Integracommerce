<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action\Context;
use Novapc\Integracommerce\Model\OrderFactory;

class MassDelete extends AbstractOrders
{
    /**
     * @var OrderFactory
     */
    protected $modelOrderFactory;

    public function __construct(Context $context, 
        OrderFactory $modelOrderFactory)
    {
        $this->modelOrderFactory = $modelOrderFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

        $collection = $this->modelOrderFactory->create()->getCollection()
            ->addFieldToFilter('entity_id', ['in' => $ordersIds]);

        foreach ($collection as $order) {
            $order->delete();
        }

        $this->_redirect('*/*/');
    }
}
