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

namespace Novapc\Integracommerce\Block\Adminhtml\Integration\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Status extends AbstractRenderer
{
    
    public function render(DataObject $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if (!$value || empty($value)) {
            return '<span style="color:red;">'. __('A Sincronizar') .'</span>';
        } else {
            $date = strtotime($value);
            $newformat = date('d/m/Y H:i:s', $date);
            return '<span>'. $newformat .'</span>';
        }
    }
   
}