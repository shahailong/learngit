<?php
/**
 * ランク履歴
 * @author m.yoshitake
 */
class Model_Log_User_Rank extends Model_Base{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
			'id',
			'user_id',
			'rank_id_before',
			'rank_id_after',
			'delete_flg',
			'delete_at',
			'created_at',
			'updated_at',
	);
	protected static $_table_name = 'log_user_ranks';

	/**
	 *
	 * @param int $user_id
	 * @param int $rank_id_before
	 * @param int $rank_id_after
	 * @return Model_User_Login
	 */
	public static function new_record($user_id, $rank_id_before, $rank_id_after){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->rank_id_before = $rank_id_before;
		$record->rank_id_after = $rank_id_after;
		$record->delete_flg = static::DELETE_FLG_FALSE;
		return $record;
	}

}