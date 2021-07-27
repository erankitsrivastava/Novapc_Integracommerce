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

class Configprod
{
    public function toOptionArray()
    {
        return [
            [
                'value' => '4',
                'label' => 'Selecione uma opção...',
            ],            
            [
                'value' => '1',
                'label' => 'Produto Único',
            ],
            [
                'value' => '2',
                'label' => 'Por Variação',
            ],
        ];
    }
}