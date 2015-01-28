<?php
/**
 * 未ログイン状態におけるTwitterアカウント認証を扱うController
 *
 * @author k-kawaguchi
 */
class Controller_Account_Entrance_Twitter extends Controller_Rest_Base{

	/**
	 * 04：Twitterアカウント・ログイン POST /account/entrance/twitter/login
	 */
	public function post_login(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Twitter(
			$this,
			new Auth_Driver_Base_Account_Twitter($this->get_auth_driver())
		);
		$this->response($domain->login(
			Validation_Base::get_valid('twitter_id'),
			Validation_Base::get_valid('oauth_token'),
			Validation_Base::get_valid('oauth_token_secret'),
			Validation_Base::get_valid('device_info'),
			Validation_Base::get_valid('os')
		));
	}

	/**
	 * 03：Twitterアカウント登録 POST /account/entrance/twitter/register
	 */
	public function post_register(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Twitter(
			$this,
			new Auth_Driver_Base_Account_Twitter($this->get_auth_driver())
		);
		$this->response($domain->register_account(
			Validation_Base::get_valid('twitter_id'),
			Validation_Base::get_valid('oauth_token'),
			Validation_Base::get_valid('oauth_token_secret'),
			Validation_Base::get_valid('nickname'),
			Validation_Base::get_valid('device_info'),
			Validation_Base::get_valid('os')
		));
		//$this->response(array('credential'=>$auth_driver->get_login_credential()));
	}
}