<?php

namespace bamboo\amazon\business;

use bamboo\core\application\AApplication;

/**
 * Class CProductFeedBuilder
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
class CProductFeedBuilder {

	/**
	 * @var AApplication
	 */
	protected $app;

	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}


}