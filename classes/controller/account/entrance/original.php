<?php
/**
 * 独自アカウントのログイン及び登録
 *
 * @author k-kawaguchi
 */
class Controller_Account_Entrance_Original extends Controller_Rest_Base{

	/**
	 * 02：通常アカウント・ログイン POST /account/entrance/original/login
	 */
	public function post_login(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Original($this, new Auth_Driver_Base_Account_Original($this->get_auth_driver()));
		$this->response($domain->login(
			Validation_Base::get_valid('login_id'),
			Validation_Base::get_valid('password'),
			Validation_Base::get_valid('device_info'),
			Validation_Base::get_valid('os')
		));
	}

	/**
	 * 01：通常アカウント登録 POST /account/entrance/original/register
	 */
	public function post_register(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Original($this, new Auth_Driver_Base_Account_Original($this->get_auth_driver()));
		$this->response($domain->method_as_transaction(
			'register_account',
			array(
				Validation_Base::get_valid('login_id'),
				Validation_Base::get_valid('password'),
				Validation_Base::get_valid('nickname'),
				Validation_Base::get_valid('device_info'),
				Validation_Base::get_valid('os')
			)
		));
	}

}