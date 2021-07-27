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

namespace Novapc\Integracommerce\Model\System\Config\Source\Dropdown;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

class Prodattr
{
    /**
     * @var Collection
     */
    protected $attributeCollection;

    public function __construct(Collection $attributeCollection)
    {
        $this->attributeCollection = $attributeCollection;

    }

    public function toOptionArray()
    {
        $productAttrs = $this->attributeCollection;
        $retornArray = [];
        foreach ($productAttrs as $productAttr) {
            $retornArray[] = [
                'value' => $productAttr->getAttributeCode(),
                'label' => $productAttr->getFrontendLabel()
            ];
        }

        return $retornArray;
    }
}