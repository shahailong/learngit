<?php
/**
 * 独自アカウント
 * @author k-kawaguchi
 */
class Model_User_Account_Original extends Model_Base
{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
		'id',
		'user_id',
		'login_id',
		'password',
		'registered_at',
		'delete_flg',
		'delete_at',
		'created_at',
		'updated_at',
	);

	protected static $_table_name = 'user_account_originals';

	public static function get_by_user_id($user_id){
		$models = static::query()->where('user_id', $user_id)->get();
		foreach ($models as $model) {
			return $model;
		}
		return null;
	}

	/**
	 *
	 * @param mixed $login_id
	 * @return null
	 */
	public static function get_by_login_id($login_id){
		$models = static::query()->where('login_id', $login_id)->get();
		$model = array_shift($models);
		if(!$model){
			return null;
		}

		return $model;
	}

	/**
	 *
	 * @param int $user_id
	 * @param string $login_id
	 * @param string $hashed_password
	 * @param string $registered_at
	 *
	 * @return Model_User_Account_Original
	 */
	public static function new_record($user_id, $login_id, $hashed_password, $registered_at = null){
		$record = static::forge();
		$record->user_id  = $user_id;
		$record->login_id = $login_id;
		$record->password = $hashed_password;
		$record->registered_at = (is_null($registered_at) ? date('Y-m-d H:i:s') : $registered_at);
		$record->delete_flg = static::DELETE_FLG_FALSE;
		return $record;
	}

}