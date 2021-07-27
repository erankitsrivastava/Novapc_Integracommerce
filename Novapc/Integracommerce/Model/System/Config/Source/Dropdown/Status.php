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

use Magento\Sales\Model\Order\StatusFactory;

class Status
{
    /**
     * @var StatusFactory
     */
    protected $orderStatusFactory;

    public function __construct(StatusFactory $orderStatusFactory)
    {
        $this->orderStatusFactory = $orderStatusFactory;

    }

    public function toOptionArray()
    {
        $orderStatusCollection = $this->orderStatusFactory->create()->getResourceCollection()->getData();
        $retornArray = [];
        $retornArray = [
            'keepstatus'=>'Por favor selecione...'
        ];        
        foreach ($orderStatusCollection as $orderStatus) {
            if ($orderStatus['status'] == 'pending') {
                continue;
            }

            $retornArray[] = [
                'value' => $orderStatus['status'], 'label' => $orderStatus['label']
            ];
        }

        return $retornArray;
    }
}