<?php
/**
 * PHP version 5
 * Novapc Integracommerce
 *
 * @category  Magento
 * @package   Novapc_Integracommerce
 * @author    Novapc <novapc@novapc.com.br>
 * @copyright 2017 Integracommerce
 * @license   https://opensource.org/licenses/osl-3.0.php PHP License 3.0
 * @version   GIT: 1.0
 * @link      https://github.com/integracommerce/modulo-magento
 */

namespace Novapc\Integracommerce\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Model\Date;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Helper\Data as HelperData;
use Novapc\Integracommerce\Helper\IntegrationData;
use Novapc\Integracommerce\Helper\OrderData;

class Observer
{
    /**
     * @var ProductFactory
     */
    protected $modelProductFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;

    /**
     * @var UpdateFactory
     */
    protected $modelUpdateFactory;

    /**
     * @var QueueFactory
     */
    protected $modelQueueFactory;

    /**
     * @var IntegrationFactory
     */
    protected $modelIntegrationFactory;

    /**
     * @var Date
     */
    protected $modelDate;

    public function __construct(ProductFactory $modelProductFactory, 
        ScopeConfigInterface $storeConfig, 
        StoreManagerInterface $modelStoreManagerInterface, 
        UpdateFactory $modelUpdateFactory, 
        QueueFactory $modelQueueFactory, 
        IntegrationFactory $modelIntegrationFactory, 
        Date $modelDate)
    {
        $this->modelProductFactory = $modelProductFactory;
        $this->storeConfig = $storeConfig;
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->modelUpdateFactory = $modelUpdateFactory;
        $this->modelQueueFactory = $modelQueueFactory;
        $this->modelIntegrationFactory = $modelIntegrationFactory;
        $this->modelDate = $modelDate;

    }


    public function stockQueue(EventObserver $event)
    {
        $item = $event->getEvent()->getItem();
        $product = $this->modelProductFactory->create()->load($item->getId());

        $exportType = $this->storeConfig->getValue('integracommerce/general/export_type', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if (($exportType == 1 && $product->getData('integracommerce_sync') == 0) && $product->getData('integracommerce_active') == 0) {
            return;
        } else {
            $insertQueue = $this->modelUpdateFactory->create()->load($product->getId(), 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
                $insertQueue = $this->modelUpdateFactory->create();
                $insertQueue->setProductId($product->getId());
                $insertQueue->save();
            }
        }
    }  

    public function orderQueue(EventObserver $event)
    {
        $order = $event->getEvent()->getOrder();
        $exportType = $this->storeConfig->getValue('integracommerce/general/export_type', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        foreach ($order->getAllItems() as $item) {
            $product = $this->modelProductFactory->create()->load($item->getProductId());
            if (($exportType == 1 && $product->getData('integracommerce_sync') == 0) && $product->getData('integracommerce_active') == 0) {
                continue;
            } else {
                $insertQueue = $this->modelUpdateFactory->create()->load($product->getId(), 'product_id');
                $queueProductId = $insertQueue->getProductId();
                if (!$queueProductId || empty($queueProductId)) {
                    $insertQueue = $this->modelUpdateFactory->create();
                    $insertQueue->setProductId($product->getId());
                    $insertQueue->save();
                }
            }
        }

        $integracommerceId = $order->getData('integracommerce_id');
        if (!empty($integracommerceId)) {
            $responseStatus = OrderData::updateOrder($order);
        }
    }

    public function massproductQueue(EventObserver $event)
    {
        $attributesData = $event->getEvent()->getAttributesData();
        $productIds     = $event->getEvent()->getProductIds();

        $count = count($attributesData);
        if ($count == 1 && array_key_exists("integracommerce_active", $attributesData)) {
            return;
        }

        foreach ($productIds as $id) {
            $product = $this->modelProductFactory->create()->load($id);

            if (array_key_exists("integracommerce_active", $attributesData)) {
                $activate = $attributesData['integracommerce_active'];
            }

            //VERIFICANDO SE O ATRIBUTO DE CONTROLE SERA ALTERADO PARA NAO
            //POIS MESMO SENDO EVENTO AFTER NAO RETORNA APOS ATUALIZACAO
            if (isset($activate) && $activate == 0) {
                continue;
            }

            //VERIFICANDO SE O PRODUTO JA FOI SINCRONIZADO
            if (empty($activate) && $product->getData('integracommerce_active') == 0) {
                continue;
            }

            $insertQueue = $this->modelUpdateFactory->create()->load($id, 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
                $insertQueue = $this->modelUpdateFactory->create();
                $insertQueue->setProductId($id);
                $insertQueue->save();
            }
        }
    }

    public function productQueue(EventObserver $event)
    {
        $product = $event->getProduct();

        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        $exportType = $this->storeConfig->getValue('integracommerce/general/export_type', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if (($exportType == 1 && $product->getData('integracommerce_sync') == 0) && $product->getData('integracommerce_active') == 0) {
           return;
        } else {
           $insertQueue = $this->modelUpdateFactory->create()->load($product->getId(), 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
               $insertQueue = $this->modelUpdateFactory->create();
               $insertQueue->setProductId($product->getId());
               $insertQueue->save();
            }
        }
    }  

    public function getOrders()
    {
        $orderModel = $this->modelQueueFactory->create()->load('Order', 'integra_model');
        $message = IntegrationData::checkRequest($orderModel, 'get');

        if (isset($message)) {
            $orderModel->setAvailable(0);
            $orderModel->save();
            return;
        } else {
            $requested = HelperData::getOrders();

            if (empty($requested['Orders'])) {
                return;
            }

            OrderData::startOrders($requested, $orderModel);

            return;
        }
    }      

    public function productUpdate()
    {
        $productModel = $this->modelIntegrationFactory->create()->load('Product Update', 'integra_model');

        $message = IntegrationData::checkRequest($productModel, 'put');

        if (isset($message)) {
            $productModel->setAvailable(0);
            $productModel->save();
            return;
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

            return;
        }
    }
}