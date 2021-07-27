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

namespace Novapc\Integracommerce\Block\Adminhtml;

use Magento\Backend\Block;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;

class Report extends Container
{
    /**
     * @var LayoutFactory
     */
    protected $viewLayoutFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;


    /**
     * Set template
     */
      public function __construct(Context $context, 
        LayoutFactory $viewLayoutFactory, 
        StoreManagerInterface $modelStoreManagerInterface, 
        array $data = [])
      {
        $this->viewLayoutFactory = $viewLayoutFactory;
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;

        parent::__construct($context, $data);
      }

    /**
     * Prepare button and grid
     *
     * @return Block\
     */
    protected function _prepareLayout()
    {
       
        $this->setChild('grid', $this->viewLayoutFactory->create()->createBlock('integracommerce/adminhtml_report_grid', 'report.grid'));
        return parent::_prepareLayout();
    }

    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        if (!$this->modelStoreManagerInterface->isSingleStoreMode()) {
               return false;
        }

        return true;
    }
}
