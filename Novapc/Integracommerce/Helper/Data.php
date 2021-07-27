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

namespace Novapc\Integracommerce\Helper;

use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Type\ConfigurableFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Http\Adapter\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Novapc\Integracommerce\Model\UpdateFactory;
use Zend\Http\Client;
use Zend\Http\Response;

class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $modelStoreManagerInterface;

    /**
     * @var ItemFactory
     */
    protected $stockItemFactory;

    /**
     * @var Action
     */
    protected $productAction;

    /**
     * @var ConfigurableFactory
     */
    protected $typeConfigurableFactory;

    /**
     * @var ProductFactory
     */
    protected $modelProductFactory;

    /**
     * @var UpdateFactory
     */
    protected $modelUpdateFactory;

    public function __construct(Context $context, 
        ScopeConfigInterface $storeConfig, 
        StoreManagerInterface $modelStoreManagerInterface, 
        ItemFactory $stockItemFactory, 
        Action $productAction, 
        ConfigurableFactory $typeConfigurableFactory, 
        ProductFactory $modelProductFactory, 
        UpdateFactory $modelUpdateFactory)
    {
        $this->storeConfig = $storeConfig;
        $this->modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->stockItemFactory = $stockItemFactory;
        $this->productAction = $productAction;
        $this->typeConfigurableFactory = $typeConfigurableFactory;
        $this->modelProductFactory = $modelProductFactory;
        $this->modelUpdateFactory = $modelUpdateFactory;

        parent::__construct($context);
    }

    public static function updateStock($product)
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $exportType = $this->storeConfig->getValue('integracommerce/general/export_type', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if ($exportType == 1) {
            if ($product->getData('integracommerce_sync') == 0) {
                return;
            }   
        }     

        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        $stockItem = $this->stockItemFactory->create()
            ->loadByProduct($product->getId());

        $stockQuantity = (int) strstr($stockItem['qty'], '.', true);

        $productControl = $this->storeConfig->getValue('integracommerce/general/sku_control', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if ($productControl == 'sku') {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $body = [];
        array_push(
            $body, [
                'IdSku' => $idSku,
                'Quantity' => $stockQuantity
            ]
        );

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Stock';

        $return = self::callCurl("PUT", $url, $jsonBody);

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return [$jsonBody, $return, $product->getId()];
        }
    }

    public static function newProduct($product,$_cats,$_attrs,$loadedAttrs, $environment)
    {
        if ($loadedAttrs['0'] !== 'not_selected') {
            $_nbmOrigin = $product->getResource()->getAttribute($loadedAttrs['0']);

            if (strpos($_nbmOrigin->getFrontend()->getValue($product), 'Estrangeira') !== false || strpos($_nbmOrigin->getFrontend()->getValue($product), 'Internacional') !== false) {
                $nbmOrigin = "1";
            } elseif (strpos($_nbmOrigin->getFrontend()->getValue($product), 'Nacional') !== false) {
                $nbmOrigin = "0";
            } elseif ($_nbmOrigin->getFrontend()->getValue($product)) {
                $nbmOrigin = "0";
            }
        }

        if (empty($nbmOrigin) || !$nbmOrigin || $nbmOrigin == null) {
            $nbmOrigin = $product->getData($loadedAttrs['0']);

            if (strpos($nbmOrigin, 'Estrangeira') !== false || strpos($nbmOrigin, 'Internacional') !== false) {
                $nbmOrigin = "1";
            } elseif (strpos($nbmOrigin, 'Nacional') !== false) {
                $nbmOrigin = "0";
            } else {
                $nbmOrigin = "0";
            }
        }       

        if (empty($loadedAttrs['3']) || !$loadedAttrs['3'] || $loadedAttrs['3'] == null || $loadedAttrs['3'] == 'not_selected') {
            $checkBrand = "";
        } else {
            $checkBrand = $product->getAttributeText($loadedAttrs['3']);

            if (empty($checkBrand) || !$checkBrand) {
                $checkBrand = $product->getData($loadedAttrs['3']);
            }

            if (empty($checkBrand) || $checkBrand == null) {
                $checkBrand = "";
            }
        }

        if (empty($loadedAttrs['1']) || !$loadedAttrs['1'] || $loadedAttrs['1'] == null || $loadedAttrs['1'] == 'not_selected') {
            $checkNbmNumber = "";
        } else {
            $checkNbmNumber = $product->getAttributeText($loadedAttrs['1']);

            if (empty($checkNbmNumber) || !$checkNbmNumber) {
                $checkNbmNumber = $product->getData($loadedAttrs['1']);
            }   

            if (empty($checkNbmNumber) || $checkNbmNumber == null) {
                $checkNbmNumber = "";
            }                     
        }    

        if (empty($loadedAttrs['2']) || !$loadedAttrs['2'] || $loadedAttrs['2'] == null || $loadedAttrs['2'] == 'not_selected') {
            $checkWarrantyTime = "0";
        } else {
            $checkWarrantyTime = $product->getAttributeText($loadedAttrs['2']);

            if (empty($checkWarrantyTime) || !$checkWarrantyTime) {
                $checkWarrantyTime = $product->getData($loadedAttrs['2']);
            }  

            if (empty($checkWarrantyTime) || $checkWarrantyTime == null) {
                $checkWarrantyTime = "0";
            }                       
        }  

        if ($nbmOrigin !== "0" || $nbmOrigin !== "1") {
            $nbmOrigin = "0";
        }

        $productControl = $this->storeConfig->getValue('integracommerce/general/sku_control', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        if ($productControl == 'sku') {
            $idProduct = $product->getData('sku');
        } else {
            $idProduct = $product->getId();
        }

        $body = [
            "idProduct" => $idProduct,
            "Name" => $product->getName(),
            "Code" => $product->getId(),
            "Brand" => $checkBrand,
            "NbmOrigin" => $nbmOrigin,
            "NbmNumber" => $checkNbmNumber,
            "WarrantyTime" => $checkWarrantyTime,
            "Active" => true,
            "Categories" => $_cats,
            "Attributes" => $_attrs
        ];  

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Product';

        if ($product->getData('integracommerce_active') == 0) {
            $return = self::callCurl("POST", $url, $jsonBody);
        } elseif ($product->getData('integracommerce_active') == 1) {
            $return = self::callCurl("PUT", $url, $jsonBody);
        }

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return [$jsonBody, $return, $product->getId()];
        }

        $productType = $product->getTypeId();
        if ($product->getData('integracommerce_active') == 0 && $productType == 'configurable') {
            $this->productAction->updateAttributes(
                [$product->getId()],
                ['integracommerce_active' => 1],
                0
            );
        }
    }

    public static function newSku($product, $pictures, $_attrs, $loadedAttrs, $productId, $environment, $configurableProduct = null)
    {
        $measure = $this->storeConfig->getValue('integracommerce/general/measure', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Sku';

        $heightValue = $product->getData($loadedAttrs['4']);
        $widthValue = $product->getData($loadedAttrs['5']);
        $lengthValue = $product->getData($loadedAttrs['6']);

        if (!empty($heightValue) && !empty($widthValue) && !empty($lengthValue)) {
            if ($measure && !empty($measure) && $measure == 1) {
                $heightValue = $heightValue / 100;
                $widthValue = $widthValue / 100;
                $lengthValue = $lengthValue / 100;
            } elseif ($measure && !empty($measure) && $measure == 3) {
                $heightValue = $heightValue / 1000;
                $widthValue = $widthValue / 1000;
                $lengthValue = $lengthValue / 1000;
            }
        }

        $stockItem = $this->stockItemFactory->create()
               ->loadByProduct($product->getId());                          

        $normalPrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        if (!$specialPrice || empty($specialPrice)) {
            $specialPrice = $normalPrice;
        }

        if (!$normalPrice || empty($normalPrice) || $normalPrice < 1) {
            if ($configurableProduct && !empty($configurableProduct)) {
                if ($configurableProduct->getId()) {
                    $normalPrice = $configurableProduct->getPrice();
                    $specialPrice = $configurableProduct->getSpecialPrice();
                    if (!$specialPrice || empty($specialPrice)) {
                        $specialPrice = $normalPrice;
                    }
                }
            }
        }

        $stockQuantity = (int) strstr($stockItem['qty'], '.', true);

        $weight = $product->getData($loadedAttrs['7']);
        $weightUnit = $this->storeConfig->getValue('integracommerce/general/weight_unit', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        if (strstr($weight, ".") !== false) {
            if ($weightUnit == 'grama') {
                $weight = strstr($weight, '.', true);
                $weight = $weight / 1000;
            } else {
                $weight = (float) $product->getData($loadedAttrs['7']);
            }
        } else {
            if ($weightUnit == 'grama') {
                $weight = $weight / 1000;
            } else {
                $weight = (int) $product->getData($loadedAttrs['7']);
            }
        }

        $productControl = $this->storeConfig->getValue('integracommerce/general/sku_control', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        if ($productControl == 'sku') {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $productStatus = $product->getStatus();
        if ($productStatus == 2) {
            $skuStatus = false;
        } else {
            $skuStatus = true;
        }

        $body = [
            "idSku" => $idSku,
            "IdSkuErp" => $product->getData('sku'),
            "idProduct" => $productId,
            "Name" => $product->getName(),
            "Description" => $product->getData('description'),
            "Height" => $heightValue,
            "Width" => $widthValue,
            "Length" => $lengthValue,
            "Weight" => $weight,
            "CodeEan" => ($loadedAttrs['8'] == 'not_selected' ? "" : $product->getData($loadedAttrs['8'])),
            "CodeNcm" => ($loadedAttrs['9'] == 'not_selected' ? "" : $product->getData($loadedAttrs['9'])),
            "CodeIsbn" => ($loadedAttrs['10'] == 'not_selected' ? "" : $product->getData($loadedAttrs['10'])),
            "CodeNbm" => ($loadedAttrs['1'] == 'not_selected' ? "" : $product->getData($loadedAttrs['1'])),
            "Variation" => "",
            "StockQuantity" => $stockQuantity,
            "Status" => $skuStatus,
            "Price" => [
                "ListPrice" => ($normalPrice < $specialPrice ? $specialPrice : $normalPrice),
                "SalePrice" => $specialPrice
            ],  
            "UrlImages" => $pictures,  
            "Attributes" => $_attrs
        ];  

        $jsonBody = json_encode($body);

        if ($product->getData('integracommerce_active') == 0) {
            $return = self::callCurl("POST", $url, $jsonBody);
        } elseif ($product->getData('integracommerce_active') == 1) {
            $return = self::callCurl("PUT", $url, $jsonBody);
        }

        $productId = $product->getId();

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return [$jsonBody, $return, $product->getId()];
        }

        if ($product->getData('integracommerce_active') == 0) {
            $this->productAction->updateAttributes(
                [$product->getId()],
                ['integracommerce_active' => 1],
                0
            );
        }
    } 

    public static function getOrders()
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        $url = "https://" . $environment . ".integracommerce.com.br/api/Order?page=1&perPage=10&status=approved";

        $return = self::callCurl("GET", $url, null);

        return $return;
    } 

    public static function updatePrice($product)
    {
        $environment = $this->storeConfig->getValue('integracommerce/general/environment', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        if ($product->getTypeId() == 'simple') {
            $configurableIds = $this->typeConfigurableFactory->create()->getParentIdsByChild($product->getId());
        }

        $configProd = $this->storeConfig->getValue('integracommerce/general/configprod', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());

        $normalPrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        if (!$specialPrice || empty($specialPrice)) {
            $specialPrice = $normalPrice;
        }

        if (!$normalPrice || empty($normalPrice) || $normalPrice < 1) {
            if (!empty($configurableIds) && $configProd == 1) {
                foreach ($configurableIds as $configurableId) {
                    $configurableProduct = $this->modelProductFactory->create()->load($configurableId);
                    $normalPrice = $configurableProduct->getPrice();
                    $specialPrice = $configurableProduct->getSpecialPrice();
                    if (!$specialPrice || empty($specialPrice)) {
                        $specialPrice = $normalPrice;
                    }
                }
            }
        }

        $productControl = $this->storeConfig->getValue('integracommerce/general/sku_control', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        if ($productControl == 'sku') {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $body = [];
        array_push(
            $body, [
                'IdSku' => $idSku,
                'ListPrice' => ($normalPrice < $specialPrice ? $specialPrice : $normalPrice),
                'SalePrice' => $specialPrice
            ]
        );

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Price';

        $return = self::callCurl("PUT", $url, $jsonBody);

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return [$jsonBody, $return, $product->getId()];
        }
    }

    public static function checkError($jsonBody = null, $response = null, $productId = null, $delete = null, $type = null)
    {
        $errorQueue = $this->modelUpdateFactory->create()->load($productId, 'product_id');

        $errorProductId = $errorQueue->getProductId();

        if ($delete == 1 && !empty($errorProductId)) {
            $errorQueue->delete();
            return;
        }

        if (empty($errorProductId) && $delete !== 1) {
            $errorQueue = $this->modelUpdateFactory->create();
            $errorQueue->setProductId($productId);
        }

        if (empty($response) && empty($jsonBody)) {
            return;
        }

        if (is_array($response)) {
            if (!empty($response['Errors'])) {
                foreach ($response['Errors'] as $error) {
                    $response = $error['Message'] . '. ';
                };
            } else {
                $response = json_encode($response);
            }
        }

        if ($type == 'product') {
            $errorQueue->setProductBody($jsonBody);
            $errorQueue->setProductError($response);
        } elseif ($type == 'sku') {
            $errorQueue->setSkuBody($jsonBody);
            $errorQueue->setSkuError($response);            
        } elseif ($type == 'price') {
            $errorQueue->setPriceBody($jsonBody);
            $errorQueue->setPriceError($response);
        } elseif ($type == 'stock') {
            $errorQueue->setStockBody($jsonBody);
            $errorQueue->setStockError($response);
        }

        $errorQueue->save();
    }

    public static function callCurl($method, $url, $body = null)
    {
        $apiUser = $this->storeConfig->getValue('integracommerce/general/api_user', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $apiPassword = $this->storeConfig->getValue('integracommerce/general/api_password', ScopeInterface::SCOPE_STORE, $this->modelStoreManagerInterface->getStore());
        $authentication = base64_encode($apiUser . ':' . $apiPassword);

        $headers = [
            "Content-type: application/json",
            "Accept: application/json",
            "Authorization: Basic " . $authentication
        ];

        if ($method == "GET") {
            $zendMethod = Client::GET;
        } elseif ($method == "POST") {
            $zendMethod = Client::POST;
        } elseif ($method == "PUT") {
            $zendMethod = Client::PUT;
        }

        $connection = new Curl();
        if ($method == "PUT") {
            //ADICIONA AS OPTIONS MANUALMENTE POIS NATIVAMENTE O WRITE NAO VERIFICA POR PUT
            $connection->addOption(CURLOPT_CUSTOMREQUEST, "PUT");
            $connection->addOption(CURLOPT_POSTFIELDS, $body);
        }

        $connection->setConfig([
            'timeout'   => 30
        ]);

        $connection->write($zendMethod, $url, '1.0', $headers, $body);
        $response = $connection->read();
        $connection->close();

        $httpCode = Response::extractCode($response);
        $response = Response::extractBody($response);

        $response = json_decode($response, true);

        $response['httpCode'] = $httpCode;

        return $response;
    }

}