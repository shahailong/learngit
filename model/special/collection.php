<?php
/**
 * フォト
 *
 * @author a-kido
 */
class Model_Special_Collection extends Model_Base
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
		'start_date',
		'end_date',
		'banner_img',
		'banner_link',
		'banner_display_flg',
		'delete_at',
		'created_at',
		'updated_at',
	);

	protected static $_table_name = 'special_collection';

	public static $response_root_element = 'special_collection';

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
	public static function filter_response_records($excutor_id, array $special_collection_models){
		if(!$special_collection_models){
			return $special_collection_models;
		}

		$return_ary = array();

		foreach($special_collection_models as $special_collection_model){
			if(!($special_collection_model instanceof Model_Special_Collection)){
				throw new Exception_Logic('Illegal argument.');
			}

			if((string)$excutor_id != (string)$special_collection_model->id){
				// プライバシーに関わる情報をマスクする
				$special_collection_model->mask_unvisible_info();
			}
			$return_ary[] = $special_collection_model;
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
	public static function new_record($id, $start_date = null, $end_date = null, $banner_img = null, $banner_link = null){
		$record = static::forge();
		$record->id = $id;
		$record->banner_img = $banner_img;
	  $record->banner_link = $banner_link;
		$record->censorship_flg = static::CENSORSHIP_FLG_PENDING;
		$record->delete_flg = static::DELETE_FLG_FALSE;
		$record->image_delete_status = static::IMAGE_DELETE_STATUS_DEFAULT;

		return $record;
	}

	/**
	 * 特集の取得件数を期間内で指定して写真一覧を取得します。
	 *
	 * @param int $need_num
	 * @param int $max_id 必要なし
	 * @param int $user_id 必要なし
	 * @param int $no_cache 必要なし
	 *
	 * @return array
	 */
	public static function list_data($need_num, $max_id = null, $user_id=null, $no_cache=null){

		// カレントページのデータ取得
		$query = DB::select('dr.*');
		$query->from(array(static::table(),'dr'));
		//$query->where('dr.start_date','<=','2015-01-06 19:00:24');
		$query->where(array('dr.delete_at'=>NULL));
		$query->where(array('dr.banner_display_flg'=>1));
		$query->where('dr.start_date','<=',DB::expr('NOW()'));
		$query->where('dr.end_date','>=',DB::expr('NOW()'));
		//$query->where('NOW()','<=',dr.end_date);
		//'DB::expr('NOW()')'

		$query->limit($need_num);
		$query->order_by('dr.id','desc');
		$query->as_object('Model_Special_Collection');

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
			self::$_max_id = $format_data['special_collection_id'];
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
			'special_collection_id' => $this->id,
			//'image_url' => Util_Url::get_photoimage_url($this->id,$this->image_file_name),
			'banner_img' => $this->banner_img,
			'banner_link' => $this->banner_link,
			//'user' => Model_User::get_format_user_data($this->user_id),
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