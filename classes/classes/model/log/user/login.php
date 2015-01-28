<?php
/**
 * ログイン履歴
 * @author a-kido
 */
class Model_Log_User_Login extends Model_Base{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
			'id',
			'user_id',
			'device_info',
			'os',
			'delete_flg',
			'delete_at',
			'created_at',
			'updated_at',
	);
	protected static $_table_name = 'log_user_logins';

	/**
	 *
	 * @param int $user_id
	 * @param string $device_info
	 * @param string $os
	 * @return Model_User_Login
	 */
	public static function new_record($user_id, $device_info, $os){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->device_info = $device_info;
		$record->os = $os;
		$record->delete_flg = static::DELETE_FLG_FALSE;
		return $record;
	}

}