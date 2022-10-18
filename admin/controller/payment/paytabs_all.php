<?php

namespace Opencart\Admin\Controller\Extension\Paytabs\Payment;

use Opencart\System\Library\PaytabsController;

class PaytabsAll extends \Opencart\System\Engine\Controller
{
	public $_code = 'all';
	public $error = array();
	public $userToken;

	private $paytabsController;

	//

	function init()
	{
		$this->load->library('paytabs_api');

		$this->paytabsController = new PaytabsController($this);
	}


	public function index()
	{
		$this->init();

		$this->paytabsController->index($data);
	}


	public function save()
	{
		$this->paytabsController->save();
	}


	/**
	 * Validate Extension's settings before saving the new values
	 */
	public function validate()
	{
		return $this->paytabsController->validate();
	}


	public function install()
	{
		$this->init();
		$this->paytabsController->install();
	}
}
