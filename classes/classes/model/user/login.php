<?php
/**
 * ログイン情報
 * @author k-kawaguchi
 */
class Model_User_Login extends Model_Base{

	protected static $_primary_key = array('user_id');
	protected static $_properties = array(
		'user_id',
		'token',
		'last_login_at',
		'last_logout_at',
		'created_at',
		'updated_at',
	);
	protected static $_table_name = 'user_logins';

	/**
	 *
	 * @param int $user_id
	 * @return Model_User_Login
	 */
	public static function get_by_user_id($user_id){
		$model_users = static::query()->where('user_id', $user_id)->get();
		if(!isset($model_users[$user_id])){
			return null;
		}

		return $model_users[$user_id];
	}

	/**
	 *
	 * @param int $user_id
	 * @param string $token
	 * @param string $last_login_at
	 * @return Model_User_Login
	 */
	public static function new_record($user_id, $token, $last_login_at){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->token = $token;
		$record->last_login_at = $last_login_at;
		return $record;
	}

}