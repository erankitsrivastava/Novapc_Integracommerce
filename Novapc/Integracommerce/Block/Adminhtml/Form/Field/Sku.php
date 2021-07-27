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

namespace Novapc\Integracommerce\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Sku extends AbstractFieldArray
{
    /**
     * @var CategoryFactory
     */
    protected $modelCategoryFactory;

    /**
     * @var Collection
     */
    protected $attributeCollection;

    public function __construct(Context $context, 
        CategoryFactory $modelCategoryFactory, 
        Collection $attributeCollection, 
        array $data = [])
    {
        $this->modelCategoryFactory = $modelCategoryFactory;
        $this->attributeCollection = $attributeCollection;

        $this->addColumn(
            'category',
            [
                'label' => __('Categoria'),
                'size'  => 28
            ]
        );

        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'size'  => 28
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Novo Atributo');

        parent::__construct($context, $data);
        $this->setTemplate('Novapc_Integracommerce::integracommerce/system/config/form/field/array_dropdown.phtml');
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Exception('Nome da coluna especificado está errado.');
        }

        if ($columnName == 'category') {
            $column     = $this->_columns[$columnName];
            $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

            $rendered = '<select name="'.$inputName.'">';
            if ($columnName == 'category') {
                $categories = $this->modelCategoryFactory->create()
                    ->getCollection()
                    ->addFieldToFilter(
                        'level',
                        [
                            'gteq' => '2'
                        ]
                    )
                    ->addAttributeToSelect('*');

                foreach ($categories as $category) {
                    $catName = str_replace("'", "\'", $category->getName());
                    $rendered .= '<option value="'.$category->getId().'">'.$catName.'</option>';
                }
            }

            $rendered .= '</select>';

            return $rendered;
        } elseif ($columnName == 'attribute') {
            $column     = $this->_columns[$columnName];
            $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

            $rendered = '<select name="'.$inputName.'">';
            if ($columnName == 'attribute') {
                $productAttrs = $this->attributeCollection;
                
                foreach ($productAttrs as $productAttr) {
                    if ($productAttr->getData('is_configurable') > 0) {
                        $attrLabel = str_replace("'", "\'", $productAttr->getFrontendLabel());
                        $rendered .= '<option value="'.$productAttr->getAttributeCode().'">'.$attrLabel.'</option>';
                    } else {
                        continue;
                    }
                }
            }

            $rendered .= '</select>';

            return $rendered;
        }
    }      
    
}
