<?php

class ControllerExtensionPaymentClickpaySadad extends Controller
{
	public $_code = 'sadad';
	public $error = array();
	public $userToken;

	private $clickpayController;
	private $clickpayCatallogController;


	//

	function init()
	{
		$this->load->library('clickpay_api');

		$this->clickpayController = new ClickpayController($this);
		$this->clickpayCatallogController = new ClickpayCatalogController($this);

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

	public function refund()
	{
		$this->init();
		$this->clickpayCatallogController->process_refund();
	}
}
