<?php
/**
 * おもフォトランキング集計
 *
 * @author a-kido
 */
class Model_Omophoto_Ranking_Term extends Model_Base{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
		'id',
		'type',
		'target_at',
		'ranking_id',
		'omophoto_id',
		'total_points',
		'delete_flg',
		'delete_at',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'omophoto_ranking_terms';

	protected static $_max_id = NULL;

	protected static $_target_at = NULL;

	const TYPE_DAILY_RANKING = 1;
	const TYPE_WEEKLY_RANKING = 2;
	const TYPE_MONTHLY_RANKING = 3;

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
	 * おもフォト一覧(人気順)
	 *
	 * @param int $need_num
	 * @param int $type
	 * @param int $target_at
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $official_flg
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function list_data($need_num, $type=null, $target_at=null, $max_id=null, $user_id=null, $official_flg=null, $no_cache=null){
		if(empty($target_at)){
			$max_target_at = DB::select(DB::expr('max(target_at) as target_at'))
										->from(static::table())
										->where('type','=',$type)
										->execute();

				if(!$max_target_at[0]["target_at"]){
					self::$_target_at = null;
					return array();
				}
				$target_at = $max_target_at[0]["target_at"];
		}

		// カレントページのデータ取得
		$query = DB::select('rnk.ranking_id',DB::expr('SUM(oa.points) as points'),'o.id','o.photo_id','o.user_id','o.image_file_name','o.title','o.created_at','o.image_file_size','o.image_width','o.image_height');
		$query->from(array(static::table(),'rnk'));
		$query->join(array(Model_Omophoto::table(),'o'), 'INNER');
		$query->on('o.id', '=','rnk.omophoto_id');
		$query->join(array(Model_Omophoto_Appraisal::table(),'oa'), 'LEFT');
		$query->on('oa.omophoto_id', '=','rnk.omophoto_id');
		$query->join(array(Model_User::table(),'u'), 'LEFT');
		$query->on('u.id', '=','o.user_id');
		if(!empty($user_id)){
			$query->where(array('o.user_id'=>$user_id));
		}
		if(!empty($official_flg)){
			$query->where(array('u.official_flg'=>$official_flg));
		}
		$query->where('rnk.target_at', '=', $target_at);
		$query->where(array('o.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where(array('rnk.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where('rnk.type','=',$type);
		$query->limit($need_num);
		$query->order_by('rnk.ranking_id','asc');
		$query->as_object('Model_Omophoto_Ranking_Term');
		$query->group_by('rnk.ranking_id');

		if(isset($max_id) && !empty($max_id) && ctype_digit((string)$max_id)){
			$query->where('rnk.ranking_id', '>', $max_id);
		}

		if($no_cache == 1){
			$results = $query->execute();
		}else{
			$cache_key = "db." .self::$_table_name .".n{$need_num}_t{$type}_t" .substr(mb_ereg_replace('[^0-9]', '', $target_at),0,8) ."_m{$max_id}_u{$user_id}_o{$official_flg}";
			$results = $query->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュを利用
		}

		$respons = array();

		self::$_target_at = $target_at;

		if(count($results) == 0){
			return $respons;
		}

		foreach ($results as $result){
			self::$_max_id = $result->ranking_id;
			$format_data = $result->format_response();
			$respons[] = $format_data;
		}

		return  $respons;
	}

	/**
	 * おもフォト一覧（人気順）付加データを付けて返すメソッド
	 *
	 * @param int $need_num
	 * @param int $target_at
	 * @param int $max_id
	 * @param int $type
	 * @param int $user_id
	 * @param int $official_flg
	 * @param int $no_cache
	 *
	 * @return array
	 */
	public static function omophoto_ranking($need_num, $target_at=null, $max_id=null, $type=null, $user_id=null, $official_flg=null, $no_cache=null){
		$respons['omophotos'] = self::list_data($need_num, $type, $target_at, $max_id, $user_id, $official_flg, $no_cache);
		$respons['target_at'] = !empty(self::$_target_at) ? date_format(new DateTime(self::$_target_at),'Y-m-d') : null ;
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
}