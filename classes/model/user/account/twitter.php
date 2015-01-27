<?php
/**
 * Twitterアカウント
 * @author k-kawaguchi
 */
class Model_User_Account_Twitter extends Model_Base
{
	protected static $_properties = array(
		'id',
		'user_id',
		'twitter_id',
		'oauth_token',
		'oauth_verifier',
		'oauth_token_secret',
		'created_at',
		'updated_at',
	);
	protected static $_table_name = 'user_account_twitters';

	/**
	 *
	 * @param mixed $twitter_id
	 * @return null
	 */
	public static function get_by_twitter_id($twitter_id){
		$models = static::query()->where('twitter_id', $twitter_id)->get();
		$model = array_shift($models);
		if(!$model){
			return null;
		}

		return $model;
	}

	/**
	 * ユーザIDで１行取得します。
	 *
	 * @param int $user_id
	 * @return Model_User_Account_Twitter
	 */
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
	 * @param type $twitter_id
	 * @param type $oauth_token
	 * @param type $oauth_verifier
	 * @param type $oauth_token_secret
	 * @return type
	 */
	public static function new_record($user_id,$twitter_id, $oauth_token, $oauth_verifier, $oauth_token_secret){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->twitter_id = $twitter_id;
		$record->oauth_token = $oauth_token;
		$record->oauth_verifier = $oauth_verifier;
		$record->oauth_token_secret = $oauth_token_secret;
		return $record;
	}

}
