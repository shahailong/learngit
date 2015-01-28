<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Account_Entrance_Facebook extends Controller_Rest_Base{

	/**
	 * 06：Facebookアカウント・ログイン POST /account/entrance/facebook/login
	 *
	 */
	public static function post_login(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('facebook_id')
				->add_rule('required')
				->add_rule('valid_string',array('numeric'))
				->add_rule('max_length', 20)
		;
		$validation->add('access_token')
				->add_rule('required')
				->add_rule('max_length', 255)
		;
		$validation->add('expires_in')
				->add_rule('max_length', 20)
				->add_rule('valid_string',array('numeric'))
		;
		$validation->add('device_info')
				->add_rule('required')
				->add_rule('min_length', 1)
				->add_rule('max_length', 1000)
		;
		$validation->add('os')
				->add_rule('required')
				->add_rule('min_length', 1)
				->add_rule('max_length', 1000)
		;
		return $validation;
	}

	/**
	 * 05：Facebookアカウント登録 POST /account/entrance/facebook/register
	 */
	public static function post_register(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('facebook_id')
				->add_rule('required')
				->add_rule('valid_string',array('numeric'))
				->add_rule('max_length', 20)
		;
		$validation->add('access_token')
				->add_rule('required')
				->add_rule('max_length', 255)
		;
		$validation->add('expires_in')
				->add_rule('max_length', 20)
				->add_rule('valid_string',array('numeric'))
		;
		$validation->add('nickname')
				->add_rule('required')
				->add_rule('min_length', 4)
				->add_rule('max_length', 20)
				->add_rule('valid_rule2')
		;
		$validation->add('device_info')
				->add_rule('required')
				->add_rule('min_length', 1)
				->add_rule('max_length', 1000)
		;
		$validation->add('os')
			->add_rule('required')
			->add_rule('min_length', 1)
			->add_rule('max_length', 1000)
		;
		return $validation;
	}
}