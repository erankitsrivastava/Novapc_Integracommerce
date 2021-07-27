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

namespace Novapc\Integracommerce\Block\Adminhtml\Integration;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Model\IntegrationFactory;

class Grid extends WidgetGrid
{
    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;

    /**
     * @var IntegrationFactory
     */
    protected $modelIntegrationFactory;

    public function __construct(Context $context, 
        HelperData $backendHelper, 
        StoreManagerInterface $modelStoreManagerInterface, 
        IntegrationFactory $modelIntegrationFactory, 
        array $data = [])
    {
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->modelIntegrationFactory = $modelIntegrationFactory;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('integrationGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('integration_filter');
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return $this->modelStoreManagerInterface->getStore($storeId);
    }        
    
    protected function _prepareCollection() 
    {
        $collection = $this->modelIntegrationFactory->create()->getCollection();

        $this->setCollection($collection);
                  
        parent::_prepareCollection();
        
        return $this;

    }

    protected function _prepareColumns() 
    {
        $this->addColumn(
            'integra_model',
            [
                'header'=> __('A integrar'),
                'index' => 'integra_model',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Integration\Renderer\Model',
            ]
        );

        $this->addColumn(
            'status',
            [
                'header'=> __('Ultima Atualização'),
                'index' => 'status',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Integration\Renderer\Status',
            ]
        );

        $this->addColumn(
            'available',
            [
                'header'=> __('Disponível'),
                'index' => 'available',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Integration\Renderer\Available',
            ]
        );

        $this->addColumn(
            'initial_hour',
            [
                'header'=> __('Primeira Atualização'),
                'index' => 'initial_hour',
                'renderer' => 'Novapc\Integracommerce\Block\Adminhtml\Integration\Renderer\Status',
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('integracommerce_integration');

        $this->getMassactionBlock()->addItem(
            'category',
            [
            'label'    => __('Exportar Categorias'),
            'url'      => $this->getUrl('*/*/massCategory')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'insert',
            [
            'label'    => __('Exportar Produtos'),
            'url'      => $this->getUrl('*/*/massInsert')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'update',
            [
                'label'    => __('Atualizar Produtos'),
                'url'      => $this->getUrl('*/*/massUpdate')
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

}