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

namespace Novapc\Integracommerce\Block\Adminhtml\Report\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tabs as WidgetTabs;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\LayoutFactory;

class Tabs extends WidgetTabs
{
    /**
     * @var LayoutFactory
     */
    protected $viewLayoutFactory;

    public function __construct(Context $context, 
        EncoderInterface $jsonEncoder, 
        Session $authSession, 
        LayoutFactory $viewLayoutFactory, 
        array $data = [])
    {
        $this->viewLayoutFactory = $viewLayoutFactory;

        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->setId('reports_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle('Seções');
    }

    protected function _beforeToHtml()
    {
        $this->addTab(
            'product_section',
            [
                'label' => 'Produto',
                'title' => 'Produto',
                'content' => $this->viewLayoutFactory->create()
                    ->createBlock('Novapc\Integracommerce\Block\Adminhtml\Report\Edit\Tab\Product')
                    ->toHtml()
            ]
        );

        $this->addTab(
            'sku_section',
            [
                'label' => 'SKU',
                'title' => 'SKU',
                'content' => $this->viewLayoutFactory->create()
                    ->createBlock('Novapc\Integracommerce\Block\Adminhtml\Report\Edit\Tab\Sku')
                    ->toHtml()
            ]
        );

        $this->addTab(
            'price_section',
            [
                'label' => 'Preço',
                'title' => 'Preço',
                'content' => $this->viewLayoutFactory->create()
                    ->createBlock('Novapc\Integracommerce\Block\Adminhtml\Report\Edit\Tab\Price')
                    ->toHtml()
            ]
        );

        $this->addTab(
            'stock_section',
            [
                'label' => 'Estoque',
                'title' => 'Estoque',
                'content' => $this->viewLayoutFactory->create()
                    ->createBlock('Novapc\Integracommerce\Block\Adminhtml\Report\Edit\Tab\Stock')
                    ->toHtml()
            ]
        );

        return parent::_beforeToHtml();
    }

}