<?php
/**
 * コントローラーに対応するバリデーション定義
 */
class Validation_Photo_User {

	/**
	 * 51：フォト登録 POST /photo/user/upload
	 */
	 public static function post_upload(){
		$validation = Validation::forge();
		return $validation;
	}

}