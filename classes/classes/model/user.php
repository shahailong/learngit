<?php
/**
 * ユーザーマスタ
 * @author a-kido
 */
class Model_User extends Model_Base{

	const LOGIN_TYPE_ORIGINAL = 1;
	const LOGIN_TYPE_FACEBOOK = 2;
	const LOGIN_TYPE_TWITTER  = 3;
	const RANK_ID_DEFAULT = 1;
	const PUSH_FLG_FALSE = 0;
	const PUSH_FLG_TRUE  = 1;
	const TEST_FLG_FALSE = 0;
	const TEST_FLG_TRUE  = 1;
	const OFFICIAL_FLG_DEFAULT = 0;
	const OFFICIAL_FLG_GEININ = 1;

	protected static $_primary_key = array('id');

	protected static $_properties = array(
		'id',
		'nickname',
		'login_type',
		'rank_id',
		'push_flg',
		'test_flg',
		'official_flg',
		'delete_flg',
		'delete_at',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'users';

	private static $stock_models = array();

	private static $is_auto_filter = true;

	private $relation_type_ary = null;

	/**
	 * 戻り値
	 *
	 * @param array $data
	 * @return array
	 */
	public function format_response(array $data = array()){

		$total_points = Model_Omophoto_Appraisal::get_total_points_by_user($this->id);

		if($this->id == self::get_executor_user_id()){
			$rankup_required_points = Model_Rank::get_rankup_required_points($total_points,$this->rank_id);
		}else{
			$rankup_required_points = 0;
		}

		return parent::format_response(array(
			'user_id' => $this->id,
			'nickname' => $this->nickname,
			'image_url' => Util_Url::get_profileimage_url($this->id, Model_User_Profileimage::get_image_file_name($this->id)),
			'login_type' => $this->login_type,
			'push_flg' => $this->push_flg,
			'test_flg' => $this->test_flg,
			'official_flg' => $this->official_flg,
			'total_points' => $total_points,
			'rank_id' => $this->rank_id,
			'rank_name' => Model_Rank::get_name($this->rank_id),
			'rankup_required_points' => $rankup_required_points,
		));
	}

	/**
	 * 非公開のプロフィール情報をマスクします。<br />
	 *
	 * @param Model_User $user_model
	 * @return Model_User
	 */
	public function mask_unvisible_info(){
		//$this->login_id = null;
		$this->login_type = null;
		$this->push_flg = null;
		$this->test_flg = null;
		return $this;
	}

	public static function enable_auto_filter(){
		static::$is_auto_filter = true;
	}

	public static function disable_auto_filter(){
		static::$is_auto_filter = false;
	}

	/**
	 * 追加情報を付加したり特定の項目をマスクしたりします<br />
	 *
	 * @param type $excutor_user_id
	 * @param Model_User $user_models
	 *
	 * @return array
	 * @throws Exception_Logic
	 */
	public static function filter_response_records($excutor_user_id, array $user_models){
		if(!$user_models){
			return $user_models;
		}

		$return_ary = array();

		foreach($user_models as $user_model){
			if(!($user_model instanceof Model_User)){
				throw new Exception_Logic('Illegal argument.');
			}

			if((string)$excutor_user_id != (string)$user_model->id){
				// プライバシーに関わる情報をマスクする
				$user_model->mask_unvisible_info();
			}
			$return_ary[] = $user_model;
		}
		return $return_ary;
	}

	 /**
	 * 追加情報を付加したり特定の項目をマスクしたりします(一次配列)<br />
	 *
	 * @param Model_User $user_models
	 *
	 * @return \Model_User|array
	 * @throws Exception_Logic
	 */
	public static function filter_single_response_record($user_models){

		$excutor_user_id = self::get_executor_user_id();

		if(!$user_models){
			return $user_models;
		}

		foreach($user_models as $user_model){
			if(!($user_model instanceof Model_User)){
				throw new Exception_Logic('Illegal argument.');
			}

			if((string)$excutor_user_id != (string)$user_model->id){
				// プライバシーに関わる情報をマスクする
				$user_model->mask_unvisible_info();
			}
		}
		return $user_model;
	}

	/**
	 *
	 * @param int $id
	 * @return Model_User
	 */
	public static function get_by_id($id){
		return static::find($id);
	}

	/**
	 *
	 * @param array $ids
	 * @return type
	 */
	public static function get_by_ids(array $ids){
		if(!$ids){
			return array();
		}

		$models = static::query()->where('id', 'in', $ids)->get();
		if(!is_array($models)){
			return array();
		}

		return $models;
	}

	/**
	 *
	 * @param strig $nickname
	 * @param int $login_type
	 *
	 * @return Model_User
	 */
	public static function new_record($nickname, $login_type){
		$record = static::forge();
		$record->nickname   = $nickname;
		$record->login_type = $login_type;
		$record->rank_id	= static::RANK_ID_DEFAULT; // マスターから初期ランクを取得するようにのちのち修正すること
		$record->push_flg = static::PUSH_FLG_TRUE;
		$record->test_flg = static::TEST_FLG_FALSE;
		$record->official_flg = static::OFFICIAL_FLG_DEFAULT;
		$record->delete_flg = static::DELETE_FLG_FALSE;

		return $record;
	}

	public static function query($options = array()){
		$query = parent::query($options);
		if(!static::$is_auto_filter){
			return $query;
		}

		//$query->where('status', static::STATUS_ENABLE);
		return $query;
	}

	public static function add_stock_by_id(Model_User $stock_model){
		static::$stock_models[$stock_model->id] = $stock_model;
	}

	public static function add_all_stock_by_id(array $stock_models){
		foreach($stock_models as $stock_model){
			static::add_stock_by_id($stock_model);
		}
	}

	public static function get_stock_by_id($id, $delete_flg = false){
		if(!isset(static::$stock_models[$id])){
			return null;
		}

		$model = static::$stock_models[$id];
		if($delete_flg === true){
			unset(static::$stock_models[$id]);
		}
		return $model;
	}

	public static function get_all_stock($clear_flg = false){
		$models = static::$stock_models;
		if($clear_flg === true){
			static::clear_stock();
		}
		return $models;
	}

	public static function clear_stock(){
		static::$stock_models = array();
	}

	/**
	* フォト・おもフォトなどの付加情報として取得するユーザー情報
	*
	* ユーザーが退会した場合、投稿したフォト・おもフォトも削除されるが、
	* フォト・おもフォト一覧がキャッシュを使用するため、削除されたユーザー情報も取得できる必要がある。
	*
	* @param int $id
	*
	* @return Model_User
	*/
	public static function get_format_user_data($id){

		$user_data = Model_User::query_default()
				->select('id','nickname','login_type','rank_id','push_flg','test_flg','official_flg')
				->where('id',$id)
				->get_one();

		if(count($user_data) == 0){
			return NULL;
		}

		$filter_data = static::filter_single_response_record(array($user_data));

		$format_data = $filter_data->format_response();
		return $format_data;
	}

	 /**
	 * ユーザーマスターのロック
	 *
	 * @param int $user_id
	 *
	 * @return Model_User
	 */
	public static function lock_user_data($user_id = NULL){
		$sql = "SELECT * FROM users WHERE id = :id AND delete_flg = :delete_flg FOR UPDATE";
		$result = DB::query($sql)
					->parameters(array('id'=> $user_id, 'delete_flg'=> static::DELETE_FLG_FALSE))
					->execute();
		return $result;
	}

}