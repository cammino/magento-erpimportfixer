<?php

class Cammino_Erpimportfixer_ErpimportfixerController extends Mage_Core_Controller_Front_Action
{

    public function groupAction()
    {
        $analized = [];
        $allProducts = Mage::getModel('catalog/product')->getCollection();

        //Inicializando atributos
        $colorAttribute = Mage::getSingleton('eav/config')
            ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'color');
        if ($colorAttribute->usesSource()) {
            $colorOptions = $colorAttribute->getSource()->getAllOptions(false);
        } else {
            $colorOptions = [];
        }
        $tamanhoAttribute = Mage::getSingleton('eav/config')
            ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'tamanho');
        if ($tamanhoAttribute->usesSource()) {
            $tamanhoOptions = $tamanhoAttribute->getSource()->getAllOptions(false);
        } else {
            $tamanhoOptions = [];
        }


        foreach ($allProducts as $parentProduct) {
            $skuTag = $parentProduct->getSku();
            //Encontra os produtos filhos através do sku do pai no começo
            $similarProducts = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('sku', array(
                array('like' => $skuTag . '-%')
            ));
            if ((!in_array($skuTag, $analized)) && ($similarProducts->count() > 0)) {
                $parentProductObj = Mage::getModel('catalog/product')->load($parentProduct->getId());
                $children = [];
                foreach ($similarProducts as $childProduct) {
                    $childProductObj = Mage::getModel('catalog/product')->load($childProduct->getId());
                    $child = ['id' => $childProduct->getId(), 'name' => $childProductObj->getName()];
                    $nameDiff = str_replace($parentProductObj->getName(), "", $childProductObj->getName());
                    $attributesFromChild = explode(" - ",$nameDiff);
                    foreach ($attributesFromChild as $attr) {
                        $attr = preg_replace('/^([0-9]{1,})\-/', '', $attr);
                        $attr = trim($attr," ");
                        foreach($colorOptions as $colorOption) {
                            if ($attr == $colorOption['label']) {
                                $child['color'] = $attr;
                                $child['color_val'] = $colorOption['value'];
                                $childProductObj->setColor($colorOption['value']);
                                break;
                            }
                        }
                        foreach($tamanhoOptions as $tamanhoOption) {
                            if ($attr == $tamanhoOption['label']) {
                                $child['tamanho'] = $attr;
                                $child['tamanho_val'] = $tamanhoOption['value'];
                                $childProductObj->setTamanho($tamanhoOption['value']);
                                break;
                            }
                        }
                    }
                    $childProductObj->save();
                    $children[] = $child;
                }
                Mage::log($children, null, 'erpimportfixer.log');
                $newProduct = $this->createNewConfigurableProduct($parentProductObj, $children);
                //Após, excluir o $parentProduct
            }
            $analized[] = $skuTag;
        }
    }

    private function createNewConfigurableProduct($templateProduct, $children)
    {
        $configProduct = Mage::getModel('catalog/product');
        try {
            $configProduct
                ->setStoreId($templateProduct->getStoreId())
                ->setWebsiteIds($templateProduct->getWebsiteIds())
                ->setAttributeSetId($templateProduct->getAttributeSetId())
                ->setTypeId('configurable')
                ->setCreatedAt(strtotime('now'))
                ->setSku($templateProduct->getSku())
                ->setName($templateProduct->getName())
                ->setWeight($templateProduct->getWeight())
                ->setStatus($templateProduct->getStatus())
                ->setTaxClassId($templateProduct->getTaxClassId())
                ->setVisibility($templateProduct->getVisibility())
                ->setPrice($templateProduct->getPrice())
                ->setCost($templateProduct->getCost())
                ->setSpecialPrice($templateProduct->getSpecialPrice())
                ->setSpecialFromDate($templateProduct->getSpecialFromDate())
                ->setSpecialToDate($templateProduct->getSpecialToDate())
                ->setDescription($templateProduct->getDescription())
                ->setShortDescription($templateProduct->getShortDescription())
                ->setMediaGallery($templateProduct->getMediaGallery())
                ->setStockData($templateProduct->getStockData())
                ->setCategoryIds($templateProduct->getCategoryIds());
            /**/
            /** assigning associated product to configurable */
            /**/
            if (!empty($children[0]['color_val']) && !empty($children[0]['tamanho_val'])) {
                $configProduct->getTypeInstance()->setUsedProductAttributeIds(array(92, 155));
                $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
                $configProduct->setCanSaveConfigurableAttributes(true);
                $configProduct->setConfigurableAttributesData($configurableAttributesData);
                $configurableProductsData = array();
                foreach($children as $child) {
                    $configurableProductsData[$child['id']] = array(
                        '0' => array(
                            'label' => $child['color'],
                            'attribute_id' => '92', //id da cor
                            'value_index' => $child['color_val'],
                            'is_percent' => '0',
                            'pricing_value' => '0'
                        ),
                        '1' => array(
                            'label' => $child['tamanho'],
                            'attribute_id' => '155', //id do tamanho
                            'value_index' => $child['tamanho_val'],
                            'is_percent' => '0',
                            'pricing_value' => '0'
                        ),    
                    );
                }
            } else if (!empty($children[0]['color_val'])) {
                $configProduct->getTypeInstance()->setUsedProductAttributeIds(array(92));
                $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
                $configProduct->setCanSaveConfigurableAttributes(true);
                $configProduct->setConfigurableAttributesData($configurableAttributesData);
                $configurableProductsData = array();
                foreach($children as $child) {
                    $configurableProductsData[$child['id']] = array(
                        '0' => array(
                            'label' => $child['color'],
                            'attribute_id' => '92', //id da cor
                            'value_index' => $child['color_val'],
                            'is_percent' => '0',
                            'pricing_value' => '0'
                        ),
                    );
                }
            } else if (!empty($children[0]['tamanho_val'])) {
                $configProduct->getTypeInstance()->setUsedProductAttributeIds(array(155));
                $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
                $configProduct->setCanSaveConfigurableAttributes(true);
                $configProduct->setConfigurableAttributesData($configurableAttributesData);
                $configurableProductsData = array();
                foreach($children as $child) {
                    $configurableProductsData[$child['id']] = array(
                        '0' => array(
                            'label' => $child['tamanho'],
                            'attribute_id' => '155', //id da cor
                            'value_index' => $child['tamanho_val'],
                            'is_percent' => '0',
                            'pricing_value' => '0'
                        ),
                    );
                }
            }
            $configProduct->setConfigurableProductsData($configurableProductsData);
            $configProduct->save();
            return $configProduct;
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            echo $e->getMessage();
        }
    }
}
