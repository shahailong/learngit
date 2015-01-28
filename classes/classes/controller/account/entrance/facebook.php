<?php
/**
 * 未ログイン状態におけるFacebookアカウント認証を扱うController
 *
 * @author k-kawaguchi
 */
class Controller_Account_Entrance_Facebook extends Controller_Rest_Base{

	/**
	 * 06：Facebookアカウント・ログイン POST /account/entrance/facebook/login
	 *
	 */
	public function post_login(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Facebook($this, new Auth_Driver_Base_Account_Facebook($this->get_auth_driver()));
		$this->response($auth_driver = $domain->login(
			Validation_Base::get_valid('facebook_id'),
			Validation_Base::get_valid('access_token'),
			Validation_Base::get_valid('expires_in'),
			Validation_Base::get_valid('device_info'),
			Validation_Base::get_valid('os')
		));
	}

	/**
	 * 05：Facebookアカウント登録 POST /account/entrance/facebook/register
	 */
	public function post_register(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Facebook($this, new Auth_Driver_Base_Account_Facebook($this->get_auth_driver()));
		$this->response($domain->register_account(
			Validation_Base::get_valid('facebook_id'),
			Validation_Base::get_valid('access_token'),
			Validation_Base::get_valid('expires_in'),
			Validation_Base::get_valid('nickname'),
			Validation_Base::get_valid('device_info'),
			Validation_Base::get_valid('os')
		));
	}
}