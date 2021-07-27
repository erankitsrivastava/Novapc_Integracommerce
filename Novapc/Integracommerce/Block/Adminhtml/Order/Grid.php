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

namespace Novapc\Integracommerce\Block\Adminhtml\Order;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Model\OrderFactory;

class Grid extends WidgetGrid
{
    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;

    /**
     * @var OrderFactory
     */
    protected $modelOrderFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    public function __construct(Context $context, 
        HelperData $backendHelper, 
        StoreManagerInterface $modelStoreManagerInterface, 
        OrderFactory $modelOrderFactory, 
        ScopeConfigInterface $storeConfig, 
        array $data = [])
    {
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->modelOrderFactory = $modelOrderFactory;
        $this->storeConfig = $storeConfig;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('orderGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('order_filter');

        $this->shippingMode = [
                         'custom' => __('custom')
                         ];
        $this->status = [
                         'PROCESSING' => __('Processing'),
                         'INVOICED' => __('Invoiced'),
                         'SHIPPED' => __('Shipped'),
                         'DELIVERED' => __('Delivered'),
                         'SHIPMENT_EXCEPTION' => __('Shipment Exception'),
                         'UNAVAILABLE' => __('Unavailable'),
                         'CANCELED' => __('Canceled'),
                         ];
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return $this->modelStoreManagerInterface->getStore($storeId);
    }        
    
    protected function _prepareCollection() 
    {
        $collection = $this->modelOrderFactory->create()->getCollection();

        $this->setCollection($collection);
                  
        parent::_prepareCollection();
        
        return $this;

    }

    protected function _prepareColumns() 
    {
        $this->addColumn(
            'integra_id',
            [
                'header'=> __('Codígo Integracommerce'),
                'index' => 'integra_id',
                'actions'   => [
                    [
                    'caption'   => __('Edit'),
                    'url'       => ['base'=> '*/*/view'],
                    'field'     => 'integra_id'
                    ]
                ],
            ]
        );

        $this->addColumn(
            'magento_order_id',
            [
                'header'=> __('Código Magento'),
                'index' => 'magento_order_id',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Order\Renderer\Mageid',
            ]
        );

        $this->addColumn(
            'inserted_at',
            [
                'header'=> __('Date Created'),
                'index' => 'inserted_at',
            ]
        );

        $this->addColumn(
            'customer_pf_name',
            [
                'header'=> __('Customer Name'),
                'index' => 'customer_pf_name',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Order\Renderer\Name',         
            ]
        );

        $this->addColumn(
            'customer_pj_corporate_name',
            [
                'header'=> __('Customer Corporate Name'),
                'index' => 'customer_pj_corporate_name',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Order\Renderer\Corporate',
            ]
        );

        $this->addColumn(
            'total_amount',
            [
                'header'=> __('Total Amount'),
                'type' => 'currency',   
                'width' => '1',             
                'currency_code' => $this->storeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE, ScopeInterface::SCOPE_STORE),                
                'index' => 'total_amount',
            ]
        );

        $this->addColumn(
            'total_freight',
            [
                'header'=> __('Shipping Cost'),
                'type' => 'currency',   
                'width' => '1',             
                'currency_code' => $this->storeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE, ScopeInterface::SCOPE_STORE),                
                'index' => 'total_freight',
            ]
        );

        $this->addColumn(
            'total_discount',
            [
                'header'=> __('Discount'),
                'type' => 'currency',   
                'width' => '1',             
                'currency_code' => $this->storeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE, ScopeInterface::SCOPE_STORE),                
                'index' => 'total_discount',
            ]
        );

        $this->addColumn(
            'shipped_carrier_name',
            [
                'header'=> __('Transportadora'),
                'index' => 'shipped_carrier_name',
            ]
        );

        $this->addColumn(
            'order_status',
            [
                'header'=> __('Status'),
                'index' => 'order_status',
                'type'  => 'options',
                'options' => $this->status,
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {

        $this->setMassactionIdField('integra_id');
        $this->getMassactionBlock()->setFormFieldName('integracommerce_order');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label'    => __('Excluir Pedido'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'confirm'  => __('Are you sure?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'search',
            [
                'label'    => __('Buscar Pedido'),
                'url'      => $this->getUrl('*/*/massSearch')
            ]
        );

        return $this;
    }                

    protected function _addColumnFilterToCollection($column)
    {

       return parent::_addColumnFilterToCollection($column);
        
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', ['id' => $row->getIntegraId()]);
    }
}
    

