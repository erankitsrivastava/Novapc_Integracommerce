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

namespace Novapc\Integracommerce\Helper;

use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Type\ConfigurableFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\Date;
use Magento\Framework\Model\ResourceModel\TransactionFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\AddressFactory as OrderAddressFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\ItemFactory as OrderItemFactory;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Helper\Data as HelperData;
use Novapc\Integracommerce\Model\OrderFactory as ModelOrderFactory;
use Novapc\Integracommerce\Model\UpdateFactory;
use Psr\Log\LoggerInterface;

class OrderData extends HelperData
{
    /**
     * @var Date
     */
    protected $modelDate;

    /**
     * @var CustomerFactory
     */
    protected $modelCustomerFactory;

    /**
     * @var OrderFactory
     */
    protected $modelOrderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logLoggerInterface;

    /**
     * @var RegionFactory
     */
    protected $modelRegionFactory;

    /**
     * @var AddressFactory
     */
    protected $modelAddressFactory;

    /**
     * @var TransactionFactory
     */
    protected $resourceModelTransactionFactory;

    /**
     * @var Config
     */
    protected $modelConfig;

    /**
     * @var OrderAddressFactory
     */
    protected $orderAddressFactory;

    /**
     * @var PaymentFactory
     */
    protected $orderPaymentFactory;

    /**
     * @var OrderItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var ModelOrderFactory
     */
    protected $integracommerceModelOrderFactory;

    /**
     * @var Collection
     */
    protected $statusCollection;

    public function __construct(Context $context, 
        ScopeConfigInterface $storeConfig, 
        StoreManagerInterface $modelStoreManagerInterface, 
        ItemFactory $stockItemFactory, 
        Action $productAction, 
        ConfigurableFactory $typeConfigurableFactory, 
        ProductFactory $modelProductFactory, 
        UpdateFactory $modelUpdateFactory, 
        Date $modelDate, 
        CustomerFactory $modelCustomerFactory, 
        OrderFactory $modelOrderFactory, 
        LoggerInterface $logLoggerInterface, 
        RegionFactory $modelRegionFactory, 
        AddressFactory $modelAddressFactory, 
        TransactionFactory $resourceModelTransactionFactory, 
        Config $modelConfig, 
        OrderAddressFactory $orderAddressFactory, 
        PaymentFactory $orderPaymentFactory, 
        OrderItemFactory $orderItemFactory, 
        ModelOrderFactory $integracommerceModelOrderFactory, 
        Collection $statusCollection)
    {
        $this->modelDate = $modelDate;
        $this->modelCustomerFactory = $modelCustomerFactory;
        $this->modelOrderFactory = $modelOrderFactory;
        $this->logLoggerInterface = $logLoggerInterface;
        $this->modelRegionFactory = $modelRegionFactory;
        $this->modelAddressFactory = $modelAddressFactory;
        $this->resourceModelTransactionFactory = $resourceModelTransactionFactory;
        $this->modelConfig = $modelConfig;
        $this->orderAddressFactory = $orderAddressFactory;
        $this->orderPaymentFactory = $orderPaymentFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->integracommerceModelOrderFactory = $integracommerceModelOrderFactory;
        $this->statusCollection = $statusCollection;

        parent::__construct($context, $storeConfig, $modelStoreManagerInterface, $stockItemFactory, $productAction, $typeConfigurableFactory, $modelProductFactory, $modelUpdateFactory);
    }

    public static function startOrders($requested, $orderModel)
    {
        /*CARREGANDO A QUANTIDADE DE REQUISICOES POR TEMPO*/
        $requestedHour = $orderModel->getRequestedHour();
        $requestedDay = $orderModel->getRequestedDay();
        $requestedWeek = $orderModel->getRequestedWeek();
        $requestedInitial = $orderModel->getInitialHour();

        /*SOMA A QUANTIDADE ANTERIOR COM A RETORNADA*/
        $requestedHour = $requestedHour + $requested['Total'];
        $requestedDay = $requestedDay + $requested['Total'];
        $requestedWeek = $requestedWeek + $requested['Total'];
        $requestTime = $this->modelDate->date('Y-m-d H:i:s');

        /*GRAVA O HORARIO DA REQUISICAO E AS QUANTIDADES*/
        $orderModel->setStatus($requestTime);
        $orderModel->setRequestedHour($requestedHour);
        $orderModel->setRequestedDay($requestedDay);
        $orderModel->setRequestedWeek($requestedWeek);

        if (empty($requestedInitial)) {
            $orderModel->setInitialHour($requestTime);
        }

        $orderModel->save();

        foreach ($requested['Orders'] as $order) {
            self::processingOrder($order);
        }
    }

    public static function processingOrder($order)
    {
        /*VERIFICA SE CLIENTE JÁ EXISTE*/
        if ($order['CustomerPfCpf']) {
            $customerDoc = $order['CustomerPfCpf'];
        } elseif ($order['CustomerPjCnpj']) {
            $customerDoc = $order['CustomerPjCnpj'];
        }

        if (!empty($customerDoc)) {
            $customer = $this->modelCustomerFactory->create()
                ->getCollection()
                ->addFieldToFilter('taxvat', $customerDoc)->load()->getFirstItem();
            $customerId = $customer->getId();
        }

        /*SE CLIENTE JA ESXISTE, ATUALIZA, SE NAO, CRIA*/
        if ($customerId && !empty($customerId)) {
            self::updateCustomer($customer, $order);
        } else {
            $customerId = self::createCustomer($order);
        }

        /*VERIFIFA SE JA EXISTE PEDIDO NO MAGENTO COM O ID DA COMPRA INTEGRACOMMERCE*/
        $existingOrder = $this->modelOrderFactory->create()->load($order['IdOrder'], 'integracommerce_id');

        $incrementId = $existingOrder->getIncrementId();
        if (!empty($incrementId)) {
            return;
        } else {
            /*CRIA O PEDIDO NA TABELA DE CONTROLE DO MODULO*/
            $integraModel = self::integraOrder($order, $customerId, null);
            /*CRIA O PEDIDO NO MAGENTO*/
            self::createOrder($order, $customerId, $integraModel);
        }
    }

    public static function createCustomer($order)
    {
        $ieAttribute = $this->storeConfig->getValue('integracommerce/attributes/ierg', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $customer = $this->modelCustomerFactory->create();
        $customer->setWebsiteId($this->modelStoreManagerInterface->getWebsite()->getId());
        $customer->setStore($this->modelStoreManagerInterface->getStore());
     
        $customer->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']));
        $customer->setLastname('.');
        $customer->setData('taxvat', (empty($order['CustomerPfCpf']) ? $order['CustomerPjCnpj'] : $order['CustomerPfCpf']));

        if (!empty($order['CustomerPjIe']) && $ieAttribute !== 'not_selected') {
            $customer->setData($ieAttribute, $customer['CustomerPjIe']);
        }

        $newEmail = $order['MarketplaceName'] . '_' . mt_rand() . '@email.com.br';
        $customer->setEmail($newEmail);

        try {
            $customer->save();
        } catch (\Exception $e) {
                $this->logLoggerInterface->debug('Erro ao criar o cliente. Mensagem: '.$e->getMessage(), null, 'customer_save_error_integracommerce.log');
        }        

        //VERIFICA SE TEM DADOS PARA CADASTRAR ENDEREÇO  
        $region = $this->modelRegionFactory->create()->loadByCode($order['DeliveryAddressState'], 'BR');

        $address = $this->modelAddressFactory->create();
        $address->setCustomerId($customer->getId())
            ->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']))
            ->setLastname('.')           
            ->setCountryId('BR')
            ->setPostcode($order['DeliveryAddressZipcode'])
            ->setCity($order['DeliveryAddressCity'])
            ->setRegion($region->getName())
            ->setRegionId($region->getId())
            ->setTelephone($order['TelephoneMainNumber'])
            ->setFax($order['TelephoneSecundaryNumber'])
            ->setStreet([$order['DeliveryAddressStreet'], $order['DeliveryAddressNumber'], (empty($order['DeliveryAddressAdditionalInfo']) ? 'Não Informado' : $order['DeliveryAddressAdditionalInfo']) , $order['DeliveryAddressNeighborhood']])
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        try {
            $address->save(); 
        } catch (\Exception $e){
            $message = $e->getMessage();
            $this->logLoggerInterface->debug('Erro ao cadastrar endereço. Mensagem: '. $message, null, 'customer_save_error_integracommerce.log');
        }

        $customerId = $customer->getId();
        return $customerId;

    }
    
    public static function updateCustomer($customer,$order)
    {
        $defaultShippingId = $customer->getDefaultShipping();
        $address = $this->modelAddressFactory->create();
        $address->load($defaultShippingId);

        $region = $this->modelRegionFactory->create()->loadByCode($order['DeliveryAddressState'], 'BR');

        $address->setCustomerId($customer->getId())
            ->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']))
            ->setLastname('.')           
            ->setCountryId('BR')
            ->setPostcode($order['DeliveryAddressZipcode'])
            ->setCity($order['DeliveryAddressCity'])
            ->setRegion($region->getName())
            ->setRegionId($region->getId())
            ->setTelephone($order['TelephoneMainNumber'])
            ->setFax($order['TelephoneSecundaryNumber'])
            ->setStreet([$order['DeliveryAddressStreet'], $order['DeliveryAddressNumber'], (empty($order['DeliveryAddressAdditionalInfo']) ? 'Não Informado' : $order['DeliveryAddressAdditionalInfo']), $order['DeliveryAddressNeighborhood']])
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        try {
            $address->save(); 
        } catch (\Exception $e){ 
            $this->logLoggerInterface->debug('Erro ao cadastrar endereço. Mensagem: '.$e->getMessage(), null, 'customer_save_error_integracommerce.log');
        }        

        $customerId = $customer->getId();
        return $customerId;
    }

    public static function createOrder($order, $customerId, $integraModel = null)
    {
        $customer = $this->modelCustomerFactory->create()->load($customerId);
        //INICIA O MODEL DE PEDIDO DO MAGENTO
        $transaction = $this->resourceModelTransactionFactory->create();
        $storeId = $customer->getStoreId();
        if ($storeId == 0) {
            $storeId = 1;
        }

        //PEGA DO BANCO QUAL VAI SER O PROXIMO ID DE PEDIDO
        $reservedOrderId = $this->modelConfig->getEntityType('order')->fetchNewIncrementId($storeId);

        $mageOrder = $this->modelOrderFactory->create()
            ->setIncrementId($reservedOrderId)
            ->setStoreId($storeId)
            ->setQuoteId(0)
            ->setDiscountAmount(0)
            ->setShippingAmount(0)
            ->setShippingTaxAmount(0)
            ->setBaseDiscountAmount(0)
            ->setIsVirtual(0)
            ->setBaseShippingAmount(0)
            ->setBaseShippingTaxAmount(0)
            ->setBaseTaxAmount(0)
            ->setBaseToGlobalRate(1)
            ->setBaseToOrderRate(1)
            ->setStoreToBaseRate(1)
            ->setStoreToOrderRate(1)
            ->setTaxAmount(0)
            ->set\Global\currency\code(Mage::app()->getBaseCurrencyCode())
            ->set\Base\currency\code(Mage::app()->getBaseCurrencyCode())
            ->set\Store\currency\code(Mage::app()->getBaseCurrencyCode())
            ->set\Order\currency\code(Mage::app()->getBaseCurrencyCode());

        $mageOrder->setCustomer_email($customer->getEmail())
            ->setCustomerFirstname($customer->getFirstname())
            ->setCustomerLastname($customer->getLastname())
            ->setCustomerTaxvat($customer->getTaxvat())
            ->setCustomerGroupId($customer->getGroupId())
            ->set\Customer\is\guest(0)
            ->setCustomer($customer);

        $billing = $customer->getDefaultBillingAddress();
        $billingAddress = $this->orderAddressFactory->create()
                ->setStoreId($storeId)
                ->setAddressType(Address::TYPE_BILLING)
                ->setCustomerId($customer->getId())
                ->setCustomerAddressId($customer->getDefaultBilling())
                ->set\Customer\address\id($billing->getEntityId())
                ->setPrefix($billing->getPrefix())
                ->setFirstname($billing->getFirstname())
                ->setLastname($billing->getLastname())
                ->setStreet($billing->getStreet())
                ->setCity($billing->getCity())
                ->setCountry_id($billing->getCountryId())
                ->setRegion($billing->getRegion())
                ->setRegion_id($billing->getRegionId())
                ->setPostcode($billing->getPostcode())
                ->setTelephone($billing->getTelephone())
                ->setFax($billing->getFax())
                ->setVatId($customer->getData('taxvat'));
        $mageOrder->setBillingAddress($billingAddress);

        $shipping = $customer->getDefaultShippingAddress();
        $shippingAddress = $this->orderAddressFactory->create()
            ->setStoreId($storeId)
            ->setAddressType(Address::TYPE_SHIPPING)
            ->setCustomerId($customer->getId())
            ->setCustomerAddressId($customer->getDefaultShipping())
            ->set\Customer\address\id($shipping->getEntityId())
            ->setPrefix($shipping->getPrefix())
            ->setFirstname($shipping->getFirstname())
            ->setLastname($shipping->getLastname())
            ->setStreet($shipping->getStreet())
            ->setCity($shipping->getCity())
            ->setCountry_id($shipping->getCountryId())
            ->setRegion($shipping->getRegion())
            ->setRegion_id($shipping->getRegionId())
            ->setPostcode($shipping->getPostcode())
            ->setTelephone($shipping->getTelephone())
            ->setFax($shipping->getFax())
            ->setVatId($customer->getData('taxvat'));

        //INSERINDO METODO DE ENTREGA
        if (empty($order['TotalFreight'])) {
            $shippingprice = 0;
        } else {
            $shippingprice = $order['TotalFreight'];
        }

        $mageOrder->setShippingAddress($shippingAddress)
            ->setShipping_method('flatrate_flatrate')
            ->setShippingDescription('Loja: '. $order['StoreName'] .', Marketplace: '. $order['MarketplaceName'] .', Frete: ' . $order['ShippedCarrierName'])
            ->setShippingAmount($shippingprice)
            ->setBaseShippingAmount($shippingprice);

        $orderPayment = $this->orderPaymentFactory->create()
            ->setStoreId($storeId)
            ->setCustomerPaymentId(0)
            ->setMethod('cashondelivery')
            ->setPo_number(' – ');
        $mageOrder->setPayment($orderPayment);

        $weightAttribute = $this->storeConfig->getValue('integracommerce/attributes/weight', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $productControl = $this->storeConfig->getValue('integracommerce/general/sku_control', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $subTotal = 0;
        foreach ($order['Products'] as $key => $product) {
            if ($productControl == 'sku') {
                $productId = $this->modelProductFactory->create()->getResource()->getIdBySku($product['IdSku']);
            } else {
                $productId = $product['IdSku'];
            }

            $mageProduct = $this->modelProductFactory->create()->load($productId);

            if (!$mageProduct->getId()) {
                continue;
            }

            $newPrice = $product['Price'];
            $rowTotal = $newPrice * $product['Quantity'];
            $orderItem = $this->orderItemFactory->create()
                ->setStoreId($storeId)
                ->setQuoteItemId(0)
                ->setQuoteParentItemId(null)
                ->setProductId($mageProduct->getId())
                ->setProductType($mageProduct->getTypeId())
                ->setQtyBackordered(null)
                ->setTotalQtyOrdered($product['Quantity'])
                ->setQtyOrdered($product['Quantity'])
                ->setName($mageProduct->getName())
                ->setSku($mageProduct->getSku())
                ->setPrice($newPrice)
                ->setWeight($mageProduct->getData($weightAttribute))
                ->setBasePrice($newPrice)
                ->setOriginalPrice($newPrice)
                ->setRowTotal($rowTotal)
                ->setBaseRowTotal($rowTotal);

                $subTotal += $rowTotal;
                $mageOrder->addItem($orderItem);
        }

        $mageOrder->setSubtotal($subTotal)
            ->setBaseSubtotal($subTotal)
            ->setGrandTotal($subTotal + $shippingprice)
            ->setBaseGrandTotal($subTotal);

        $mageOrder->setData('integracommerce_id', $order['IdOrder']);

        $estimatedDate = substr($order['EstimatedDeliveryDate'], 0, 10);
        $estimatedDate = DateTime::createFromFormat('Y-m-d', $estimatedDate);
        $estimatedDate = $estimatedDate->format('d/m/Y');
        
        $comment = $mageOrder->addStatusHistoryComment("Código do Pedido Integracommerce: " .
            $order['IdOrder'] . "<br>" . "Código do Pedido Marketplace: " .
            $order['IdOrderMarketplace'] . "<br>" . "Data Estimada de Entrega: " . $estimatedDate, false);
        $comment->setIsCustomerNotified(false);

        try {
            $transaction->addObject($mageOrder);
            $transaction->addCommitCallback([$mageOrder, 'place']);
            $transaction->addCommitCallback([$mageOrder, 'save']);
            $transaction->save();
        } catch (\Exception $e){
            $integraModel->setMageError($e->getMessage());
            $integraModel->save();
        }

        $entityId = $mageOrder->getEntityId();
        $updateIncrementId = $mageOrder->getIncrementId();
        
        if (!empty($updateIncrementId)) {
            self::updateIntegraOrder($order['IdOrder'], $entityId);

            $status = $this->storeConfig->getValue('integracommerce/order_status/approved', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
            if ($status !== 'keepstatus') {
                $states = [];
                $stateCollection = self::orderStatusFilter($status);
                foreach ($stateCollection as $state) {
                    $states[] = $state->getState();
                }

                $mageOrder->setData('state', $states[0]);
                $mageOrder->setStatus($status);
                $history = $mageOrder->addStatusHistoryComment("Status no Integracommerce: Aprovado", false);
                $history->setIsCustomerNotified(false);

                try {
                    $mageOrder->save();
                } catch (\Exception $e) {
                    $integraModel->setMageError($e->getMessage());
                    $integraModel->save();
                }
            }
        }

        $shouldInvoice = $this->storeConfig->getValue('integracommerce/order_status/invoice', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if ($mageOrder->canInvoice() && $shouldInvoice == 1) {
            self::createInvoice($mageOrder);
        }

        $integraModel->setMagentoOrderId($entityId);
        $integraModel->setMagentoCustomerId($customer->getId());
        $integraModel->setCustomerEmail($customer->getEmail());
        $integraModel->save();

        return $entityId;
    }

    public static function createInvoice($mageOrder)
    {
        $invoice = ObjectManager::getInstance()->create('sales/service_order', $mageOrder)->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
        $invoice->register();
        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->setState(Invoice::STATE_OPEN);

        $transactionSave = $this->resourceModelTransactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transactionSave->save();
    }

    public static function updateIntegraOrder($orderId, $mageOrderId)
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $url = 'https://' . $environment . '.integracommerce.com.br/api/Order';

        $body = [
            "IdOrder" => $orderId,
            "OrderStatus" => 'PROCESSING'
        ];

        $jsonBody = json_encode($body);

        $return = HelperData::callCurl("PUT", $url, $jsonBody);

        if ($return['httpCode'] !== 204) {
            if (!empty($return['Errors'])) {
                foreach ($return['Errors'] as $error) {
                    $errorMessage = $error['Message'] . ', ';
                }

                $this->logLoggerInterface->debug('Error: ' . $httpcode . 'Erro ao atualizar o pedido ' . $mageOrderId . ', Codigo Integracommerce: ' . $orderId . '. Motivo: ' . $return['Message'] . '. Erros: ' . $errorMessage, null, 'integracommerce_order_update_error.log');
            }

            $requestLog = $this->storeConfig->getValue('integracommerce/general/request_log', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
            if ($requestLog == 1) {
                $this->logLoggerInterface->debug('Requisição: ' . $jsonBody, null, 'integracommerce_order_request.log');
            }
        }
    }

    public static function integraOrder($order,$customerId,$mageOrder = null)
    {
        $customer = $this->modelCustomerFactory->create()->load($customerId);
        $integraOrder = $this->integracommerceModelOrderFactory->create()->load($order['IdOrder'], 'integra_id');

        $integraId = $integraOrder->getIntegraId();
        if (empty($integraId)) {
            $integraOrder = $this->integracommerceModelOrderFactory->create();
            $integraOrder->setIntegraId($order['IdOrder']);
        }

        $integraOrder->setMarketplaceId($order['IdOrderMarketplace']);
        $integraOrder->setMarketplaceName($order['MarketplaceName']);
        $integraOrder->setStoreName($order['StoreName']);

        $integraOrder->setCustomerPfCpf((empty($order['CustomerPfCpf']) ? "" : $order['CustomerPfCpf']));
        $integraOrder->setCustomerPfName((empty($order['CustomerPfName']) ? "" : $order['CustomerPfName']));
        $integraOrder->setCustomerPjCnpj((empty($order['CustomerPjCnpj']) ? "" : $order['CustomerPjCnpj']));
        $integraOrder->setCustomerPjCorporateName((empty($order['CustomerPjCorporatename']) ? "" : $order['CustomerPjCorporatename']));

        $integraOrder->setDeliveryStreet($order['DeliveryAddressStreet']);
        $integraOrder->setDeliveryAdditionalInfo($order['DeliveryAddressAdditionalInfo']);
        $integraOrder->setDeliveryNeighborhood($order['DeliveryAddressNeighborhood']);
        $integraOrder->setDeliveryCity($order['DeliveryAddressCity']);
        $integraOrder->setDeliveryReference($order['DeliveryAddressReference']);
        $integraOrder->setDeliveryState($order['DeliveryAddressState']);
        $integraOrder->setDeliveryNumber($order['DeliveryAddressNumber']);
        $integraOrder->setTelephoneMain($order['TelephoneMainNumber']);
        $integraOrder->setTelephoneSecondary($order['TelephoneSecundaryNumber']);
        $integraOrder->setTelephoneBusiness($order['TelephoneBusinessNumber']);
        $integraOrder->setTotalAmount($order['TotalAmount']);
        $integraOrder->setTotalFreight($order['TotalFreight']);
        $integraOrder->setTotalDiscount($order['TotalDiscount']);
        $integraOrder->setOrderStatus($order['OrderStatus']);

        if (isset($mageOrder)) {
            $integraOrder->setMagentoOrderId($mageOrder);
            $integraOrder->setMagentoCustomerId($customer->getId());
            $integraOrder->setCustomerEmail($customer->getEmail());
        }

        $integraOrder->setInsertedAt($order['InsertedDate']);
        $integraOrder->setPurchasedAt($order['PurchasedDate']);
        $integraOrder->setApprovedAt($order['ApprovedDate']);
        $integraOrder->setUpdatedAt($order['UpdatedDate']);

        try {
            $integraOrder->save();
        } catch (\Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage(), null, 'integra_order_save_error_integracommerce.log');
        }

        return $integraOrder;
    }

    public static function checkDate($line, $integraModel)
    {
        if (!empty($line)) {
            $ymd = DateTime::createFromFormat('d/m/Y', $line);
            if ($ymd) {
                $line = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            } else {
                $ymd = DateTime::createFromFormat('d/m/Y H:i:s', $line);
                if ($ymd) {
                    $line = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                } else {
                    $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                    $integraModel->save();
                    return;
                }
            }

            return $line;
        }
    }

    public static function updateOrder($order)
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $invoiceStatus = $this->storeConfig->getValue('integracommerce/order_status/nota_fiscal', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $shippingStatus = $this->storeConfig->getValue('integracommerce/order_status/dados_rastreio', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $integraModel = $this->integracommerceModelOrderFactory->create()->load($order->getData('integracommerce_id'), 'integra_id');
        $url = 'https://' . $environment . '.integracommerce.com.br/api/Order';

        try {
            $status = $order->getStatus();
            $comment = self::getHistoryByStatus($order, $status);
            $commentData = $comment->getData('comment');

            $lines = explode('|', $commentData);
            if ((empty($lines) && $status !== 'delivered') || empty($commentData)) {
                return;
            }

            $line = [];
            foreach ($lines as $_line) {
                $line[] = $_line;
            }

            if ($status == $invoiceStatus && count($line) < 4) {
                throw new \Exception("Não foi possivel enviar os dados da Nota Fiscal. Informações inválidas.");
            } elseif ($status == $shippingStatus && count($line) !== 5) {
                throw new \Exception("Não foi possivel enviar os dados de Rastreio. Informações inválidas.");
            } elseif ($status == 'shipexception' && count($line) !== 2) {
                throw new \Exception("Não foi possivel enviar os dados de Falha no Envio. Informações inválidas.");
            }

            if ((($invoiceStatus && !empty($invoiceStatus)) && $invoiceStatus == $status) || $status == 'processing') {
                //CHECANDO DATA DE EMISSAO DA FATURA
                $return = self::checkDate($line[2], $integraModel);
                $line[2] = $return;

                if (strlen($line[3]) < 44) {
                    $line[3] = str_pad($line[3], 44, "0");
                }

                $body = [
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" => "INVOICED",
                    "InvoicedNumber" => $line[0],
                    "InvoicedLine" => $line[1],
                    "InvoicedIssueDate" => $line[2],
                    "InvoicedKey" => $line[3],
                    "InvoicedDanfeXml" => (empty($line[4]) ? "" : $line[4])
                ];
            } elseif ((($shippingStatus && !empty($shippingStatus)) && $shippingStatus == $status) || $status == 'complete') {
                //CHECANDO DATA ESTIMADA DE ENTREGA
                $return = self::checkDate($line[2], $integraModel);
                $line[2] = $return;

                //CHECANDO DATA DE ENTREGA A TRANSPORTADORA
                $return = self::checkDate($line[3], $integraModel);
                $line[3] = $return;

                $body = [
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" =>"SHIPPED",
                    "ShippedTrackingUrl" => (empty($line[0]) ? "" : $line[0]),
                    "ShippedTrackingProtocol" => (empty($line[1]) ? "" : $line[1]),
                    "ShippedEstimatedDelivery" => $line[2],
                    "ShippedCarrierDate" => $line[3],
                    "ShippedCarrierName" => $line[4]
                ];
            } elseif ($status == 'delivered') {
                //CHECANDO DATA ESTIMADA DE ENTREGA
                $return = self::checkDate($commentData, $integraModel);
                $deliveredDate = $return;

                $body = [
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" => "DELIVERED",
                    "DeliveredDate" => $deliveredDate
                ];
            } elseif ($status == 'shipexception') {
                $return = self::checkDate($line[1], $integraModel);
                $line[1] = $return;

                $body = [
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" => "SHIPMENT_EXCEPTION",
                    "ShipmentExceptionObservation" => $line[0],
                    "ShipmentExceptionOccurrenceDate" => $line[1]
                ];
            }

            if (isset($body)) {
                $jsonBody = json_encode($body);
                $return = HelperData::callCurl("PUT", $url, $jsonBody);

                if ($return['httpCode'] !== 204) {
                    if (!empty($return['Errors'])) {
                        foreach ($return['Errors'] as $error) {
                            $return = $error['Message'] . '. ';
                        };
                    } elseif ($return['httpCode'] == 200) {
                        $return = 'Dados inseridos';
                    } else {
                        $return = json_encode($return);
                    }
                    $integraModel->setIntegraError($return);
                    $integraModel->save();
                }
            }

        } catch (\Exception $e) {
            $integraModel->setIntegraError($e->getMessage());
            $integraModel->save();
        }
    }

    public static function viewOrder($id)
    {
        $integraOrder = $this->integracommerceModelOrderFactory->create()->load($id, 'integra_id');
        $mageCustomer = $this->modelCustomerFactory->create()->load($integraOrder->getMagentoCustomerId());
        $mageOrder = $this->modelOrderFactory->create()->load($integraOrder->getMagentoOrderId());

        return [$integraOrder,$mageCustomer,$mageOrder];
    }

    public static function getHistoryByStatus($order, $statusId)
    {
        foreach ($order->getStatusHistoryCollection(true) as $status) {
            if ($status->getStatus() == $statusId) {
                return $status;
            }
        }

        return false;
    }

    public static function orderStatusFilter($status)
    {
        $collection = $this->statusCollection;
        $collection->getSelect()->joinLeft(
            ['state_table' => 'sales_order_status_state'],
            'main_table.status=state_table.status',
            ['state', 'is_default']
        );

        $collection->getSelect()->where('state_table.status=?', $status);

        return $collection;
    }

}