<?php
/**
 * フォト
 *
 * @author a-kido
 */
class Model_Photo extends Model_Base
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
	protected static $_table_name = 'photos';

	public static $response_root_element = 'photos';

	protected static $_max_id = NULL;

	/**
	 * 追加情報を付加したり特定の項目をマスクしたりします<br />
	 *
	 * @param int $excutor_user_id
	 * @param Model_Photo $photo_models
	 *
	 * @return array
	 * @throws Exception_Logic
	 */
	public static function filter_response_records($excutor_user_id, array $photo_models){
		if(!$photo_models){
			return $photo_models;
		}

		$return_ary = array();

		foreach($photo_models as $photo_model){
			if(!($photo_model instanceof Model_Photo)){
				throw new Exception_Logic('Illegal argument.');
			}

			if((string)$excutor_user_id != (string)$photo_model->user_id){
				// プライバシーに関わる情報をマスクする
				$photo_model->mask_unvisible_info();
			}
			$return_ary[] = $photo_model;
		}
		return $return_ary;
	}

	/**
	 * 非公開のプロフィール情報をマスクします。<br />
	 *
	 * @return Model_Photo
	 */
	public function mask_unvisible_info(){
		//$this->login_id = null;
		return $this;
	}

	/**
	 * 新規レコードを返却します。
	 *
	 * @param int $user_id
	 * @param string $image_file_name
	 * @param int $image_file_size
	 * @param int $image_width
	 * @param int $image_height
	 *
	 * @return Model_Photo
	 */
	public static function new_record($user_id, $image_file_name = null, $image_file_size = null, $image_width = null, $image_height = null){
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

	/**
	 * 取得件数を指定して写真一覧を取得します。
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function list_data($need_num, $max_id = null, $user_id=null, $no_cache=null){

		// カレントページのデータ取得
		$query = DB::select('p.*');
		$query->from(array(static::table(),'p'));
		$query->where(array('p.delete_flg'=>static::DELETE_FLG_FALSE));
		if(!empty($user_id)){
			$query->where('p.user_id','=',$user_id);
		}
		$query->limit($need_num);
		$query->order_by('p.id','desc');
		$query->as_object('Model_Photo');
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('p.id', '<', $max_id);
		}

		if($no_cache == 1){
			$results = $query->execute();
		}else{
			$cache_key = "db." .self::$_table_name .".n{$need_num}_m{$max_id}_u{$user_id}";
			$results = $query->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
		}

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			$format_data = $result->format_response();
			self::$_max_id = $format_data['photo_id'];
			$respons[] = $format_data;
		}

		return  $respons;
	}

	/**
	 * フォト一覧付加データを付けて返すメソッド
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function photo_lists($need_num, $max_id = null, $user_id=null, $no_cache = null){
		$respons['photos'] = self::list_data($need_num,$max_id,$user_id,$no_cache);
		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['photos']);

		return  $respons;
	}

	 /**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function format_response(array $data = array()){

		$excutor_user_id = self::get_executor_user_id();

		return parent::format_response(array(
			'photo_id' => $this->id,
			'image_url' => Util_Url::get_photoimage_url($this->id,$this->image_file_name),
			'image_size' => $this->image_file_size,
			'image_width' => $this->image_width,
			'image_height' => $this->image_height,
			'created_at' => $this->created_at,
			'user' => Model_User::get_format_user_data($this->user_id),
		));
	}

	 /**
	 *
	 * @param int $id
	 * @return Model_Photo
	 */
	public static function get_by_id($id){
		return static::find($id);
	}

	/**
	* おもフォトなどの付加情報として取得するフォト情報
	*
	* フォトが削除された場合、紐づくおもフォトも削除されるが、
	* おもフォト一覧がキャッシュを使用するため、削除されたフォト情報も取得できる必要がある。
	*
	* @param int $id
	*
	* @return Model_Photo
	*/
	public static function get_format_photo_data($id){
		$photo_data = Model_Photo::query_default()
			->select('id','user_id','image_file_name','image_file_size','image_width','image_height','created_at','updated_at')
			->where('id',$id)
			->get_one();

		if(count($photo_data) == 0){
			return NULL;
		}

		$format_data = $photo_data->format_response();

		return $format_data;
	}
}