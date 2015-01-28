<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Specialcollection{

	/**
	 * 特集フォト一覧取得(新着順) GET /specialCollection/photo
	 */
	public static function get_photo(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('need_num')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 10)
		;
		$validation->add('special_collection_id')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 10)
		;
		
		$validation->add('max_id')
			->add_rule('valid_string',array('numeric'))
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
	 * 特集おもフォト一覧取得 GET /specialCollection/omophoto
	 */
	public static function get_omophoto(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('need_num')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 10)
		;
		$validation->add('special_collection_id')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('numeric_min', 1)
			->add_rule('max_length', 10)
		;
		
		$validation->add('max_id')
			->add_rule('valid_string',array('numeric'))
			->add_rule('max_length', 20)
		;
		
		$validation->add('order_type')
			->add_rule('required')		
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
	 * 過去特集一覧取得 GET /specialCollection/old
	 */
	public static function get_old(){
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