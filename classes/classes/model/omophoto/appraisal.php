<?php
/**
 * おもフォトへの評価
 *
 * @author a-kido
 */
class Model_Omophoto_Appraisal extends Model_Base{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
		'id',
		'omophoto_id',
		'user_id',
		'action_user_id',
		'points',
		'delete_flg',
		'delete_at',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'omophoto_appraisals';

	protected static $_max_id = NULL;

	/**
	 * 指定ユーザーの累積評価ポイント
	 * 削除された投稿おもフォトへの評価や退会ユーザーの評価も含む
	 *
	 * @param $user_id
	 * @return type
	 */
	public static function get_total_points_by_user($user_id = NULL){
		if(!$user_id){
			return 0;
		}

		$sql = "SELECT SUM(points) as total_points FROM omophoto_appraisals WHERE user_id = :user_id GROUP BY user_id";
		$result = DB::query($sql)
					->param('user_id', $user_id)
					->execute()
					->current();

		return empty($result) ? 0 : $result['total_points'];
	}

	 /**
	 * 指定おもフォトへログインユーザーが評価したポイント
	 *
	 * @param $omophoto_id
	 *
	 * @return type
	 */
	public static function get_login_user_points_by_omophoto_id($omophoto_id=null){

		$excutor_user_id = self::get_executor_user_id();

		if(empty($omophoto_id) || empty($excutor_user_id)){
			return 0;
		}

		$sql = "SELECT SUM(points) as login_user_points FROM omophoto_appraisals WHERE omophoto_id = :omophoto_id AND action_user_id = :action_user_id GROUP BY action_user_id";
		$result = DB::query($sql)
					->parameters(array('omophoto_id'=> $omophoto_id,
									'action_user_id'=> $excutor_user_id))
					->execute()
					->as_array();


		return empty($result) ? 0 : $result['0']['login_user_points'];
	}

	 /**
	 * 指定おもフォトのログインユーザのデータ
	 *
	 * @param $omophoto_id
	 * @param $user_id
	 * @return type
	 */
	public static function get_omophoto_appraisals_data($omophoto_id,$user_id = NULL){
		$sql = "SELECT * FROM omophoto_appraisals WHERE omophoto_id = :omophoto_id AND action_user_id = :action_user_id FOR UPDATE";
		$result = DB::query($sql)
								->parameters(array('omophoto_id'=> $omophoto_id,
									'action_user_id'=> $user_id))
					->execute()
					->as_array();
		return $result;
	}

	 /**
	 * @param int $omophoto_id
	 * @param int $user_id
	 * @param int $action_user_id
	 * @param int $points
	 * @return Model_User
	 */
	public static function new_record($omophoto_id,$user_id,$action_user_id,$points){
		$record = static::forge();
		$record->omophoto_id = $omophoto_id;
		$record->user_id = $user_id;
		$record->action_user_id = $action_user_id;
		$record->points = $points;
		$record->delete_flg = static::DELETE_FLG_FALSE;

		return $record;
	}

	 /**
	 * 指定おもフォトへの評価者一覧(新着順)
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $omophoto_id
	 *
	 * @param int
	 *
	 * @return array
	 */
	public static function list_data($need_num, $max_id=null, $omophoto_id=null){

		$query = DB::select('oa.id','oa.omophoto_id','oa.action_user_id','oa.points','oa.created_at');
		$query->from(array(static::table(),'oa'));
		$query->join(array(Model_Omophoto::table(),'o'), 'Inner');
		$query->on('oa.omophoto_id', '=','o.id');
		$query->join(array(Model_User::table(),'u'), 'Inner');
		$query->on('oa.action_user_id', '=','u.id');
		$query->where(array('oa.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where(array('u.delete_flg'=>static::DELETE_FLG_FALSE));
		if(!empty($omophoto_id)){
			$query->where('oa.omophoto_id','=',$omophoto_id);
		}
		$query->limit($need_num);
		$query->order_by('oa.id','desc');
		$query->as_object('Model_Omophoto_Appraisal');

		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('oa.id', '<', $max_id);
		}

		$results = $query->execute();

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			self::$_max_id = $result->id;
			$format_data = $result->format_response();
			$respons[] = $format_data;
		}

		return  $respons;
	}

	/**
	 * 指定おもフォトへの評価者一覧に付加データを付けて返すメソッド
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $omophoto_id
	 *
	 * @return array
	 */
	public static function appraiser_lists($need_num, $max_id=null, $omophoto_id=null){
		$respons['appraisals'] = self::list_data($need_num, $max_id, $omophoto_id);
		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['appraisals']);

		return  $respons;
	}

	 /**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function format_response(array $data = array()){

		return parent::format_response(array(
				'user' => Model_User::get_format_user_data($this->action_user_id),
				'omophoto_id' => $this->omophoto_id,
				'points' => $this->points,
				'created_at' => $this->created_at,

		));
	}

	/**
	 * 指定おもフォトへの評価者一覧(新着順)
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 *
	 * @return array
	 */
	public static function user_appraised_list_data($need_num, $max_id=null, $user_id=null){

		$query = DB::select('oa1.id','oa1.omophoto_id', DB::expr('SUM(oa2.points) as points'), 'o.photo_id', 'o.user_id', 'o.image_file_name', 'o.title', 'o.created_at', 'o.image_file_size', 'o.image_width','o.image_height');
		$query->from(array(static::table(),'oa1'));
		$query->join(array(Model_Omophoto::table(),'o'), 'Inner');
		$query->on('oa1.omophoto_id', '=','o.id');
		$query->join(array(static::table(),'oa2'), 'Left');
		$query->on('oa2.omophoto_id', '=','oa1.omophoto_id');
		$query->where(array('oa1.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		if(!empty($user_id)){
			$query->where('oa1.action_user_id','=',$user_id);
		}
		$query->limit($need_num);
		$query->order_by('oa1.id','desc');
		$query->group_by('oa2.omophoto_id');
		$query->as_object('Model_Omophoto_Appraisal');

		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('oa1.id', '<', $max_id);
		}

		$results = $query->execute();

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			self::$_max_id = $result->id;
			$format_data = $result->omophoto_format_response();
			$respons[] = $format_data;
		}

		return  $respons;
	}

	/**
	 * 指定おもフォトへの評価者一覧に付加データを付けて返すメソッド
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 *
	 * @return array
	 */
	public static function omophoto_user_appraised_list($need_num, $max_id=null, $user_id=null){
		$respons['omophotos'] = self::user_appraised_list_data($need_num, $max_id, $user_id);
		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['omophotos']);

		return  $respons;
	}

	 /**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function omophoto_format_response(array $data = array()){

		if(is_null($this->points)){
			$this->points = 0;
		}

		$login_user_points = self::get_login_user_points_by_omophoto_id($this->omophoto_id);

		return parent::format_response(array(
				'omophoto_id' => $this->omophoto_id,
				'image_url' => Util_Url::get_omophotoimage_url($this->omophoto_id,$this->image_file_name),
				'image_size' => $this->image_file_size,
				'image_width' => $this->image_width,
				'image_height' => $this->image_height,
				'created_at' => $this->created_at,
				'title' => $this->title,
				'total_points' => $this->points,
				'login_user_points' => $login_user_points,
				'user' => Model_User::get_format_user_data($this->user_id),
				'photo' => Model_Photo::get_format_photo_data($this->photo_id),

		));
	}


}