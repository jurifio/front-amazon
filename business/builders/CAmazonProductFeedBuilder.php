<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CMarketplaceAccountHasProduct;
use bamboo\domain\entities\CMarketplaceAccountHasProductSku;

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
	/**
	 * @param CObjectCollection $marketplaceAccountHasProducts
	 * @param bool $indent
	 * @return $this
	 */
	public function prepare(CObjectCollection $marketplaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('MessageType','Product');
		$i = 0;
		foreach ($marketplaceAccountHasProducts as $marketplaceAccountHasProduct)
		{
			$i++;
			$writer->startElement('Message');
			$writer->writeElement('MessageID',$i);
			$writer->writeElement('OperationType','Update');
			$writer->startElement('Product');
			$writer->writeRaw($this->writeParentProduct($marketplaceAccountHasProduct,$indent));
			$writer->endElement();
			$writer->endElement();

			foreach($marketplaceAccountHasProduct->marketplaceAccountHasProductSku as $marketplaceAccountHasProductSku) {
				$i++;
				$writer->startElement('Message');
				$writer->writeElement('MessageID',$i);
				$writer->writeElement('OperationType','Update');
				$writer->startElement('Product');
				$writer->writeRaw($this->writeChildProduct($marketplaceAccountHasProductSku,$indent));
				$writer->endElement();
				$writer->endElement();

			}
		}
		$this->rawBody = $writer->outputMemory();
		return $this;
	}

	protected function writeProductData($productIncoming, $indent = false)
	{
		if($productIncoming instanceof CMarketplaceAccountHasProduct) {
			$marketplaceAccountHasProduct = $productIncoming;
			$isParent = true;
		} elseif($productIncoming instanceof CMarketplaceAccountHasProductSku) {
			$isParent = false;
			$marketplaceAccountHasProduct = $productIncoming->marketplaceAccountHasProduct;
		} else throw new \Exception('olè');

		$product = $marketplaceAccountHasProduct->product;

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
		foreach ($product->productSheetActual as $sheetPage) {
			try{
				$writer->writeElement('BulletPoint',$sheetPage->productDetail->productDetailTranslation->getFirst()->name);
			} catch (\Exception $e){}

		}
		$writer->writeElement('Manufacturer',$product->productBrand->name);
		$writer->writeElement('MfrPartNumber',$product->itemno);

		$writer->writeElement('SearchTerms',$product->productBrand->name);
		$writer->writeElement('SearchTerms',$product->itemno);
		$writer->writeElement('ItemType',$product->productCategory->getFirst()->getLocalizedName());
		$writer->writeElement('IsGiftWrapAvailable','true');
		$writer->writeElement('IsGiftMessageAvailable','true');
		$writer->endElement();
		$writer->endElement();
		$mCategoryIds = [];
		foreach ($product->productCategory as $category ) {
			foreach ($category->marketplaceAccountCategory->findByKeys(['marketplaceId'=>$marketplaceAccountHasProduct->marketplaceId,'marketplaceAccountId'=>$marketplaceAccountHasProduct->marketplaceAccountId,'isRelevant'=>1]) as $mCategory) {
				$mCategoryIds[] = $mCategory;
			}
		}
		//$category = $mCategoryIds[0];
		//$writer->writeElement('RecommendedBrowseNode',$category->marketplaceCategoryId);
		$writer->startElement('ProductData');

		//$builder = "build" . ucfirst($category->config['productDataElement']);
		$builder = "buildShoes";
		if (method_exists($this, $builder) && is_callable(array($this, $builder))) {
			if($isParent) {
				$writer->writeRaw($this->$builder($marketplaceAccountHasProduct,$indent));
			} else {
				$writer->writeRaw($this->$builder($productIncoming,$indent));
			}

		} else {

		}
		$writer->endElement();
		return $writer->outputMemory();
	}

	/**
	 * @param CMarketplaceAccountHasProduct $marketplaceAccountHasProduct
	 * @param bool $indent
	 * @return string
	 */
	protected function writeParentProduct(CMarketplaceAccountHasProduct $marketplaceAccountHasProduct, $indent = false)
	{
		$product = $marketplaceAccountHasProduct->product;

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$product->printId());
		$writer->startElement('StandardProductID');
		$writer->writeElement('Type','EAN');
		$writer->writeElement('Value','abracadabra');
		$writer->endElement();
		$writer->writeRaw($this->writeProductData($marketplaceAccountHasProduct, $indent));

		return $writer->outputMemory();
	}

	protected function writeChildProduct(CMarketplaceAccountHasProductSku $marketplaceAccountHasProductSku, $indent = false)
	{
		$marketplaceAccountHasProduct = $marketplaceAccountHasProductSku->marketplaceAccountHasProduct;
		$productSkuSample = $marketplaceAccountHasProductSku->productSku->getFirst();
		$product = $marketplaceAccountHasProduct->product;

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$productSkuSample->printPublicSku());
		$writer->startElement('StandardProductID');
		$writer->writeElement('Type','EAN');
		$writer->writeElement('Value',empty($productSkuSample->ean) ? '0000000000001' : $productSkuSample->barcode);
		$writer->endElement();
		$writer->writeRaw($this->writeProductData($marketplaceAccountHasProductSku, $indent));
		return $writer->outputMemory();
	}

	protected function buildShoes($product, $indent = false)
	{
		if($product instanceof CMarketplaceAccountHasProduct) {
			$isParent = true;
		} elseif($product instanceof CMarketplaceAccountHasProductSku) {
			$isParent = false;
			$marketplaceProductSku = $product;
			$product = $marketplaceProductSku->marketplaceAccountHasProduct;
		} else throw new \Exception('olè');
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->startElement('Shoes');
		$writer->writeElement('ClothingType','Shoes');

		$writer->startElement('VariationData');
		$writer->writeElement('Parentage',$isParent ? 'parent' : 'child');
		if(!$isParent) {
			$writer->writeElement('Size',$marketplaceProductSku->productSku->getFirst()->productSize->name);
		}
		$writer->writeElement('VariationTheme','Size');
		$writer->endElement();
		$writer->startElement('ClassificationData');
		$writer->endElement();
		$writer->endElement();
		return $writer->outputMemory();
	}

	protected function buildClothingAccessories($product, $indent = false)
	{
		if($product instanceof CMarketplaceAccountHasProduct) {
			$isParent = true;
		} elseif($product instanceof CMarketplaceAccountHasProductSku) {
			$isParent = false;
			$marketplaceProductSku = $product;
			$product = $marketplaceProductSku->marketplaceAccountHasProduct;
		} else throw new \Exception('olè');

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->startElement('Home');
		$writer->writeElement('Parentage',$isParent ? 'parent' : 'child');
		$writer->startElement('VariationData');
		$writer->writeElement('VariationTheme','Size');
		$writer->endElement();
		$writer->endElement();
		return $writer->outputMemory();
	}
}