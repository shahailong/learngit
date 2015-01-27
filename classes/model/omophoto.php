<?php
/**
 * おもフォト
 *
 * @author a-kido
 */
class Model_Omophoto extends Model_Base
{
	const CENSORSHIP_FLG_PENDING = 0; // 検閲待ち
	const CENSORSHIP_FLG_PROCESSING  = 1; // 検閲中
	const CENSORSHIP_FLG_COMPLETE = 2; // 検閲完了

	const IMAGE_DELETE_STATUS_DEFAULT = 0;
	const IMAGE_DELETE_STATUS_WAIT = 1; // 画像削除待ち
	const IMAGE_DELETE_STATUS_COMPLETE = 2; // 画像削除完了
	const IMAGE_DELETE_STATUS_FAIL = 3; // 画像削除失敗

	protected static $_properties = array(
		'id',
		'photo_id',
		'user_id',
		'image_file_name',
		'image_file_size',
		'image_width',
		'image_height',
		'title',
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
	protected static $_table_name = 'omophotos';

	public static $response_root_element = 'omophotos';

	protected static $_max_id = NULL;

	/**
	 * 追加情報を付加したり特定の項目をマスクしたりします<br />
	 *
	 * @param int $excutor_user_id
	 * @param Model_Omophoto $omophoto_models
	 *
	 * @return array
	 * @throws Exception_Logic
	 */
	public static function filter_response_records($excutor_user_id, array $omophoto_models){
		if(!$omophoto_models){
			return $omophoto_models;
		}

		$return_ary = array();


		foreach($omophoto_models as $omophoto_model){
			if(!($omophoto_model instanceof Model_Omophoto)){
				throw new Exception_Logic('Illegal argument.');
			}

			if((string)$excutor_user_id != (string)$omophoto_model->user_id){
				// プライバシーに関わる情報をマスクする
				$omophoto_models->mask_unvisible_info();
			}
			$return_ary[] = $omophoto_model;
		}
		return $return_ary;
	}

	/**
	 * 非公開のプロフィール情報をマスクします。<br />
	 *
	 * @return Model_Omophoto
	 */
	public function mask_unvisible_info(){
		//$this->login_id = null;
		return $this;
	}

	/**
	 * 新規レコードを返却します。
	 *
	 * @param int $user_id
	 * @param int $photo_id
	 * @param string $image_file_name
	 * @param string $title
	 * @param string $image_file_size
	 * @param int $image_width
	 * @param int $image_height
	 *
	 * @return Model_Omophoto
	 */
	public static function new_record($user_id, $photo_id, $image_file_name = null, $title = null, $image_file_size = null, $image_width = null, $image_height = null){
		$record = static::forge();
		$record->user_id = $user_id;
		$record->photo_id = $photo_id;
		$record->image_file_name = $image_file_name;
		$record->image_file_size = $image_file_size;
		$record->image_width = $image_width ? $image_width : 0;
	   	$record->image_height = $image_height ? $image_height : 0;
		$record->title = $title;
		$record->points = 0;
		$record->censorship_flg = static::CENSORSHIP_FLG_PENDING;
		$record->delete_flg = static::DELETE_FLG_FALSE;
		$record->image_delete_status = static::IMAGE_DELETE_STATUS_DEFAULT;

		return $record;
	}

	/**
	 * おもフォト一覧(新着順)
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $photo_id
	 * @param int $official_flg
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function list_data($need_num, $max_id = null, $user_id= null, $photo_id = null, $official_flg = null, $no_cache = null){

		// カレントページのデータ取得
		$query = DB::select('o.id','o.photo_id','o.user_id','o.image_file_name','o.image_file_size','o.image_width','o.image_height','o.title','o.created_at',DB::expr('SUM(oa.points) as points'));
		$query->from(array(static::table(),'o'));
		$query->join(array(Model_Omophoto_Appraisal::table(),'oa'), 'LEFT');
		$query->on('oa.omophoto_id', '=','o.id');
		$query->join(array(Model_User::table(),'u'), 'LEFT');
		$query->on('u.id', '=','o.user_id');
		if(!empty($user_id)){
			$query->where('o.user_id','=',$user_id);
		}
		if(!empty($photo_id)){
			$query->where('o.photo_id','=',$photo_id);
		}
		if(!empty($official_flg)){
			$query->where('u.official_flg','=',$official_flg);
		}
		$query->where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->limit($need_num);
		$query->group_by('o.id');
		$query->order_by('o.id','desc');
		$query->as_object('Model_Omophoto');
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('o.id', '<', $max_id);
		}

		if($no_cache == 1){
			$results = $query->execute();
		}else{
			$cache_key = "db." .self::$_table_name .".n{$need_num}_m{$max_id}_u{$user_id}_p{$photo_id}_o{$official_flg}";
			$results = $query->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
		}

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			$format_data = $result->format_response();
			self::$_max_id = $format_data['omophoto_id'];
			$respons[] = $format_data;
		}

		return  $respons;
	}

	/**
	 * おもフォト一覧付加データを付けて返すメソッド
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $photo_id
	 * @param int $official_flg
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function omophoto_lists($need_num, $max_id = null, $user_id = null, $photo_id = null, $official_flg = null, $no_cache = null){
		$respons['omophotos'] = self::list_data($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache);
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
	public function format_response(array $data = array()){

		if(is_null($this->points)){
			$this->points = 0;
		}

		$login_user_points = Model_Omophoto_Appraisal::get_login_user_points_by_omophoto_id($this->id);

		return parent::format_response(array(
				'omophoto_id' => $this->id,
				'image_url' => Util_Url::get_omophotoimage_url($this->id,$this->image_file_name),
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

	 /**
	 *
	 * @param int $id
	 * @return Model_Omophoto
	 */
	public static function get_by_id($id){
		return static::find($id);
	}

	/**
	* 指定おもフォトIDのデータを取得
	*
	* @param int $id
	*
	* @return Model_Omophoto
	*/
	public static function get_format_omophoto_data($id){
		// カレントページのデータ取得
		$query = DB::select('o.id','o.photo_id','o.user_id','o.image_file_name','o.image_file_size','o.image_width','o.image_height','o.title','o.created_at',DB::expr('SUM(oa.points) as points'));
		$query->from(array(static::table(),'o'));
		$query->join(array(Model_Omophoto_Appraisal::table(),'oa'), 'LEFT');
		$query->on('oa.omophoto_id', '=','o.id');
		$query->where('o.id','=',$id);
		$query->where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->group_by('o.id');
		$query->order_by('o.id','desc');
		$query->as_object('Model_Omophoto');
		$results = $query->execute();

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}
		$result = $results[0];
		$format_data = $result->format_response();

		return $format_data;
	}
}