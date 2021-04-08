<?php


namespace bamboo\amazon\business\builders;
use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
use bamboo\domain\entities\CMarketplaceAccount;


/**
 * Class AAmazonFeedBuilder
 * @package bamboo\amazon\business\builders
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
abstract class AAmazonFeedBuilder
{
	/**
	 * @var AApplication
	 */
	protected $app;

	/**
	 * @var
	 */
	protected $feedTypeName;

	/**
	 * @var string
	 */
	protected $rawBody;
	/**
	 * CAmazonProductFeedBuilder constructor.
	 * @param AApplication $app
	 */
	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->rawBody;
	}

	public function getFeedTypeName() {
		return $this->feedTypeName;
	}

	public abstract function prepare(CMarketplaceAccount $marketplaceAccount,CObjectCollection $prestashopHasProductHasMarketplaceHasShops, $indent = false);
}