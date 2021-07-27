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

$configValue = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');

if ($configValue == 1) {
    $indexer = Mage::getModel('index/indexer')->getProcessByCode('catalog_category_flat');
    $indexer->reindexEverything();
}

