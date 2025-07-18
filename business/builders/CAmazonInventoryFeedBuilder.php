<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CMarketplaceAccount;
use bamboo\domain\entities\CMarketplaceAccountHasProductSku;
use bamboo\domain\entities\CProduct;
use bamboo\domain\entities\CProductSku;

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
class CAmazonInventoryFeedBuilder extends AAmazonFeedBuilder
{
	protected $feedTypeName = '_POST_INVENTORY_AVAILABILITY_DATA_';

	public function prepare(CMarketplaceAccount $marketplaceAccount, CObjectCollection $prestashopHasProductHasMarketplaceHasShops, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('MessageType','Inventory');
		$i = 0;
        foreach ($prestashopHasProductHasMarketplaceHasShops as $prestashopHasProductHasMarketplaceHasShop)
		{
            $marketplaceAccountHasProductSkus=\Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findBy(['productId'=>$prestashopHasProductHasMarketplaceHasShop->productId,'productVariantId'=>$prestashopHasProductHasMarketplaceHasShop->productVariantId,'marketplaceId'=>$marketplaceAccount->marketplaceId,'marketplaceAccountId'=>$marketplaceAccount->id]);
			foreach ($marketplaceAccountHasProductSkus as $sku) {
				$i++;
				$writer->startElement('Message');
				$writer->writeElement('MessageID',$i);
				$writer->writeElement('OperationType','Update');
				$writer->startElement('Inventory');
				$writer->writeRaw($this->writeProductSku($sku,$indent));
				$writer->endElement();
				$writer->endElement();
			}
		}
		$this->rawBody = $writer->outputMemory();
		return $this;
	}

	/**
	 * @param CMarketplaceAccountHasProductSku $sku
	 * @param bool $indent
	 * @return string
	 */
	protected function writeProductSku(CMarketplaceAccountHasProductSku $sku, $indent = false) {
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$sku->productId.'-'.$sku->productVariantId.'-'.$sku->productSizeId);
		$writer->writeElement('FulfillmentCenterID','DEFAULT');
		$qty = 0;
		foreach ($sku->productSku as $productSku) {
			$qty+= $productSku->stockQty;
		}
		$writer->writeElement('Quantity',$qty);
		$writer->endElement();

		return $writer->outputMemory();
	}
}