<?php
/**
 * Facebookアカウント
 * @author k-kawaguchi
 */
class Model_User_Account_Facebook extends Model_Base
{
	protected static $_primary_key = array('user_id');
	protected static $_properties = array(
		'user_id',
		'facebook_id',
		'access_token',
		'expires_in',
		'created_at',
		'updated_at',
	);
	protected static $_table_name = 'user_account_facebooks';

	/**
	 *
	 * @param mixed $facebook_id
	 * @return Model_User_Account_Facebook
	 */
	public static function get_by_facebook_id($facebook_id){
		$models = static::query()->where('facebook_id', $facebook_id)->get();
		$model = array_shift($models);
		if(!$model){
			return null;
		}

		return $model;
	}

	public static function get_by_user_id($user_id){
		$models = static::query()->where('user_id', $user_id)->get();
		$model = array_shift($models);
		if(!$model){
			return null;
		}

		return $model;
	}

	 /**
	 *
	 * @param int $user_id
	 * @param int $facebook_id
	 * @param string $access_token
	 * @param int $expires_in
	 * @param string $registered_at
	 * @return Model_User_Account_Original
	 */
	public static function new_record($user_id, $facebook_id, $access_token, $expires_in = null,$registered_at){
		$record = static::forge();
		$record->user_id  = $user_id;
		$record->facebook_id = $facebook_id;
		$record->access_token = $access_token;
		$record->expires_in = $expires_in;
		$record->registered_at = (is_null($registered_at) ? date('Y-m-d H:i:s') : $registered_at);
		$record->delete_flg = static::DELETE_FLG_FALSE;
		return $record;
	}
}