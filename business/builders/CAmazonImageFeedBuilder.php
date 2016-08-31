<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CProduct;
use bamboo\domain\entities\CProductPhoto;

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
class CAmazonImageFeedBuilder extends AAmazonFeedBuilder
{
	protected $feedTypeName = '_POST_PRODUCT_IMAGE_DATA_';

	public function prepare(CObjectCollection $marketPlaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('MessageType','ProductImage');
		$amazon = $this->app->cfg()->fetch("general","product-photo-host");
		$i = 0;
		foreach ($marketPlaceAccountHasProducts as $marketPlaceAccountHasProduct)
		{
			$path = $amazon.$marketPlaceAccountHasProduct->product->productBrand->slug.'/';
			foreach ($marketPlaceAccountHasProduct->product->productPhoto as $productPhoto) {
				/** @var $productPhoto CProductPhoto */
				if(!$productPhoto->isBig()) continue;
				$i++;
				$writer->startElement('Message');
				$writer->writeElement('MessageID',$i);
				$writer->writeElement('OperationType','Update');
				$writer->startElement('ProductImage');
				$writer->writeElement('SKU',$marketPlaceAccountHasProduct->product->printId());
				switch ($productPhoto->order) {
					case '1':
						$type = "Main";
						break;
					default: $type = "PT".$productPhoto->order;
				}
				$writer->writeElement('ImageType',$type);
				$writer->writeElement('ImageLocation',$path.$productPhoto->name);

				$writer->endElement();
				$writer->endElement();

				/*foreach ($marketPlaceAccountHasProduct->marketplaceAccountHasProductSku as $mahps) {
					$i++;
					$writer->startElement('Message');
					$writer->writeElement('MessageID',$i);
					$writer->writeElement('OperationType','Update');
					$writer->startElement('ProductImage');
					$writer->writeElement('SKU',$mahps->productSku->getFirst()->printPublicSku());
					$writer->writeElement('ImageType',$type);
					$writer->writeElement('ImageLocation',$path.$productPhoto->name);
					$writer->endElement();
					$writer->endElement();
				}*/
			}
		}
		$this->rawBody = $writer->outputMemory();
		return $this;
	}
}