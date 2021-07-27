<?php

namespace Novapc\Integracommerce\Controller\Adminhtml\Integration;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\Date;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Helper\IntegrationData;
use Novapc\Integracommerce\Model\IntegrationFactory;

class MassCategory extends AbstractIntegration
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
     * @var IntegrationFactory
     */
    protected $modelIntegrationFactory;

    /**
     * @var Date
     */
    protected $modelDate;

    public function __construct(Context $context, 
        ScopeConfigInterface $storeConfig, 
        StoreManagerInterface $modelStoreManagerInterface, 
        IntegrationFactory $modelIntegrationFactory, 
        Date $modelDate)
    {
        $this->storeConfig = $storeConfig;
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->modelIntegrationFactory = $modelIntegrationFactory;
        $this->modelDate = $modelDate;

        parent::__construct($context);
    }

    public function execute()
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $categoryModel = $this->modelIntegrationFactory->create()->load('Category', 'integra_model');

        $message = IntegrationData::checkRequest($categoryModel, 'post');

        if (isset($message)) {
            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addError(__($message));
            $categoryModel->setAvailable(0);
            $categoryModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $categoryModel->getRequestedHour();
            $requestedDay = $categoryModel->getRequestedDay();
            $requestedWeek = $categoryModel->getRequestedWeek();
            $requestedInitial = $categoryModel->getInitialHour();
            $requestedHour = IntegrationData::integrateCategory($alreadyRequested);

            if ($alreadyRequested == $requestedHour) {
                $requested = 0;
                $requestedHour = 0;
            } else {
                $requested = $requestedHour - $alreadyRequested;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = $this->modelDate->date('Y-m-d H:i:s');

            $categoryModel->setStatus($requestTime);
            $categoryModel->setRequestedHour($requestedHour);
            $categoryModel->setRequestedDay($requestedDay);
            $categoryModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $categoryModel->setInitialHour($requestTime);
            }

            $categoryModel->save();

            ObjectManager::getInstance()->get('Magento\Framework\Model\Session')->addSuccess(__("Synchronization completed."));

            $this->_redirect('*/*/');
        }
    }
}
