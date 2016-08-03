<?php

namespace bamboo\amazon\business;

use bamboo\amazon\business\builders\CAmazonProductFeedBuilder;
use bamboo\core\application\AApplication;
use bamboo\domain\entities\CMarketplaceAccountHasProduct;

/**
 * Class CAmazonImageFeedBuilder
 * @package bamboo\amazon\business
 *
 * @author Bambooshoot Team <emanuele@bambooshoot.agency>
 *
 * @copyright (c) Bambooshoot snc - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @date 02/08/2016
 * @since 1.0
 */
class CAmazonAddProducts
{
	/**
	 * @var AApplication
	 */
	protected $app;

	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * @return string
	 */
	public function sendProducts()
	{
		$sql = "SELECT 	productId, 
						productVariantId, 
						marketplaceId,
						marketplaceAccountId 
				FROM 	MarketplaceAccountHasProduct mahp, 
						Marketplace m 
				WHERE 	m.id = mahp.marketplaceId 
					AND m.name = 'Amazon'";
		$res = $this->app->repoFactory->create('MarketplaceAccountHasProduct')->em()->findBySql($sql, []);
		foreach ($res as $re) {
			$this->prepareSkus($re);
		}
		$product = new CAmazonProductFeedBuilder($this->app);
        return (string) $product->prepare($res)->getRawBody();
	}

	public function prepareSkus(CMarketplaceAccountHasProduct $res)
	{
		$sizesDone = [];
		foreach ($res as $marketplaceAccountHasProduct) {
			foreach ($marketplaceAccountHasProduct->product->productSku as $sku) {
				$marketSku = $this->app->repoFactory->create('MarketplaceAccountHasProductSku')->getEmptyEntity();
				if(!isset($sizesDone[$sku->productSizeId])) {
					$sizesDone[$sku->productSizeId] = $sku->stockQty;
					$marketSku->productSizeId = $sku->productSizeId;
					$marketSku->productId = $sku->productId;
					$marketSku->productVariantId = $sku->productVariantId;
					$marketSku->marketplaceId = $marketplaceAccountHasProduct->productSizeId;
					$marketSku->marketplaceAccountId = $marketplaceAccountHasProduct->marketplaceAccountId;
					$marketSku->qty = $sku->stockQty;
					$marketSku->insert();
				} else {
					$sizesDone[$sku->productSizeId] += $sku->stockQty;

					$marketSku->productSizeId = $sku->productSizeId;
					$marketSku->productId = $sku->productId;
					$marketSku->productVariantId = $sku->productVariantId;
					$marketSku->marketplaceId = $marketplaceAccountHasProduct->productSizeId;
					$marketSku->marketplaceAccountId = $marketplaceAccountHasProduct->marketplaceAccountId;
					$marketSku = $marketSku->em()->findOneBy($marketSku->getIds());
					$marketSku->qty = $sizesDone[$sku->productSizeId];

					$marketSku->update();
				}
			}
		}
	}
}