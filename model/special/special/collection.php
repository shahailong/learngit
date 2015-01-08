<?php
/**
 * 
 * @author k-kawaguchi
 */
class Model_Special_Collection extends Model_Base
{
	const LINKED_TYPE_0 = 0; // フォト
	const LINKED_TYPE_1 = 1; // おもフォト
	const BANNER_DISPLAY_flg = 1; // 特集バナー表示フラグ  1:表示
	const DELETE_FLG = 0;
	
	const CENSORSHIP_FLG_PENDING = 0; // 検閲待ち
	const CENSORSHIP_FLG_PROCESSING  = 1; // 検閲中
	const CENSORSHIP_FLG_COMPLETE = 2; // 検閲完了

	const IMAGE_DELETE_STATUS_DEFAULT = 0;
	const IMAGE_DELETE_STATUS_WAIT = 1; // 画像削除待ち
	const IMAGE_DELETE_STATUS_COMPLETE = 2; // 画像削除完了
	const IMAGE_DELETE_STATUS_FAIL = 3; // 画像削除失敗

	protected static $_max_id = NULL;
	
	protected static $_properties = array(
		'id',
		'start_date',
		'end_date',
		'banner_img',
		'banner_link',
		'banner_display_flg',
		'delete_at',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'special_collection';

	public static $response_root_element = 'special_collection';
	
	
	/**
	 * 
	 *
	 * @param int $need_num
	 * @param int $special_collection_id 
	 * @param int $max_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function get_special_photo($need_num, $special_collection_id, $max_id = null, $no_cache = null){
		$respons['photos'] = self::list_photo_data($need_num, $special_collection_id, $max_id, $no_cache);
		$respons['max_id'] = self::$_max_id;
		$respons['special_collection_id'] = $special_collection_id;
		$respons['current_count'] = count($respons['photos']);

		return  $respons;
	}	
	
	/**
	 * 
	 *
	 * @param int $need_num
	 * @param int $special_collection_id 
	 * @param int $max_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function list_photo_data($need_num, $special_collection_id, $max_id, $no_cache){

		$sql = "select p.id,p.user_id,p.image_file_name,p.image_file_size,p.created_at from special_collection as sc".
				" inner join special_collection_linked as scl on sc.id = scl.special_collection_id".
				" inner join photos as p on scl.linked_photo_id = p.id".
				" where scl.linked_type = ".static::LINKED_TYPE_0.
				" and  scl.delete_at is null and sc.delete_at is null".
				" and sc.banner_display_flg = ".static::BANNER_DISPLAY_flg.
				" and (sc.start_date <= now() and sc.end_date >= now())".
				" and sc.id = ".$special_collection_id.
				" and p.delete_flg = ".static::DELETE_FLG;
		
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$sql .=	" and p.id < ".$max_id;
					
		}
		$sql .=	 " order by p.id desc limit ".$need_num;
		Log::info($sql);
		if($no_cache == 1){
			$results = DB::query($sql)->execute();	
		}else{
			//$cache_key = "db." .self::$_table_name .".n{$need_num}_m{$max_id}_u{$user_id}_p{$photo_id}_o{$official_flg}";
			$cache_key = "";
			$results = DB::query($sql)->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
		}		
		
		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			$format_data = $result->photo_format_response();
			self::$_max_id = $format_data['id'];
			$respons[] = $format_data;
		}

		return  $respons;	
		
	}	
	
	 /**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function photo_format_response(array $data = array()){
		return parent::format_response(array(
				'id' => $this->id,
				'user_id' => $this->user_id,
				'image_file_name' => $this->image_file_name,
				'image_file_size' => $this->image_file_size,
				'created_at' => $this->created_at
		));
	}
	
	/**
	 * おもフォト一覧(新着順)データを付けて返すメソッド
	 *
	 * @param int $need_num
	 * @param int $special_collection_id 
	 * @param int $max_id
	 * @param int order_type 
	 * @param int $no_cache
	 * @param datetime $max_target_at
	 * @return array
	 */		
	public static function get_special_omophoto($need_num, $special_collection_id, $max_id = null, $order_type,$no_cache = null,$max_target_at){
		//リストの取得
		if(order_type == 1){
			//1:新着順の場合
			$respons['omophotos'] = self::list_omophoto_data_by_id($need_num, $special_collection_id, $max_id, $no_cache);
		}else if(order_type == 2){	
			//　2:人気順の場合
			$respons['omophotos'] = self::list_omophoto_data_by_ranking($need_num, $special_collection_id, $max_id, $no_cache,$max_target_at);
		}

		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['omophotos']);

		return  $respons;
	}	
	
	/**
	 * 【リストの取得】（※新着順の場合）
	 *
	 * @param int $need_num
	 * @param int $special_collection_id 
	 * @param int $max_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function list_omophoto_data_by_id($need_num, $special_collection_id, $max_id, $no_cache){
		$query = DB::select('o.id', 'o.photo_id', 'o.user_id','o.image_file_name','o.title','o.created_at', DB::expr('SUM(oa.points) as total_points'));
		$query->from(array(static::table(),'sc'));
		$query->join(array(Model_Special_Collection_Linked::table(),'scl'), 'Inner');
		$query->on('sc.id', '=','scl.special_collection_id');	
		$query->join(array(Model_Omophoto::table(),'o'), 'Inner');
		$query->on('scl.linked_photo_id', '=','o.id');				
		$query->join(array(Model_Omophoto_Appraisal::table(),'oa'), 'Left');
		$query->on('oa.omophoto_id', '=','o.id');		
		$query->where('scl.linked_type',static::LINKED_TYPE_1);
		//$query->where('scl.delete_at is null');
		//$query->where('sc.delete_at is null');
		$query->where('sc.banner_display_flg',static::BANNER_DISPLAY_flg);
		//$query->where('sc.start_date', '<=', now());
		//$query->where('sc.end_date', '>=', now());		
		$query->where('sc.id', '=', $special_collection_id);
		$query->where('o.delete_flg',static::DELETE_FLG);
		$query->limit($need_num);
		$query->order_by('o.id','desc');
		$query->group_by('o.id');
		$query->as_object('Model_Special_Collection');		
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('o.id', '<', $max_id);
		}			

		$results = $query->execute();

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			self::$_max_id = $result->id;
			$format_data = $result->omophoto_format_response_by_id();
			$respons[] = $format_data;
		}

		return  $respons;	
		
	}		
	
	/**
	 * 【リストの取得】（※人気順の場合）
	 *
	 * @param int $need_num
	 * @param int $special_collection_id 
	 * @param int $max_id
	 * @param int $no_cache
	 * @param datetime $target_at
	 *
	 * @return array
	 */
	public static function list_omophoto_data_by_ranking($need_num, $special_collection_id, $max_id, $no_cache, $target_at){
		$query = DB::select('rnk.ranking_id', DB::expr('SUM(oa.points) as points'), 'o.id', 'o.photo_id', 'o.user_id', 'o.image_file_name', 'o.title', 'o.created_at', 'o.image_file_size', 'o.image_width','o.image_height');
		$query->from(array(static::table(),'sc'));
		$query->join(array(Model_Special_Collection_Linked::table(),'scl'), 'Inner');
		$query->on('sc.id', '=','scl.special_collection_id');	
		$query->join(array(Model_Omophoto::table(),'o'), 'Inner');
		$query->on('scl.linked_photo_id', '=','o.id');		
		$query->join(array(Model_Omophoto_Ranking_Total::table(),'rnk'), 'Inner');
		$query->on('o.id', '=','rnk.omophoto_id');			
		$query->join(array(Model_Omophoto_Appraisal::table(),'oa'), 'Left');
		$query->on('oa.omophoto_id', '=','rnk.omophoto_id');			
		$query->where('scl.linked_type',static::LINKED_TYPE_1);
		//$query->where('scl.delete_at is null');
		//$query->where('sc.delete_at is null');
		$query->where('sc.banner_display_flg',static::BANNER_DISPLAY_flg);
		//$query->where('sc.start_date', '<=', now());
		//$query->where('sc.end_date', '>=', now());		
		$query->where('sc.id', '=', $special_collection_id);
		if(isset($target_at)){
			$query->where('rnk.target_at', '=', $target_at);
		}
		$query->where('o.delete_flg',static::DELETE_FLG);
		$query->where('rnk.delete_flg',static::DELETE_FLG);
		$query->limit($need_num);
		$query->order_by('rnk.ranking_id','asc');
		$query->group_by('rnk.ranking_id');
		$query->as_object('Model_Special_Collection');		
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('rnk.ranking_id', '>', $max_id);
		}			

		$results = $query->execute();

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			self::$_max_id = $result->ranking_id;
			$format_data = $result->omophoto_format_response_by_ranking();
			$respons[] = $format_data;
		}

		return  $respons;
	}			
	
	 /**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function omophoto_format_response_by_id(array $data = array()){																											
		
		return parent::format_response(array(
				'id' => $this->id,
				'photo_id' => $this->photo_id,
				'user_id' => $this->user_id,
				'image_file_name' => $this->image_file_name,
				'title' => $this->title,
				'created_at' => $this->created_at,
				'total_points' => $this->total_points
		));
	}	
	
	 /**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function omophoto_format_response_by_ranking(array $data = array()){																											

		return parent::format_response(array(
				'ranking_id' => $this->ranking_id,
				'points' => $this->points,
				'id' => $this->id,
				'photo_id' => $this->photo_id,
				'user_id' => $this->user_id,
				'image_file_name' => $this->image_file_name,
				'title' => $this->title,
				'created_at' => $this->created_at,
				'image_file_size' => $this->image_file_size,
				'image_width' => $this->image_width,
				'image_height' => $this->image_height
		));
	}	
	
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