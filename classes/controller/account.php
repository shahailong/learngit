<?php
/**
 * 認証済アカウントに関するリクエストを受け付けるController
 * @author k-kawaguchi
 */
class Controller_Account extends Controller_Rest_Base_Authorized{

	/**
	 * 07：ログアウト POST /account/logout
	 */
	public function post_logout(){
		$domain = new Domain_User_Account($this, $this->get_auth_driver());
		$domain->logout();
		$this->response();
	}

	/**
	 * 75：退会 POST /account/retire
	 */
	public function post_retire(){
		$domain = new Domain_User_Account($this, $this->get_auth_driver());
		$domain->retire();
		$this->response();
	}

	/**
	 * 71：ログインユーザ情報取得 GET /account/setting
	 */
	public function get_setting(){
		$domain = new Domain_User_Account($this, $this->get_auth_driver());

		$this->response($domain->get_info());
	}

	/**
	 * 73：パスワード変更 POST /account/password
	 */
	public function post_password(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account_Original(
			$this,
			new Auth_Driver_Base_Account_Original($this->get_auth_driver())
		);
		$domain->modify_password(
			Validation_Base::get_valid('old'),
			Validation_Base::get_valid('new'),
			Validation_Base::get_valid('confirm')
		);
		$this->response();
	}

	 /**
	 * 74：PUSH通知設定変更 POST /account/push
	 */
	public function post_push(){
		Validation_Base::init(__METHOD__);
		$domain = new Domain_User_Account(
			$this,
			$this->get_auth_driver()
		);
		$this->response($domain->modify_push(
			Validation_Base::get_valid('push_flg')
		));
	}

	/**
	 * 72：プロフィール画像登録・変更・削除 POST /account/profileimage
	 *
	 * @since  phase2
	 */
	public function post_profileimage(){
		Validation_Base::init(__METHOD__);
		$imagefile = Input::file('imagefile', array());

		$domain = new Domain_User_Account(
			$this,
			$this->get_auth_driver()
		);

		$this->response(
			$domain->modify_profileimage(
				$imagefile,
				Validation_Base::get_valid('imagefile_delete_flg')
			)
		);
	}

}