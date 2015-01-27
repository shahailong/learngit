<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Photo{

	 /**
	 * 41：フォト一覧(新着順) GET /photo/list
	 */
	public static function get_list(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('need_num')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 10)
		;

		$validation->add('max_id')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 20)
		;

		$validation->add('no_cache')
		->add_rule('valid_string',array('numeric'))
		->add_rule('numeric_min', 0)
		->add_rule('numeric_max', 1)
		->add_rule('max_length', 1)
		;

		return $validation;
	}

	 /**
	 * 42：指定ユーザーが投稿したフォト一覧(新着順) GET /photo/list_user
	 */
	public static function get_list_user(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('user_id')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 20)
		;

		$validation->add('need_num')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 10)
		;

		$validation->add('max_id')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 20)
		;

		$validation->add('no_cache')
		->add_rule('valid_string',array('numeric'))
		->add_rule('numeric_min', 0)
		->add_rule('numeric_max', 1)
		->add_rule('max_length', 1)
		;

		return $validation;
	}

}