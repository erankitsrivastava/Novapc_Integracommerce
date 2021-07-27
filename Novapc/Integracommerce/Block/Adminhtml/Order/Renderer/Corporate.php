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

namespace Novapc\Integracommerce\Block\Adminhtml\Order\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Corporate extends AbstractRenderer
{
    
    public function render(DataObject $row)
    {

    $value =  $row->getData($this->getColumn()->getIndex());
    if (!$value || $value == '') {
        return '<span style="color:red; font-weight: bold;">'. __('No Data') .'</span>';
    } else {
        return $value;
    }    
 
    }
   
}