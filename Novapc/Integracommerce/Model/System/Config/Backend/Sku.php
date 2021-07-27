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

namespace Novapc\Integracommerce\Model\System\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Novapc\Integracommerce\Model\SkuFactory;

class Sku extends Value
{
    /**
     * @var SkuFactory
     */
    protected $modelSkuFactory;

    public function __construct(Context $context, 
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        SkuFactory $modelSkuFactory, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->modelSkuFactory = $modelSkuFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function _afterLoad()
    {
        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setValue(empty($value) ? false : unserialize($value));
        }
    }    

    public function _beforeSave()
    {   
        $value = $this->getValue();
        
        $clearCollection = $this->modelSkuFactory->create()->getCollection();
        
        if (!empty($clearCollection) && $clearCollection) {
            foreach ($clearCollection as $item) {
                $item->delete();
            }
        }

        foreach ($value as $key => $newValue) {
            if (empty($newValue)) {
                continue;
            }

            $integraAttrs = $this->modelSkuFactory->create();
            $integraAttrs->setData('category', $newValue['category']);
            $integraAttrs->setData('attribute', $newValue['attribute']);
            $integraAttrs->save();                    
        }
        
        if (empty($value['__empty']) && count($value) <= 1) {
            $this->setValue(null);
        } else {
            unset($value['__empty']);
            $this->setValue(serialize($value));
        }
    }     
}