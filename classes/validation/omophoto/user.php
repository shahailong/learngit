<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Omophoto_User {

	/**
	 * 31：おもフォト登録 POST /omophoto/user/upload
	 */
	 public static function post_upload(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('photo_id')
			->add_rule('valid_string', array('numeric'))
			->add_rule('required')
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 20)
		;
		$validation->add('title')
			->add_rule('min_length', 1)
			->add_rule('max_length', 100)
			->add_rule('valid_rule3')
		;
		return $validation;
	}

	 /**
	 * 32：おもフォトへのいいね POST /omophoto/user/appraised
	 */
	 public static function post_appraised(){
		$validation = Validation::forge();

		$validation->add('omophoto_id')
			->add_rule('valid_string', array('numeric'))
			->add_rule('required')
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 20)
		;
		$validation->add('points')
			->add_rule('required')
			->add_rule('numeric_min', 1)
			->add_rule('numeric_max', 5)
			->add_rule('max_length', 1)
		;
		return $validation;
	}

}