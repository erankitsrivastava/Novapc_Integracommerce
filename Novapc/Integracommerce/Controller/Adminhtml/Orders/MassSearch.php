<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Orders;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\Date;
use Magento\Sales\Model\OrderFactory as ModelOrderFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Helper\Data as HelperData;
use Novapc\Integracommerce\Helper\IntegrationData;
use Novapc\Integracommerce\Helper\OrderData;
use Novapc\Integracommerce\Model\OrderFactory;
use Novapc\Integracommerce\Model\QueueFactory;

class MassSearch extends AbstractOrders
{
    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;

    /**
     * @var QueueFactory
     */
    protected $modelQueueFactory;

    /**
     * @var OrderFactory
     */
    protected $modelOrderFactory;

    /**
     * @var ModelOrderFactory
     */
    protected $salesModelOrderFactory;

    /**
     * @var Date
     */
    protected $modelDate;

    public function __construct(Context $context, 
        ScopeConfigInterface $storeConfig, 
        StoreManagerInterface $modelStoreManagerInterface, 
        QueueFactory $modelQueueFactory, 
        OrderFactory $modelOrderFactory, 
        ModelOrderFactory $salesModelOrderFactory, 
        Date $modelDate)
    {
        $this->storeConfig = $storeConfig;
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->modelQueueFactory = $modelQueueFactory;
        $this->modelOrderFactory = $modelOrderFactory;
        $this->salesModelOrderFactory = $salesModelOrderFactory;
        $this->modelDate = $modelDate;

        parent::__construct($context);
    }

    public function execute()
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $orderModel = $this->modelQueueFactory->create()->load('Orderid', 'integra_model');
        $message = IntegrationData::checkRequest($orderModel, 'getid');

        if (isset($message)) {
            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addError(__($message));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            $requestedHour = $orderModel->getRequestedHour();
            $requestedDay = $orderModel->getRequestedDay();
            $requestedWeek = $orderModel->getRequestedWeek();
            $requestedInitial = $orderModel->getInitialHour();

            $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

            $requested = 0;
            foreach ($ordersIds as $id) {
                $checkOrder = $this->modelOrderFactory->create()->load($id, 'entity_id');
                $magentoId = $checkOrder->getData('magento_order_id');

                if (!empty($magentoId)) {
                    $tryOrder = $this->salesModelOrderFactory->create()->load($magentoId);
                    $checkIncrementId = $tryOrder->getIncrementId();

                    if ($checkIncrementId || !empty($checkIncrementId)) {
                        continue;
                    }
                }

                $integraId = $checkOrder->getIntegraId();

                $url = "https://" . $environment . ".integracommerce.com.br/api/Order/" . $integraId;

                $return = HelperData::callCurl("GET", $url, null);

                $requested++;
                if ($return['OrderStatus'] !== 'APPROVED' && $return['OrderStatus'] !== 'PROCESSING') {
                    continue;
                }

                OrderData::processingOrder($return);

                sleep(2);
            }

            $requestedHour = $requestedHour + $requested;
            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = $this->modelDate->date('Y-m-d H:i:s');

            $orderModel->setStatus($requestTime);
            $orderModel->setRequestedHour($requestedHour);
            $orderModel->setRequestedDay($requestedDay);
            $orderModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $orderModel->setInitialHour($requestTime);
            }

            $orderModel->save();

            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addSuccess(__('Sincronização Completa!'));
            $this->_redirect('*/*/');
        }
    }
}
