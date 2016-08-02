<?php

namespace bamboo\amazon\business;

use bamboo\amazon\business\builders\CAmazonProductFeedBuilder;
use bamboo\core\application\AApplication;
use bamboo\domain\entities\CProduct;
use bamboo\domain\entities\CProductPhoto;

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
class CAmazonImageFeedBuilder
{
	/**
	 * @var AApplication
	 */
	protected $app;

	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}

	public function sendProducts()
	{
		$productSet = [];

		$sql = "SELECT 	productId, 
						productVariantId, 
						marketplaceId,
						marketplaceAccountId 
				FROM 	MarketplaceAccountHasProduct mahp, 
						Marketplace m 
				WHERE 	m.id = mahp.marketplaceId 
					AND m.name = 'Amazon'";
		$res = $this->app->repoFactory->create('MarketplaceAccountHasProduct')->em()->findBySql($sql, []);

		$product = new CAmazonProductFeedBuilder($this->app);
		$product->prepare($res);
		$product->call();
	}
}