<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Integration;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\Date;
use Novapc\Integracommerce\Helper\IntegrationData;
use Novapc\Integracommerce\Model\IntegrationFactory;
use Novapc\Integracommerce\Model\UpdateFactory;

class MassUpdate extends AbstractIntegration
{
    /**
     * @var IntegrationFactory
     */
    protected $modelIntegrationFactory;

    /**
     * @var Date
     */
    protected $modelDate;

    /**
     * @var UpdateFactory
     */
    protected $modelUpdateFactory;

    public function __construct(Context $context, 
        IntegrationFactory $modelIntegrationFactory, 
        Date $modelDate, 
        UpdateFactory $modelUpdateFactory)
    {
        $this->modelIntegrationFactory = $modelIntegrationFactory;
        $this->modelDate = $modelDate;
        $this->modelUpdateFactory = $modelUpdateFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $productModel = $this->modelIntegrationFactory->create()->load('Product Update', 'integra_model');

        $message = IntegrationData::checkRequest($productModel, 'put');

        if (isset($message)) {
            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addError(__($message));
            $productModel->setAvailable(0);
            $productModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requestedDay = $productModel->getRequestedDay();
            $requestedWeek = $productModel->getRequestedWeek();
            $requestedInitial = $productModel->getInitialHour();
            $requestedHour = IntegrationData::forceUpdate($alreadyRequested);

            if ($alreadyRequested == $requestedHour) {
                $requested = 0;
                $requestedHour = 0;
            } else {
                $requested = $requestedHour - $alreadyRequested;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = $this->modelDate->date('Y-m-d H:i:s');

            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requestedHour);
            $productModel->setRequestedDay($requestedDay);
            $productModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $productModel->setInitialHour($requestTime);
            }

            $productModel->save();

            $queueCollection = $this->modelUpdateFactory->create()->getCollection();
            $queueCount = $queueCollection->getSize();

            if ($queueCount >= 1) {
                ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addWarning(__("Existem itens no Relatório, por favor, verifique para mais informações."));
            } else {
                ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addSuccess(__("Synchronization completed."));
            }

            $this->_redirect('*/*/');
        }
    }
}
