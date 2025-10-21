<?php

namespace Opencart\Catalog\Model\Extension\Paytabs\Payment;

require_once DIR_EXTENSION . 'paytabs/system/library/paytabs_api.php';

use Opencart\System\Library\PaytabsCatalogModel;

class PaytabsTamara extends PaytabsCatalogModel
{
	public $_code = 'tamara';

	protected function _getTitle()
	{
		$mainTitle = parent::_getTitle();
		$title = '&nbsp;<p style="color: grey">Monthly payments, No late fees. <a href="https://tamara.co" target="_blank" className="see-more-link">More options</a></p>';

		return $mainTitle . $title;
	}
}
