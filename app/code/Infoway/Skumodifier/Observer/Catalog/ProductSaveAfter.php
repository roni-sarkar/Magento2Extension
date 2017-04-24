<?php

/**
 * Modify Product SKU by Category, Attribute Set
 * Copyright (C) 2017  
 * 
 * This file included in Infoway/Skumodifier is licensed under OSL 3.0
 * 
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Infoway\Skumodifier\Observer\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductSaveAfter implements \Magento\Framework\Event\ObserverInterface {

    protected $_categoryloader;
    protected $_attributeSet;
    protected $_scopeConfig;
    protected $_productloader;

    /**
     * @param Filesystem                                       $filesystem,
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     */
    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Catalog\Model\CategoryFactory $_categoryloader, \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet, \Magento\Catalog\Model\ProductFactory $_productloader
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_categoryloader = $_categoryloader;
        $this->_attributeSet = $attributeSet;
        $this->_productloader = $_productloader;
    }

    public function getLoadCategory($id) {
        return $this->_categoryloader->create()->load($id);
    }

    public function getLoadProduct($id) {
        return $this->_productloader->create()->load($id);
    }

    public function getAttributeSet($id) {
        return $this->_attributeSet->get($id);
    }

    /**
     * @access public
     * @param type $config_path
     * @return type object
     * @uses getConfigValue(), It retuens Sytem Configuration
     */
    public function getConfigValue($config_path) {
        return $this->_scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
    \Magento\Framework\Event\Observer $observer
    ) {
        if ($this->getConfigValue('sku_modifier/general/is_active')) {
            $_product = $observer->getProduct();  // you will get product object
            $_sku = $_product->getSku(); // for sku
            $new_sku = array();
            $modifierActive = false;

            if ($this->getConfigValue('sku_modifier/attributeset/is_attributeset_prefix')) {
                $_attributeSetId = $_product->getAttributeSetId();
                $_attributeSet = $this->getAttributeSet($_attributeSetId);
                $_noOfLetter = ($this->getConfigValue('sku_modifier/attributeset/attributeset_noofletter')) ?
                        $this->getConfigValue('sku_modifier/attributeset/attributeset_noofletter') : 7;
                $_attributeSetName = substr(strip_tags(trim($_attributeSet->getAttributeSetName())), 0, $_noOfLetter);
                if ($this->getConfigValue('sku_modifier/attributeset/attributeset_position')) {
                    $new_sku[(int) $this->getConfigValue('sku_modifier/attributeset/attributeset_position')] = strtoupper($_attributeSetName);
                } else {
                    $new_sku[] = strtoupper($_attributeSetName);
                }

                $modifierActive = true;
            }
            if ($this->getConfigValue('sku_modifier/category/is_category_prefix')) {
                $_categoryIds = $_product->getCategoryIds();
                $_category = $this->getLoadCategory(end($_categoryIds));
                $_noOfLetter = ($this->getConfigValue('sku_modifier/category/category_noofletter')) ?
                        $this->getConfigValue('sku_modifier/category/category_noofletter') : 7;
                $_categoryName = substr(strip_tags(trim($_category->getName())), 0, $_noOfLetter);
                if ($this->getConfigValue('sku_modifier/category/category_position')) {
                    $new_sku[(int) $this->getConfigValue('sku_modifier/category/category_position')] = strtoupper($_categoryName);
                } else {
                    $new_sku[] = strtoupper($_categoryName);
                }
                $modifierActive = true;
            }
            if ($this->getConfigValue('sku_modifier/customtext/is_customtext_prefix') && $this->getConfigValue('sku_modifier/customtext/customtext_value') != '') {
                $_customText = strip_tags(trim($this->getConfigValue('sku_modifier/customtext/customtext_value')));
                if ($this->getConfigValue('sku_modifier/customtext/customtext_position')) {
                    $new_sku[(int) $this->getConfigValue('sku_modifier/customtext/customtext_position')] = strtoupper($_customText);
                } else {
                    $new_sku[] = strtoupper($_customText);
                }
                $modifierActive = true;
            }
            ksort($new_sku);
            $new_sku = implode('-', $new_sku);
            if ($modifierActive) {
                $new_sku .= '-' . $_product->getId();
                $product = $this->getLoadProduct($_product->getId());
                $product->setData('sku', $new_sku);
                $product->save($_product);
                //$_product->getResource()->saveAttribute($_product, 'sku');
            }

//            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
//            $logger = new \Zend\Log\Logger();
//            $logger->addWriter($writer);
//            $logger->info($new_sku);
        }
    }

}
