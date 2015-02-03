<?php
/**
 * 
 * @author k-kawaguchi
 * テーブル構造が変わります、BJT:2015/01/26 Edit by Sha
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
	
	//global $gid;

	protected static $_max_id = NULL;
	
	protected static $_properties = array(
		'id',
		'display_order',
		'name',
		'introduction',
		'start_date',
		'end_date',
		'sp_banner_img',
		'sp_banner_link',
		'pc_banner_img',
		'pc_banner_link',
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
	 * @auther Sha
	 * @param int $need_num 
	 * @param int $max_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function get_old_data($need_num, $max_id = null, $no_cache = null){
		$respons['special_collections'] = self::old_data($need_num, $max_id, $no_cache);
		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['special_collections']);

		return  $respons;
	}	
	
	/**
	 * PC対応
	 * @auther Sha
	 * @param int $need_num 
	 * @param int $max_id
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function get_old_datap($need_num, $max_id = null, $no_cache = null){
		$respons['special_collections'] = self::old_datap($need_num, $max_id, $no_cache);
		$respons['max_id'] = self::$_max_id;
		$respons['current_count'] = count($respons['special_collections']);

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
				" and scl.delete_at is null and sc.delete_at is null".
				" and sc.banner_display_flg = ".static::BANNER_DISPLAY_flg.
				" and (sc.start_date <= now() and sc.end_date >= now())".
				" and sc.id = ".$special_collection_id.
				" and p.delete_flg = ".static::DELETE_FLG;
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$sql .=	" and p.id < ".$max_id;		
		}
		$sql .=	 " order by p.id desc limit ".$need_num.";";
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
			$format_data = array('id' => $result['id'],
							'user_id' => $result['user_id'],
							'image_file_name' => $result['image_file_name'],
							'image_file_size' => $result['image_file_size'],
							'created_at' =>$result['created_at']);
			self::$_max_id = $format_data['id'];
			$respons[] = $format_data;
		}
		
		return  $respons;	
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
		if($order_type == '1'){
			//1:新着順の場合
			$respons['omophotos'] = self::list_omophoto_data_by_id($need_num, $special_collection_id, $max_id, $no_cache);
		}else if($order_type == '2'){	
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
	
		$sql = "select o.id,o.photo_id,o.user_id,o.image_file_name,o.title,o.created_at,sum(oa.points) as total_points".
		" from special_collection as sc".
		" inner join special_collection_linked as scl on sc.id = scl.special_collection_id".
		" inner join omophotos as o on scl.linked_photo_id = o.id".
		" left join omophoto_appraisals as oa on oa.omophoto_id = o.id".
		" where scl.linked_type = ".static::LINKED_TYPE_1.
		" and scl.delete_at is null and sc.delete_at is null".
		" and sc.banner_display_flg = ".static::BANNER_DISPLAY_flg.
		" and (sc.start_date <= now() and sc.end_date >= now())".
		" and sc.id = ".$special_collection_id;
		" and o.delete_flg = ".static::DELETE_FLG;
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$sql .=	" and o.id < ".$max_id;
		}
		$sql .= " group by o.id".
		" order by o.id desc limit ".$need_num.";";
		Log::info($sql);
		if($no_cache == 1){
			$results = DB::query($sql)->execute();	
		}else{
			$cache_key = "";
			$results = DB::query($sql)->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
		}		
					
		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			$total_points = 0;
			if(isset($result['total_points'])){
				$total_points = $result['total_points'];
			}		
			$format_data = array('id' => $result['id'],
								'photo_id' => $result['photo_id'],
								'user_id' => $result['user_id'],
								'image_file_name' => $result['image_file_name'],
								'title' => $result['title'],
								'created_at' =>  $result['created_at'],
								'total_points' => $total_points
								);
			self::$_max_id = $format_data['id'];
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

		$sql = "select rnk.ranking_id, sum(oa.points) as points, o.id, o.photo_id, o.user_id, o.image_file_name, o.title, o.created_at, o.image_file_size, o.image_width,o.image_height".
		" from special_collection as sc".
		" inner join special_collection_linked as scl on sc.id = scl.special_collection_id".
		" inner join omophotos as o on scl.linked_photo_id = o.id".
		" inner join omophoto_ranking_totals as rnk on o.id = rnk.omophoto_id".
		" left join omophoto_appraisals as oa on oa.omophoto_id = rnk.omophoto_id".
		" where scl.linked_type = ".static::LINKED_TYPE_1.
		" and scl.delete_at is null and sc.delete_at is null".
		" and sc.banner_display_flg = ".static::BANNER_DISPLAY_flg.
		" and (sc.start_date <= now() and sc.end_date >= now())".
		" and sc.id = ".$special_collection_id.
		" and o.delete_flg = ".static::DELETE_FLG." and rnk.delete_flg = ".static::DELETE_FLG;
		if(isset($target_at)){
			$sql .= " and rnk.target_at = '".$target_at."'";
		}		
		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$sql .=	" and rnk.ranking_id > ".$max_id;
		}		
		$sql .= " group by rnk.ranking_id".
		" order by rnk.ranking_id asc limit ".$need_num.";";
		Log::info($sql);
		if($no_cache == 1){
			$results = DB::query($sql)->execute();	
		}else{
			$cache_key = "";
			$results = DB::query($sql)->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
		}	

		$respons = array();

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			$points = 0;
			if(isset($result['points'])){
				$points = $result['points'];
			}				
			$format_data = array('ranking_id' => $result['ranking_id'],
								'points' => $points,
								'id' => $result['id'],
								'photo_id' => $result['photo_id'],
								'user_id' => $result['user_id'],
								'image_file_name' => $result['image_file_name'],
								'title' => $result['title'],
								'created_at' => $result['created_at'],
								'image_file_size' => $result['image_file_size'],
								'image_width' => $result['image_width'],
								'image_height' => $result['image_height']);
			self::$_max_id = $format_data['ranking_id'];
			$respons[] = $format_data;
		}

		return  $respons;
	}			
	
	/**
	 * START FROM HERE
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
	public static function new_record($id, $start_date = null, $end_date = null, $sp_banner_img = null, $sp_banner_link = null, $type = null){
		$record = static::forge();
		$record->id = $id;
		$record->banner_img = $sp_banner_img;
		$record->banner_link = $sp_banner_link;
		$record->type = $type;
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
		$query->where(array('dr.delete_at'=>NULL));
		$query->where(array('dr.banner_display_flg'=>1));
		$query->where('dr.start_date','<=',DB::expr('NOW()'));
		//150202 Phase5対応
		//$query1->where('dr.end_date','>=',DB::expr('NOW()'));
		//$query1->where(array('dr.type'=>1));
		$query->order_by('dr.display_order','desc');
		$query->limit(3);		

		//$query->limit($need_num);
		//$query->order_by('dr.id','desc');
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
			//$respons[] = $format_data1;
			array_push($respons, $format_data);
		}
		
		return  $respons;	
		
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
	public static function list_datap($need_num, $max_id = null, $user_id=null, $no_cache=null){
		// カレントページのデータ取得
		$query = DB::select('dr.*');
		$query->from(array(static::table(),'dr'));
		$query->where(array('dr.delete_at'=>NULL));
		$query->where(array('dr.banner_display_flg'=>1));
		$query->where('dr.start_date','<=',DB::expr('NOW()'));
		//$query1->where('dr.end_date','>=',DB::expr('NOW()'));
		//$query1->where(array('dr.type'=>1));
		$query->order_by('dr.display_order','desc');
		$query->limit(3);		

		//$query->limit($need_num);
		//$query->order_by('dr.id','desc');
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
			//$respons[] = $format_data1;
			array_push($respons, $format_data);
		}

		return  $respons;

	}
	
	/**
	 * 過去特集の取得件数を期間内で指定して写真一覧を取得します。
	 * 
	 * @param int $need_num
	 * @param int $max_id 必要なし
	 * @param int $user_id 必要なし
	 * @param int $no_cache 必要なし
	 *
	 * @return array
	 */
	public static function old_data($need_num, $max_id = null, $no_cache=null){
		//SP
		// カレントページのデータ取得
		$sql2 = "select sc.id, sc.display_order, sc.sp_banner_img, sc.sp_banner_link, sc.name, sc.introduction from special_collection as sc".
				" where sc.delete_at is null".
				" and sc.banner_display_flg = ".static::BANNER_DISPLAY_flg.
				" and sc.start_date <= now()";
				if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
					$sql2 .=	" and sc.id < ".$max_id;		
				}
				$sql2 .=	 " order by sc.display_order desc limit ".$need_num.";";
				Log::info($sql2);
				if($no_cache == 1){
					$results2 = DB::query($sql2)->execute();
				}else{
					//$cache_key = "db." .self::$_table_name .".n{$need_num}_m{$max_id}_u{$user_id}_p{$photo_id}_o{$official_flg}";
					$cache_key = "";
					$results2 = DB::query($sql2)->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
				}	
						
		$respons2 = array();

		if(count($results2) == 0){
			return $respons2;
		}

		foreach ($results2 as $result){
			$format_data = array('id' => $result['id'],
							'display_order' => $result['display_order'],
							'banner_img' => $result['sp_banner_img'],
							'banner_link' => $result['sp_banner_link'],
							'name' => $result['name'],
							'introduction' =>$result['introduction']);
							//'omophoto_list' =>$respons);

			self::$_max_id = $format_data['display_order'];
			$respons2[] = $format_data;
		}
		
		return  $respons2;

	}
	
	public static function old_datap($need_num, $max_id = null, $no_cache=null){
	
			$sql2 = "select sc.id, sc.display_order, sc.pc_banner_img, sc.pc_banner_link, sc.name, sc.introduction from special_collection as sc".
				" where sc.delete_at is null".
				" and sc.banner_display_flg = ".static::BANNER_DISPLAY_flg.
				" and sc.start_date <= now()";
				if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
					$sql2 .=	" and sc.id < ".$max_id;		
				}
				$sql2 .=	 " order by sc.display_order desc limit ".$need_num.";";
				Log::info($sql2);
				if($no_cache == 1){
					$results2 = DB::query($sql2)->execute();
				}else{
					//$cache_key = "db." .self::$_table_name .".n{$need_num}_m{$max_id}_u{$user_id}_p{$photo_id}_o{$official_flg}";
					$cache_key = "";
					$results2 = DB::query($sql2)->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
				}	
						
		$respons2 = array();

		if(count($results2) == 0){
			return $respons2;
		}

		foreach ($results2 as $result){
			$format_data = array('id' => $result['id'],
							'display_order' => $result['display_order'],
							'banner_img' => $result['pc_banner_img'],
							'banner_link' => $result['pc_banner_link'],
							'name' => $result['name'],
							'introduction' =>$result['introduction']);
							
			self::$_max_id = $format_data['display_order'];
			$respons2[] = $format_data;
		}
		
		return  $respons2;

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
		
		$issp = Util_Common::is_mobile_request();
		if($issp == true){
			return parent::format_response(array(
				'special_collection_id' => $this->id,
				'display_order' => $this->display_order,
				//'image_url' => Util_Url::get_photoimage_url($this->id,$this->image_file_name),
				'banner_img' => $this->sp_banner_img,
				'banner_link' => $this->sp_banner_link,
				//'user' => Model_User::get_format_user_data($this->user_id),
		));
			}
			else{
				return parent::format_response(array(
					'special_collection_id' => $this->id,
					'display_order' => $this->display_order,
					//'image_url' => Util_Url::get_photoimage_url($this->id,$this->image_file_name),
					'banner_img' => $this->pc_banner_img,
					'banner_link' => $this->pc_banner_link,
			));
				}
	}
	
	 /**
	 * 過去特集一覧取得の戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function format_response_ks(array $data = array()){

		$excutor_user_id = self::get_executor_user_id();
		$issp = self::is_mobile_request();
		if($issp == true){
			return parent::format_response(array(
			'special_collection_id' => $this->id,
			//'image_url' => Util_Url::get_photoimage_url($this->id,$this->image_file_name),
			'banner_img' => $this->sp_banner_img,
			'banner_link' => $this->sp_banner_link,
			'name' => $this->name,
			'introduction' => $this->introduction,
			));
			}
			else{
			return parent::format_response(array(
			'special_collection_id' => $this->id,
			//'image_url' => Util_Url::get_photoimage_url($this->id,$this->image_file_name),
			'banner_img' => $this->pc_banner_img,
			'banner_link' => $this->pc_banner_link,
			'name' => $this->name,
			'introduction' => $this->introduction,
		));
				}
		
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