<?php

class ControllerExtensionPaymentClickpayStcpay extends Controller
{
	public $_code = 'stcpay';
	public $error = array();
	public $userToken;

	private $clickpayController;

	//

	function init()
	{
		$this->load->library('clickpay_api');

		$this->clickpayController = new ClickpayController($this);
	}


	public function index()
	{
		$this->init();

		$this->clickpayController->index($data);
	}


	public function save()
	{
		$this->clickpayController->save();
	}


	/**
	 * Validate Extension's settings before saving the new values
	 */
	public function validate()
	{
		return $this->clickpayController->validate();
	}


	public function install()
	{
		$this->init();
		$this->clickpayController->install();
	}
}
