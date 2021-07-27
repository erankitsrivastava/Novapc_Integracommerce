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

namespace Novapc\Integracommerce\Block\Adminhtml\Report\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Price extends Generic
{

    public function __construct(Context $context, 
        Registry $registry, 
        FormFactory $formFactory, 
        array $data = [])
    {


        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = new Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'vendor_form',
            [
                'legend' => __('Preço')
            ]
        );

        $fieldset->addField(
            'price_body',
            'textarea',
            [
                'name' => 'price_body',
                'label' => __('Requisição'),
            ]
        );


        $fieldset->addField(
            'price_error',
            'textarea',
            [
                'name'    => 'price_error',
                'label'   => __('Erro'),
            ]
        );

        $form->addValues($this->frameworkRegistry->registry('report_data')->getData());

        return parent::_prepareForm();
    }

}