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

namespace Novapc\Integracommerce\Block\Adminhtml\Report;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Model\UpdateFactory;

class Grid extends WidgetGrid
{
    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;

    /**
     * @var UpdateFactory
     */
    protected $modelUpdateFactory;

    public function __construct(Context $context, 
        HelperData $backendHelper, 
        StoreManagerInterface $modelStoreManagerInterface, 
        UpdateFactory $modelUpdateFactory, 
        array $data = [])
    {
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->modelUpdateFactory = $modelUpdateFactory;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('reportGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('report_filter');
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return $this->modelStoreManagerInterface->getStore($storeId);
    }        
    
    protected function _prepareCollection() 
    {
        $collection = $this->modelUpdateFactory->create()->getCollection();

        $this->setCollection($collection);
                  
        parent::_prepareCollection();
        
        return $this;
    }

    protected function _prepareColumns() 
    {
        $this->addColumn(
            'product_id',
            [
                'header'=> __('Product Id'),
                'index' => 'product_id',
                'width' => '50px',
                'type'  => 'number',
            ]
        );

        $this->addColumn(
            'product_error',
            [
                'header'=> __('Produto'),
                'index' => 'product_error',
                'width' => '50px',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Report\Renderer\Status',
            ]
        );

        $this->addColumn(
            'sku_error',
            [
                'header'=> __('SKU'),
                'index' => 'sku_error',
                'width' => '50px',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Report\Renderer\Status',
            ]
        );

        $this->addColumn(
            'price_error',
            [
                'header'=> __('Preço'),
                'index' => 'price_error',
                'width' => '50px',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Report\Renderer\Status',
            ]
        );

        $this->addColumn(
            'stock_error',
            [
                'header'=> __('Estoque'),
                'index' => 'stock_error',
                'width' => '50px',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Report\Renderer\Status',
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('integracommerce_report');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label'    => __('Excluir da Fila'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'confirm'  => __('Tem certeza? Esta ação removerá os itens marcados da fila, inclusive caso ainda não tenha sido atualizado!')
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
        return $this->getUrl('*/*/edit', ['id'=>$row->getProductId()]);
    }

}