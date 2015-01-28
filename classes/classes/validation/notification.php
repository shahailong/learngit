<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Notification{

	/**
	 * 61：お知らせ一覧 GET /notification/list
	 */
	public static function get_list(){
		$validation = Validation::forge();
		$validation->add('need_num')
			->add_rule('required')
			->add_rule('valid_string',array('numeric'))
			->add_rule('max_length', 10)
			->add_rule('numeric_min', 1)
		;
		$validation->add('max_id')
			->add_rule('valid_string', array('numeric'))
			->add_rule('max_length', 20)
			->add_rule('numeric_min', 1)
		;
		return $validation;
	}

	/**
	 * 62：お知らせの既読変更 POST /notification/read
	 */
	public static function post_read(){
		$validation = Validation::forge();
		$validation->add_callable('customvalidation');

		$validation->add('notification_ids')
			->add_rule('valid_rule4')
		;
		$validation->add('all_read_flg')
			->add_rule('required')
			->add_rule('max_length', 1)
			->add_rule('match_value', array(0, 1))
			->add_rule('valid_string',array('numeric'))
		;
		return $validation;
	}

	/**
	 * 63：お知らせ未読有無 GET /notification/check_unread
	 */
	public static function get_check_unread(){
		return null;
	}
}