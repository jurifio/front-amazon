<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CMarketplaceAccountHasProduct;
use bamboo\domain\entities\CMarketplaceAccountHasProductSku;
use bamboo\domain\entities\CPrestashopHasProductHasMarketplaceHasShop;
use bamboo\domain\entities\CPrestashopHasProduct;
use bamboo\domain\entities\CMarketplaceAccount;

/**
 * Class CAmazonProductFeedBuilder
 * @package bamboo\amazon\business
 *
 * @author Bambooshoot Team <emanuele@bambooshoot.agency>
 *
 * @copyright (c) Bambooshoot snc - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @date 27/07/2016
 * @since 1.0
 */
class CAmazonProductFeedBuilder extends AAmazonFeedBuilder
{
    protected $feedTypeName = '_POST_PRODUCT_DATA_';

    /**
     * @param CObjectCollection $prestashopHasProductHasMarketplaceHasShops
     * @param CMarketplaceAccount $marketplaceAccount
     * @param bool $indent
     * @return $this
     */
    public function prepare(CMarketplaceAccount $marketplaceAccount,CObjectCollection $prestashopHasProductHasMarketplaceHasShops,$indent = false)
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);
        $writer->writeElement('MessageType','Product');
        $writer->writeElement('PurgeAndReplace','true');
        $i = 0;
        foreach ($prestashopHasProductHasMarketplaceHasShops as $prestashopHasProductHasMarketplaceHasShop) {
            $i++;
            $writer->startElement('Message');
            $writer->writeElement('MessageID',$i);
            $writer->writeElement('OperationType','Update');
            $writer->startElement('Product');
            $writer->writeRaw($this->writeParentProduct($prestashopHasProductHasMarketplaceHasShop,$marketplaceAccount,$indent));
            $writer->endElement();
            $writer->endElement();
            $marketplaceAccountHasProductSkus = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findBy(['productId' => $prestashopHasProductHasMarketplaceHasShop->productId,'productVariantId' => $prestashopHasProductHasMarketplaceHasShop->productVariantId,'marketplaceId' => $marketplaceAccount->marketplaceId,'marketplaceAccountId' => $marketplaceAccount->id]);
            foreach ($marketplaceAccountHasProductSkus as $marketplaceAccountHasProductSku) {
                $i++;
                $writer->startElement('Message');
                $writer->writeElement('MessageID',$i);
                $writer->writeElement('OperationType','Update');
                $writer->startElement('Product');
                $writer->writeRaw($this->writeChildProduct($marketplaceAccountHasProductSku,$marketplaceAccount,$indent));
                $writer->endElement();
                $writer->endElement();

            }
        }
        $this->rawBody = $writer->outputMemory();
        return $this;
    }

    protected function writeProductData($productIncoming,$marketplaceAccount,$indent = false)
    {
        if ($productIncoming instanceof CPrestashopHasProductHasMarketplaceHasShop) {
            $prestashopHasProductHasMarketplaceHasShop = $productIncoming;
            $isParent = true;
            $product = $prestashopHasProductHasMarketplaceHasShop->product;
        } elseif ($productIncoming instanceof CMarketplaceAccountHasProductSku) {
            $isParent = false;
            //   $prestashopHasProductHasMarketplaceHasShop = $productIncoming->prestashopHasProductHasMarketplaceHasShop;
            $prestashopHasProductHasMarketplaceHasShop = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findOneBy(['productId' => $productIncoming->productId,'productVariantId' => $productIncoming->productVariantId,'marketplaceId' => $marketplaceAccount->marketplaceId,'marketplaceAccountId' => $marketplaceAccount->id]);
            //   $product=$prestashopHasProductHasMarketplaceHasShop->product;
            $product = \Monkey::app()->repoFactory->create('Product')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productId,'productVariantId' => $prestashopHasProductHasMarketplaceHasShop->productVariantId]);
        } else {
            throw new \Exception('olè');
        }


        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);

        $writer->writeElement('ProductTaxCode','A_GEN_TAX');
        $writer->writeElement('LaunchDate',(new \DateTime())->format(DATE_ATOM));
        $writer->writeElement('ReleaseDate',(new \DateTime())->format(DATE_ATOM));
        $writer->startElement('Condition');
        $writer->writeElement('ConditionType','New');
        $writer->endElement();
        $writer->startElement('DescriptionData');
        $writer->writeElement('Title',$product->getName());
        $writer->writeElement('Brand',$product->productBrand->name);
        $writer->writeElement('Description',$product->getDescription());
        $max = 5;
        $current = 0;
        foreach ($product->productSheetActual as $sheetPage) {
            $current++;
            if ($current > $max) break;
            try {
                $writer->writeElement('BulletPoint',$sheetPage->productDetail->productDetailTranslation->getFirst()->name);
            } catch (\Throwable $e) {
            }

        }
        $writer->writeElement('Manufacturer',$product->productBrand->name);
        $writer->writeElement('MfrPartNumber',$product->itemno);

        /*  $writer->writeElement('SearchTerms',$product->productBrand->name);
          $writer->writeElement('SearchTerms',$product->itemno);*/
        $writer->writeElement('ItemType',$product->productCategory->getFirst()->getLocalizedName());
        $writer->writeElement('IsGiftWrapAvailable','true');
        $writer->writeElement('IsGiftMessageAvailable','true');

        $mCategoryIds = [];
        foreach ($product->productCategory as $category) {
            foreach ($category->marketplaceAccountCategory->findByKeys(['marketplaceId' => $marketplaceAccount->marketplaceId,'marketplaceAccountId' => $marketplaceAccount->id,'isRelevant' => 1]) as $mCategory) {
                $mCategoryIds[] = $mCategory;
            }
        }
        $category = $mCategoryIds[0];
        $writer->writeElement('RecommendedBrowseNode',$category->marketplaceCategoryId);
        $writer->endElement();
        $writer->endElement();
        $writer->startElement('ProductData');
        $findTypeBuilder = $product->getLocalizedProductCategories('<br>','/');
        $builder = 'buildClothingAccessories';
        if (strpos($findTypeBuilder,'Calzature') !== false) {
            $builder = 'buildShoes';
        }


        if (strpos($findTypeBuilder,'Abbigliamento')  !== false) {
            $builder = 'buildClothingAccessories';
        }
        if (strpos($findTypeBuilder,'Accessori')  !== false) {
            $builder = 'buildClothingAccessories';
        }
        if (strpos($findTypeBuilder,'Borse')  !== false) {
            $builder = 'buildClothingAccessories';
        }


        //$builder = "build" . ucfirst($category->config['productDataElement']);
        $builder = "buildClothingAccessories";
        if (method_exists($this,$builder) && is_callable(array($this,$builder))) {
            if ($isParent) {
                $writer->writeRaw($this->$builder($prestashopHasProductHasMarketplaceHasShop,$marketplaceAccount,$indent));
            } else {
                $writer->writeRaw($this->$builder($productIncoming,$marketplaceAccount,$indent));
            }

        } else {

        }
        $writer->endElement();
        return $writer->outputMemory();
    }

    /**
     * @param CPrestashopHasProductHasMarketplaceHasShop $prestashopHasProductHasMarketplaceHasShop
     * @param CMarketplaceAccount $marketplaceAccount
     * @param bool $indent
     * @return string
     */
    protected
    function writeParentProduct(CPrestashopHasProductHasMarketplaceHasShop $prestashopHasProductHasMarketplaceHasShop,CMarketplaceAccount $marketplaceAccount,$indent = false)
    {
        $product = $prestashopHasProductHasMarketplaceHasShop->product;

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);
        $writer->writeElement('SKU',$product->printId());
        $writer->writeRaw($this->writeProductData($prestashopHasProductHasMarketplaceHasShop,$marketplaceAccount,$indent));

        return $writer->outputMemory();
    }

    protected
    function writeChildProduct(CMarketplaceAccountHasProductSku $marketplaceAccountHasProductSku,$marketplaceAccount,$indent = false)
    {
        // $marketplaceAccountHasProduct = $marketplaceAccountHasProductSku->marketplaceAccountHasProduct;
        $productSkuSample = $marketplaceAccountHasProductSku->productSku->getFirst();

        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);
        $writer->writeElement('SKU',$productSkuSample->printPublicSku());
        $writer->startElement('StandardProductID');
        $writer->writeElement('Type','EAN');
        $writer->writeElement('Value',$productSkuSample->ean);
        $writer->endElement();
        $writer->writeRaw($this->writeProductData($marketplaceAccountHasProductSku,$marketplaceAccount,$indent));
        return $writer->outputMemory();
    }

    protected
    function buildShoes($product,$marketplaceAccount,$indent = false)
    {
        if ($product instanceof CPrestashopHasProductHasMarketplaceHasShop) {
            $isParent = true;
        } elseif ($product instanceof CMarketplaceAccountHasProductSku) {
            $isParent = false;
            $prestashopHasProductHasMarketplaceHasShop = $product;
            //  $product = $marketplaceProductSku->prestashopHasProductHasMarketplaceHasShop;
            /*  $prestashopHasProductHasMarketplaceHasShop = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findBy(['productId' => $marketplaceProductSku->productId,'productVariantId' => $marketplaceProductSku->productVariantId,'marketplaceHasShopId' => $marketplaceAccount->config['marketplaceHasShopId']]);
              $product = $prestashopHasProductHasMarketplaceHasShop;
              //   $prestashopHasProductHasMarketplaceHasShop = $productIncoming->prestashopHasProductHasMarketplaceHasShop;*/
            $prestashopHasProductHasMarketplaceHasShop = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findOneBy(['productId' => $product->productId,'productVariantId' => $product->productVariantId,'productSizeId' => $product->productSizeId,'marketplaceId' => $marketplaceAccount->marketplaceId,'marketplaceAccountId' => $marketplaceAccount->id]);
            //   $product=$prestashopHasProductHasMarketplaceHasShop->product;
            $product = \Monkey::app()->repoFactory->create('Product')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productId,'productVariantId' => $prestashopHasProductHasMarketplaceHasShop->productVariantId]);

        } else throw new \Exception('olè');
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);
        $writer->startElement('Shoes');
        $writer->writeElement('ClothingType','Shoes');

        $writer->startElement('VariationData');
        $writer->writeElement('Parentage',$isParent ? 'parent' : 'child');
        if (!$isParent) {
            $productSize = \Monkey::app()->repoFactory->create('ProductSize')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productSizeId]);
            $writer->writeElement('Size',$productSize->name);
            $writer->writeElement('Color',$product->productVariant->name);
        }
        if ($product instanceof CPrestashopHasProductHasMarketplaceHasShop) {
            $writer->writeElement('Color',$product->product->productVariant->name);
        }
        $writer->writeElement('VariationTheme','Size');
        $writer->endElement();
        $writer->startElement('ClassificationData');
        if (!$isParent) {
            $productSize = \Monkey::app()->repoFactory->create('ProductSize')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productSizeId]);
            if (!is_null($productSize)) {
                $writer->writeElement('ColorMap',$product->productVariant->name);
                $writer->writeElement('SizeMap',$productSize->name);
                $writer->writeElement('IsAdultProduct','false');
                $writer->writeElement('MaterialType','Leather');
                // $writer->writeElement('sizeColor',$product->productVariant->name);
                $writer->writeElement('TargetGender','unisex');

            } else {
                $writer->writeElement('ColorMap',$product->productVariant->name);
                $writer->writeElement('SizeMap',$prestashopHasProductHasMarketplaceHasShop->productSku->getFirst()->productSize->name);

                $writer->writeElement('IsAdultProduct','false');
                $writer->writeElement('MaterialType','Leather');
                $writer->writeElement('TargetGender','unisex');


            }
        }
        // $writer->writeElement('target_gender','Female');

        $writer->endElement();
        if (!$isParent) {
            $writer->startElement('ShoeSizeComplianceData');
            $writer->writeElement('AgeRangeDescription','adult');
            $writer->writeElement('FootwearSizeSystem','eu_footwear_size_system');
            $writer->writeElement('ShoeSizeAgeGroup','adult');
            $writer->writeElement('ShoeSizeGender','women');
            $writer->writeElement('ShoeSizeClass','numeric');
            $writer->writeElement('ShoeSizeWidth','medium');
            $productSize = \Monkey::app()->repoFactory->create('ProductSize')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productSizeId]);
            if (!is_null($productSize)) {
                $writer->writeElement('ShoeSize','numeric_' . $productSize->name);
            } else {
                $writer->writeElement('ShoeSize','numeric_' . $prestashopHasProductHasMarketplaceHasShop->productSku->getFirst()->productSize->name);
            }
            $writer->endElement();
        }


        $writer->endElement();
        return $writer->outputMemory();
    }

    protected
    function buildClothingAccessories($product,$marketplaceAccount,$indent = false)
    {
        if ($product instanceof CPrestashopHasProductHasMarketplaceHasShop) {
            $isParent = true;
        } elseif ($product instanceof CMarketplaceAccountHasProductSku) {
            $isParent = false;
            $prestashopHasProductHasMarketplaceHasShop = $product;
            //  $product = $marketplaceProductSku->prestashopHasProductHasMarketplaceHasShop;
            /*  $prestashopHasProductHasMarketplaceHasShop = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findBy(['productId' => $marketplaceProductSku->productId,'productVariantId' => $marketplaceProductSku->productVariantId,'marketplaceHasShopId' => $marketplaceAccount->config['marketplaceHasShopId']]);
              $product = $prestashopHasProductHasMarketplaceHasShop;
              //   $prestashopHasProductHasMarketplaceHasShop = $productIncoming->prestashopHasProductHasMarketplaceHasShop;*/
            $prestashopHasProductHasMarketplaceHasShop = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findOneBy(['productId' => $product->productId,'productVariantId' => $product->productVariantId,'productSizeId' => $product->productSizeId,'marketplaceId' => $marketplaceAccount->marketplaceId,'marketplaceAccountId' => $marketplaceAccount->id]);
            //   $product=$prestashopHasProductHasMarketplaceHasShop->product;
            $product = \Monkey::app()->repoFactory->create('Product')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productId,'productVariantId' => $prestashopHasProductHasMarketplaceHasShop->productVariantId]);

        } else throw new \Exception('olè');
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent($indent);
        $writer->startElement('ClothingAccessories');

        $writer->startElement('VariationData');
        $writer->writeElement('Parentage',$isParent ? 'parent' : 'child');
        $writer->startElement('VariationData');
        $writer->writeElement('VariationTheme','Size');
        $writer->endElement();
        $writer->startElement('ClassificationData');
        if (!$isParent) {
            $productSize = \Monkey::app()->repoFactory->create('ProductSize')->findOneBy(['id' => $prestashopHasProductHasMarketplaceHasShop->productSizeId]);
            if (!is_null($productSize)) {
                $findTypeDepartment = $product->getLocalizedProductCategories('<br>','/');
                $department = '';
                if (strpos($findTypeDepartment,'Uomo') !== False) {
                    $department = 'uomo';
                }

                if (strpos($findTypeDepartment,'Donna') !== False) {
                    $department = 'donna';
                }

                $writer->writeElement('Department',$department);
                $writer->writeElement('ColorMap',$product->productVariant->name);
                $writer->writeElement('SizeMap',$productSize->name);
                $writer->writeElement('IsAdultProduct','false');
                $writer->writeElement('MaterialType','Leather');
                // $writer->writeElement('sizeColor',$product->productVariant->name);
                $writer->writeElement('TargetGender','unisex');

            } else {
                $findTypeDepartment = $product->getLocalizedProductCategories('<br>','/');
                switch (true) {
                    case (strpos($findTypeDepartment,'Uomo')):
                        $department = 'uomo';
                        break;
                    case (strpos($findTypeDepartment,'Donna')):
                        $department = 'donna';
                        break;
                    default:
                        $department = 'uomo';

                }
                $writer->writeElement('Department',$department);
                $writer->writeElement('ColorMap',$product->productVariant->name);
                $writer->writeElement('SizeMap',$prestashopHasProductHasMarketplaceHasShop->productSku->getFirst()->productSize->name);

                $writer->writeElement('IsAdultProduct','false');
                $writer->writeElement('MaterialType','Leather');
                $writer->writeElement('TargetGender','unisex');


            }
        }
        // $writer->writeElement('target_gender','Female');

        $writer->endElement();


        $writer->endElement();
        return $writer->outputMemory();
    }
}