<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Account extends Controller_Rest_Base_Authorized{

	/**
	 * 07：ログアウト POST /account/logout
	 */
	public static function post_logout(){
		return null;
	}

	/**
	 * 75：退会 POST /account/retire
	 */
	public static function post_retire(){
		return null;
	}

	/**
	 * 73：パスワード変更 POST /account/password
	 */
	public static function post_password(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('old')
			->add_rule('required')
			->add_rule('min_length', 8)
			->add_rule('max_length', 20)
			->add_rule('valid_rule1')
		;
		$validation->add('new')
			->add_rule('required')
			->add_rule('min_length', 8)
			->add_rule('max_length', 20)
			->add_rule('valid_rule1')
		;
		$validation->add('confirm')
			->add_rule('required')
			->add_rule('min_length', 8)
			->add_rule('max_length', 20)
			->add_rule('valid_rule1')
		;
		return $validation;
	}

	/**
	 * 74：PUSH通知設定変更 POST /account/push
	 */
	public static function post_push(){
		$validation = Validation::forge();
		$validation->add('push_flg')
			->add_rule('required')
			->add_rule('max_length', 1)
			->add_rule('match_value', array(0, 1))
			->add_rule('valid_string',array('numeric'))
				;

		return $validation;
	}

	/**
	 * 72：プロフィール画像登録・変更・削除 POST /account/profileimage
	 */
	public static function post_profileimage(){
		$validation = Validation::forge();
		$validation->add('imagefile_delete_flg')
			->add_rule('required')
			->add_rule('max_length', 1)
			->add_rule('match_value', array(0, 1))
			->add_rule('valid_string',array('numeric'))
			;

		return $validation;
	}


}