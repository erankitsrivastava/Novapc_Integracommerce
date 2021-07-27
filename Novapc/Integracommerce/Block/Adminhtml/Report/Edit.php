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

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    public function __construct()
    {
       parent::__construct();
        $this->_objectId = 'id';
        //you will notice that assigns the same blockGroup the Grid Container
        $this->_blockGroup = 'integracommerce';
        // and the same container
        $this->_controller = 'adminhtml_report';
        //we define the labels for the buttons save and delete
        $this->_removeButton('save');
        $this->_updateButton('delete', 'label', 'Excluir Item');
        $this->_removeButton('reset');
        $this->_headerText = __('Informações da Integração');
    }

}