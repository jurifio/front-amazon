<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CMarketplaceAccount;
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
class CAmazonRelationshipFeedBuilder extends AAmazonFeedBuilder
{
	protected $feedTypeName = '_POST_PRODUCT_RELATIONSHIP_DATA_';
	/**
	 *  @param CObjectCollection $prestashopHasProductHasMarketplaceHasShops
     * @param CMarketplaceAccount $marketplaceAccount
     * @param bool $indent
     * @return $this
     */
    public function prepare(CMarketplaceAccount $marketplaceAccount, CObjectCollection $prestashopHasProductHasMarketplaceHasShops, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('MessageType','Relationship');
        $writer->writeElement('PurgeAndReplace','true');
        $i = 0;
		$i = 0;
        foreach ($prestashopHasProductHasMarketplaceHasShops as $prestashopHasProductHasMarketplaceHasShop)
		{
			$i++;
			$writer->startElement('Message');
			$writer->writeElement('MessageID',$i);
			$writer->writeElement('OperationType','Update');
			$writer->startElement('Relationship');
			$writer->writeElement('ParentSKU',$prestashopHasProductHasMarketplaceHasShop->productId.'-'.$prestashopHasProductHasMarketplaceHasShop->productVariantId);
            $marketplaceAccountHasProductSkus=\Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->findBy(['productId'=>$prestashopHasProductHasMarketplaceHasShop->productId,'productVariantId'=>$prestashopHasProductHasMarketplaceHasShop->productVariantId,'marketplaceId'=>$marketplaceAccount->marketplaceId,'marketplaceAccountId'=>$marketplaceAccount->id]);
            foreach($marketplaceAccountHasProductSkus as $marketplaceAccountHasProductSku) {
				$writer->startElement('Relation');
				$writer->writeElement('SKU',$marketplaceAccountHasProductSku->productId.'-'.$marketplaceAccountHasProductSku->productVariantId.'-'.$marketplaceAccountHasProductSku->productSizeId);
				$writer->writeElement('Type','Variation');
				$writer->endElement();
			}
			$writer->endElement();
			$writer->endElement();
		}
		$this->rawBody = $writer->outputMemory();
		return $this;
	}

	/**
	 * @param CProduct $product
	 * @param $indent
	 * @return string
	 */
}