<?php
/**
 * お知らせ
 * @author k-kawaguchi
 */
class Model_User_Notification extends Model_Base
{
	const READ_FLG_TRUE  = 1;
	const READ_FLG_FALSE = 0;
	const REMOVE_FLG_TRUE  = 1;
	const REMOVE_FLG_FALSE = 0;

	const PUSH_SEND_FLG_FALSE = 0;  //0:PUSH送信待ち
	const PUSH_SEND_FLG_TRUE = 1;   //1:PUSH送信済
	const PUSH_SEND_FLG_DELETE = 2; //2:PUSH未送信完了(対象データ削除)
	const PUSH_SEND_FLG_OFF = 3;	//3:PUSH未送信完了(PUSH通知OFF)'

	protected static $_properties = array(
		'id',
		'user_id',
		'type',
		'from_user_id',
		'target_photo_id',
		'target_omophoto_id',
		'push_send_flg',
		'read_flg',
		'read_at',
		'delete_flg',
		'delete_at',
		'created_at',
		'updated_at'
	);

	protected static $_max_id = NULL;

	protected static $message_type = array(
		1 => 'あなたが投稿したフォトでおもフォトが作られました！',
		2 => 'あなたが投稿したおもフォトが評価されました！',
		3 => 'ランクが昇格しました！'
	);

	protected static $_table_name = 'user_notifications';
	public static $response_root_element = 'notifications';
	private static $photo_stock_ary = array();

	public function format_response(array $data = array()){

		switch ($this->type){
			case 1:
				 $photo_obj = Model_Photo::get_format_photo_data($this->target_photo_id);
				 $omophoto_obj = Model_Omophoto::get_format_omophoto_data($this->target_omophoto_id);
				 break;
			 case 2:
				 $photo_obj = NULL;
				 $omophoto_obj = Model_Omophoto::get_format_omophoto_data($this->target_omophoto_id);
				 break;
			 default :
				 $photo_obj = NULL;
				 $omophoto_obj = NULL;
		}

		return parent::format_response(array(
			'id'=> $this->id,
			'message'=> static::$message_type[$this->type],
			'type'=> $this->type,
			'photo'=> $photo_obj,
			'omophoto'=> $omophoto_obj,
			'read_flg'=> $this->read_flg,
			'created_at'=> $this->created_at
			)
		);
	}

	 /**
	 * 新規のレコードを返却します。
	 *
	 * @param int $user_id
	 * @param int $type
	 * @param int $from_user_id
	 * @param int $target_photo_id
	 * @param int $target_omophoto_id
	 *
	 * @return Model_User_Notification
	 */
	public static function new_record($user_id, $type, $from_user_id,$target_photo_id,$target_omophoto_id){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->type = $type;
		$record->from_user_id = $from_user_id;
		$record->target_photo_id = $target_photo_id;
		$record->target_omophoto_id = $target_omophoto_id;

		$record->push_send_flg = static::PUSH_SEND_FLG_FALSE;
		$record->read_flg = static::READ_FLG_FALSE;
		$record->delete_flg = static::DELETE_FLG_FALSE;

		return $record;
	}

	/**
	 * レスポンスデータをフィルタリングします。
	 *
	 * @param int $user_id
	 * @param array $notification_models
	 * @return array
	 */
	public static function filter_response_records($user_id, array $notification_models){
		if(!$notification_models){
			return $notification_models;
		}

		$from_user_ids  = static::extract_array_values_by_column_name($notification_models, 'from_user_id');
		$photo_ids = static::extract_array_values_by_column_name($notification_models, 'photo_id');
		Model_User::add_all_stock_by_id(Model_User::filter_response_records($user_id,
			Model_User::get_by_ids($from_user_ids)
		));
		Model_User_Photo::add_all_stock_by_id(Model_User_Photo::filter_response_records($user_id,
			Model_User_Photo::get_by_ids($photo_ids),
			null,
			array()
		));
		return $notification_models;
	}

	public static function base_query(){
		return DB::select(static::table() . '.*')->from(static::table())
			// 削除フラグ
			->where(array(static::table() . '.delete_flg'=>static::DELETE_FLG_FALSE))
			// Removeフラグ
			->where(array(static::table() . '.remove_flg'=>static::READ_FLG_FALSE))
		;
	}

	public static function query($options = array()){
		$query = parent::query($options);
		$query->where('remove_flg', static::REMOVE_FLG_FALSE);
		return $query;
	}

	 /**
	 * 取得件数を指定してお知らせ一覧を取得します。
	 *
	 * @param int $need_num
	 * @param int max_id
	 * @return array
	 */
	public static function list_data($need_num,$executor_user_id=NULL, $max_id = NULL){
		// カレントページのデータ取得
		$query = DB::select('un.*');
		$query->from(array(static::table(),'un'));
		$query->join(array(Model_User::table(),'u'), 'LEFT');
		$query->on('u.id', '=','un.from_user_id');
		$query->join(array(Model_Photo::table(),'p'), 'LEFT');
		$query->on('p.id', '=','un.target_photo_id');
		$query->join(array(Model_Omophoto::table(),'o'), 'LEFT');
		$query->on('o.id', '=','un.target_omophoto_id');
		$query->where('un.user_id','=',$executor_user_id);
		$query->where(array('un.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_open();
		$query->where(array('u.delete_flg'=>NULL));
		$query->or_where(array('u.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_close();
		$query->where_open();
		$query->where(array('p.delete_flg'=>NULL));
		$query->or_where(array('p.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_close();
		$query->where_open();
		$query->where(array('o.delete_flg'=>NULL));
		$query->or_where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_close();
		$query->limit($need_num);
		$query->order_by('un.id','desc');
		$query->as_object('Model_User_Notification');
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('un.id', '<', $max_id);
		}
		$results = $query->execute();

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			$result->excutor_user_id = $executor_user_id;
			$format_data = $result->format_response();
			self::$_max_id = $format_data['id'];
			$respons[] = $format_data;
		}

		return  $respons;
	}

	/**
	 * お知らせ一覧付加データを付けて返すメソッド
	 *
	 * @param array $data
	 * @return array
	 */
	public static function notification_lists($need_num, $max_id = NULL,$executor_user_id=NULL){
		$respons['notifications'] = self::list_data($need_num,$executor_user_id,$max_id);
		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['notifications']);
		$respons['unread_count'] = self::get_count_unread($executor_user_id);

		return  $respons;
	}

	private static function build_notification_read_base(){
		return DB::update(static::table())
			->set(array(
				'read_at'=>DB::expr('NOW()'),
				'updated_at'=>DB::expr('NOW()'),
				'read_flg'=>static::READ_FLG_TRUE
			))
			->where('delete_flg', '=', static::DELETE_FLG_FALSE)
		;
	}

	/**
	 * お知らせ一覧付加データを付けて返すメソッド
	 *
	 * @param string $notification_ids
	 * @param int $all_read_flg
	 * @param int $executor_user_id
	 *
	 * @return array
	 */
	public static function notification_read($notification_ids, $all_read_flg = NULL, $executor_user_id=NULL){
		$notification_ids = preg_split("[,]",$notification_ids);

		$respons = static::build_notification_read_base();
		$respons->where('user_id', '=', $executor_user_id);
		$respons->where('read_flg', '=', static::READ_FLG_FALSE);
		if($all_read_flg == 0 && !is_null($notification_ids)){
			$respons->where('id', 'in', $notification_ids);
		}
		$respons->execute();

		return  $respons;
	}

	/**
	 * お知らせ未読件数を返すメソッド
	 *
	 * @param int $executor_user_id
	 *
	 * @return array
	 */
	public static function get_count_unread($executor_user_id=NULL){

		// カレントページのデータ取得
		$query = DB::select(DB::expr('COUNT(*) as count'));
		$query->from(array(static::table(),'un'));
		$query->join(array(Model_User::table(),'u'), 'LEFT');
		$query->on('u.id', '=','un.from_user_id');
		$query->join(array(Model_Photo::table(),'p'), 'LEFT');
		$query->on('p.id', '=','un.target_photo_id');
		$query->join(array(Model_Omophoto::table(),'o'), 'LEFT');
		$query->on('o.id', '=','un.target_omophoto_id');
		$query->where('un.user_id','=',$executor_user_id);
		$query->where(array('un.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_open();
		$query->where(array('u.delete_flg'=>NULL));
		$query->or_where(array('u.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_close();
		$query->where_open();
		$query->where(array('p.delete_flg'=>NULL));
		$query->or_where(array('p.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_close();
		$query->where_open();
		$query->where(array('o.delete_flg'=>NULL));
		$query->or_where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where_close();
		$query->where(array('un.read_flg'=>static::READ_FLG_FALSE));
		$results = $query->execute();

		$result_arr = $results->current();
		$count = (int)$result_arr['count'];

		return  $count;
	}

}