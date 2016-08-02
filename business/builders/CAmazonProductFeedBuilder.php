<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CProduct;

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
class CAmazonProductFeedBuilder
{
	/**
	 * @param CObjectCollection $marketPlaceAccountHasProducts
	 * @param bool $indent
	 * @return string
	 */
	public function prepare(CObjectCollection $marketPlaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$i = 0;
		foreach ($marketPlaceAccountHasProducts as $marketPlaceAccountHasProduct)
		{
			$i++;
			$writer->startElement('Message');
			$writer->writeElement('MessageID',$i);
			$writer->writeElement('OperationType','Update');
			$writer->startElement('Product');
			$writer->writeRaw($this->writeProduct($marketPlaceAccountHasProduct->product,$indent));
			$writer->endElement();
			$writer->endElement();
		}
		$this->rawBody =  $writer->outputMemory();
	}

	/**
	 * @param CProduct $product
	 * @param $indent
	 * @return string
	 */
	protected function writeProduct(CProduct $product, $indent = false) {
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$product->printId());
		$writer->startElement('StandardProductID');
		$writer->writeElement('Type','EAN');
		$writer->writeElement('Value','abracadabra');
		$writer->endElement();
		$writer->writeElement('ProductTaxCode','A_GEN_TAX');
		$writer->writeElement('LaunchDate',(new \DateTime())->format(DATE_ATOM));
		$writer->writeElement('ReleaseDate',(new \DateTime())->format(DATE_ATOM));
		$writer->startElement('Condition');
		$writer->writeElement('ConditionType','New');
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
		$writer->startElement('Home');
		$writer->writeElement('Parentage','variation-parent');
		$writer->startElement('VariationData');
		$writer->writeElement('VariationTheme','Size');
		$writer->endElement();
		$writer->endElement();

		$this->rawBody = $writer->outputMemory();
		return $this;
	}
}