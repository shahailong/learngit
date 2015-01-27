<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Account_Entrance_Twitter{

	/**
	 * 04：Twitterアカウント・ログイン POST /account/entrance/twitter/login
	 */
	public static function post_login(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('twitter_id')
				->add_rule('required')
				->add_rule('valid_string',array('numeric'))
				->add_rule('max_length', 20)
		;
		$validation->add('oauth_token')
				->add_rule('required')
		;
		$validation->add('oauth_token_secret')
				->add_rule('required')
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
	 * 03：Twitterアカウント登録 POST /account/entrance/twitter/register
	 */
	public static function post_register(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('twitter_id')
				->add_rule('required')
				->add_rule('valid_string',array('numeric'))
				->add_rule('max_length', 20)
		;
		$validation->add('oauth_token')
				->add_rule('required')
		;
		$validation->add('oauth_token_secret')
				->add_rule('required')
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