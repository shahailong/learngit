<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Omophoto{

	/**
	 * 21：おもフォト一覧(人気順) GET /omophoto/ranking
	 */
	public static function get_ranking(){
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

		$validation->add('target_at')
			->add_rule('numeric_min', 1)
			->add_rule('valid_date')
		;

		$validation->add('type')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 1)
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
	 * 22：指定ユーザーが投稿したおもフォト一覧(人気順) GET /omophoto/ranking_user
	 */
	public static function get_ranking_user(){
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

		$validation->add('target_at')
			->add_rule('numeric_min', 1)
			->add_rule('valid_date')
		;

		$validation->add('user_id')
			->add_rule('required')
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
	 * 23：おもフォト一覧(新着順) GET /omophoto/list
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
	 * 24：指定ユーザーが投稿したおもフォト一覧(新着順) GET /omophoto/list_user
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

	/**
	 * 25：指定ユーザーが評価したおもフォト一覧(新着順) GET /omophoto/list_user_appraised
	 */
	public static function get_list_user_appraised(){
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

		$validation->add('user_id')
		->add_rule('required')
		->add_rule('valid_string',array('numeric'))
		->add_rule('numeric_min', 1)
		->add_rule('max_length', 20)
		;

		return $validation;
	}

	 /**
	 * 26：指定おもフォトへの評価者一覧(新着順) GET /omophoto/appraisers
	 */
	public static function get_appraisers(){
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

		$validation->add('omophoto_id')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 20)
		;

		return $validation;
	}

	 /**
	 * 27：指定フォトのおもフォト一覧(新着順) GET /omophoto/list_photo
	 */
	public static function get_list_photo(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('photo_id')
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

	/**
	 * 28：芸人が投稿したおもフォト一覧(新着順) GET /omophoto/list_officialuser
	 */
	public static function get_list_officialuser(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('official_flg')
		->add_rule('required')
		->add_rule('valid_string',array('numeric'))
		->add_rule('numeric_min', 1)
		->add_rule('max_length', 1)
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

	/**
	 * 29：芸人が投稿したおもフォト一覧(人気順) GET /omophoto/ranking_officialuser
	 */
	public static function get_ranking_officialuser(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('official_flg')
		->add_rule('required')
		->add_rule('valid_string',array('numeric'))
		->add_rule('numeric_min', 1)
		->add_rule('max_length', 1)
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

		$validation->add('target_at')
			->add_rule('numeric_min', 1)
			->add_rule('valid_date')
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