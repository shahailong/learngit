<?php
/**
 * プロフィール情報
 * @author a-kido
 */
class Model_User_Profileimage extends Model_Base{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
		'id',
		'user_id',
		'image_file_name',
		'image_file_size',
		'image_width',
		'image_height',
		'censorship_flg',
		'censorship_start_dt',
		'censorship_end_dt',
		'censorship_result',
		'delete_flg',
		'delete_at',
		'image_delete_status',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'user_profileimages';

	const CENSORSHIP_FLG_PENDING = 0; // 検閲待ち
	const CENSORSHIP_FLG_PROCESSING  = 1; // 検閲中
	const CENSORSHIP_FLG_COMPLETE = 2; // 検閲完了

	const IMAGE_DELETE_STATUS_DEFAULT = 0;
	const IMAGE_DELETE_STATUS_WAIT = 1; // 画像削除待ち
	const IMAGE_DELETE_STATUS_COMPLETE = 2; // 画像削除完了
	const IMAGE_DELETE_STATUS_FAIL = 3; // 画像削除失敗

	/**
	 *
	 * @param int $user_id
	 * @return
	 */
	public static function get_image_file_name($user_id){
		$models = static::query()->where('user_id', $user_id)->get();
		$model = array_shift($models);
		if(!$model){
			return null;
		}

		return $model['image_file_name'];
	}

	/**
	* プロフィール画像のロック
	*
	* @param $user_id
	* @return type
	*/
	public static function lock_user_data($user_id = NULL){
		$sql = "SELECT * FROM user_profileimages WHERE user_id = :user_id AND delete_flg = :delete_flg FOR UPDATE";
		$result = DB::query($sql)
					->parameters(array('user_id'=> $user_id, 'delete_flg'=> static::DELETE_FLG_FALSE))
					->execute();

		return $result[0];
	}

	 /**
	 * 新規レコードを返却します。
	 *
	 * @param int $user_id
	 * @param int $image_file_name
	 * @param int $image_file_size
	 * @param int $image_width
	 * @param int $image_height
	 *
	 * @return Model_Photo
	 */
	public static function new_record($user_id,$image_file_name,$image_file_size,$image_width,$image_height){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->image_file_name = $image_file_name;
		$record->image_file_size = $image_file_size;
		$record->image_width = $image_width ? $image_width : 0;
	   	$record->image_height = $image_height ? $image_height : 0;
		$record->censorship_flg = static::CENSORSHIP_FLG_PENDING;
		$record->delete_flg = static::DELETE_FLG_FALSE;
		$record->image_delete_status = static::IMAGE_DELETE_STATUS_DEFAULT;
		return $record;
	}


}