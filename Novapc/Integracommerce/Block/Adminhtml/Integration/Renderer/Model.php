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

class Model extends AbstractRenderer
{
    
    public function render(DataObject $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if ($value == 'Category') {
            return '<span>'. __('Exportar Categorias') .'</span>';
        } elseif ($value == 'Product Insert') {
            return '<span>'. __('Exportar Produtos') .'</span>';
        } elseif ($value == 'Product Update') {
            return '<span>'. __('Atualizar Produtos') .'</span>';
        }
    }
   
}