<?php
/**
 * Modelの基底クラスです。
 *
 * @author k-kawaguchi
 * @package app
 * @extends \Orm\Model
 */
abstract class Model_Base extends \Orm\Model{

	const DELETE_FLG_FALSE = 0;

	private static $executor_user_id = null;
	private static $is_use_query_cache = false;

	protected static $_observers = array(
		'Orm\Observer_CreatedAt' => array(
			'events'=>array('before_insert'),
			'mysql_timestamp' => true
		),
		'Orm\Observer_UpdatedAt' => array(
			'events'=>array('before_save'),
			'mysql_timestamp' => true
		),
		'Observer_Logging' => array(
			'events' => array('after_load', 'after_delete', 'after_save')
		)
	);

	/**
	 * プライマリキーの名称と値を配列で返却します。
	 *
	 * @return array
	 */
	public function get_pk_values(){
		$return_ary = array();
		$class = get_class($this);
		foreach($class::$_primary_key as $pk_name){
			$return_ary[$pk_name] = $this->get($pk_name);
		}
		return $return_ary;
	}

	/**
	 * レコードをロックします。
	 *
	 * @return Model_Base
	 */
	public function lock_record(){
		$model_class = get_class($this);
		$table = $model_class::table();
		$query = DB::select("${table}.*")->from($table);
		foreach($this->get_pk_values() as $name=>$value){
			$query->where($name, '=', $value);
		}
		$query->limit(1);
		$results = DB::query($query->compile() . ' FOR UPDATE')
			->as_object($model_class)
			->execute()
			->as_array()
		;
		if(!isset($results[0])){
			return null;
		}

		return $results[0];
	}

	/**
	 * レスポンス仕様を定義したい場合は、これをオーバーライドし、引数に内容を指定して下さい。
	 *
	 * @param array $data
	 * @return array
	 */
	public function format_response(array $data = array()){
		return ($data ? $data : $this->to_array());
	}

	/**
	 * 非公開にしたいパラメータをNULLにして削除してください。
	 *
	 * @param Model_Base $model
	 * @return \Model_Base
	 */
	public function mask_unvisible_info(){
		return $this;
	}

	/**
	 * stockしているModelデータを全て削除する。
	 */
	public static function flush_all_stocks(){
		Model_User::clear_stock();
		//Model_Image_Info::clear_stock();
		//Model_User_Relation::clear_stock();
		//Model_User_Photo_Comment::clear_stock();
		//Model_User_Photo_Stamp::clear_stock();
		//Model_User_Setting::clear_stock();
		//Model_User_Photo::clear_stock();
		//Model_User_Group::clear_stock();
		self::$executor_user_id = null;
	}

	/**
	 * 実行者のユーザIDを取得します。
	 *
	 * @return int
	 */
	protected static function get_executor_user_id(){
		return self::$executor_user_id;
	}

	/**
	 * 実行者のユーザIDを設定します。<br />
	 * ※別のユーザIDへ上書きを試みた場合、Exception_Logicが発生します。
	 *
	 * @param int $user_id
	 * @throws Exception_Logic
	 */
	public static function set_executor_user_id($user_id){
		// 上書きを許可しない
		if(!is_null(self::$executor_user_id)
			&& self::$executor_user_id != $user_id){
			throw new Exception_Logic("Could not rewrite executor_user_id '" . self::$executor_user_id . "' to suplied user_id '${user_id}'.");
		}

		self::$executor_user_id = $user_id;
	}

	/**
	 * 「SHOW TABLES」クエリを用いて、現在選択されているデータベースに存在するテーブル名の一覧を取得します。
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function show_tables(){
		$results = \DB::query('SELECT DATABASE() AS db_name', DB::SELECT)->execute()->as_array();
		if(!isset($results[0]['db_name'])){
			throw new Exception("Could not select 'DATABASE()'.");
		}

		$database_name = $results[0]['db_name'];
		$key_name = "Tables_in_${database_name}";
		$tables = array();
		$records = \DB::query('SHOW TABLES', DB::SELECT)->execute()->as_array();
		foreach($records as $record){
			if(!isset($record[$key_name])){
				continue;
			}

			$tables[] = $record[$key_name];
		}
		return $tables;
	}

	/**
	 * Modelオブジェクトの配列をレスポンス仕様に従い組み替えます。
	 *
	 * @param array $ary Modelオブジェクトの配列
	 * @param array $merge マージしたい配列（任意項目、可変長）
	 * @return array
	 * @throws Exception
	 */
	public static function format_array(){
		// 呼び出しクラスのチェック
		$called_class = get_called_class();
		if($called_class == 'Model_Base'){
			throw new Exception_Logic("Called class 'Model_Base' can not has response format.");
		}

		// ROOT要素の決定（未定義である場合は、テーブル名を使う）
		$root_element = (
			property_exists($called_class, 'response_root_element')
			? $called_class::$response_root_element
			: $called_class::$_table_name
		);
		if(!$root_element){
			throw new Exception_Logic('Could not decide name of root element.');
		}

		// 引数チェック
		$args = func_get_args();
		if(!isset($args[0]) || !is_array($args[0])){
			throw new Exception_Logic('Illegal argument for 1. all arguments -> ' . print_r($args, true));
		}

		$root_ary = $args[0];
		unset($args[0]);
		// 追加情報をマージする
		return static::format_with_merge_info(
			// 内部構造とROOT要素を関連付ける
			array($root_element=>static::format_element_contents($root_ary)),
			$args
		);
	}

	/**
	 * Modelオブジェクトの配列を受け取り、レスポンス仕様の内部構造を返却します。
	 *
	 * @param array $moodel_ary
	 * @param string $called_class
	 * @return array
	 * @throws Exception_Logic
	 */
	public static function format_element_contents(array $moodel_ary, $called_class = null){
		if(!$moodel_ary){
			return array();
		}

		$called_class = (is_null($called_class) ? get_called_class() : $called_class);
		if(!class_exists($called_class)){
			throw new Exception_Logic('Illegal argument. ->' . $called_class);
		}

		// フォーマット
		$data = array();
		foreach($moodel_ary as $instance){
			// NULLはスキップする
			if(is_null($instance)){
				continue;
			}

			if(!($instance instanceof $called_class)){
				throw new Exception_Logic(
					"The contents of array must be instance of '${called_class}'. instance=" .
					(is_object($instance) ? 'get_class(' . get_class($instance) . ')' : 'echo as string(' . $instance . ')')
				);
			}

			$data[] = $instance->format_response();
		}
		return $data;
	}

	/**
	 * Modelオブジェクトを受け取り、レスポンス仕様を単数要素としてフォーマットします。
	 *
	 * @param Model_Base $instance
	 * @param array... 追加情報（省略可能、可変長）
	 * @return array
	 * @throws Exception_Logic
	 */
	public static function format_single_element(){
		$args = func_get_args();
		if(!array_key_exists(0, $args)){
			throw new Exception_Logic('Argument 1 is required.');
		}

		// 呼び出しクラスのチェック
		$called_class = get_called_class();
		$instance = $args[0];
		unset($args[0]);
		return static::format_with_merge_info(
			array($called_class::get_single_element_key()=>$called_class::format_single_element_content($instance)),
			$args
		);
	}


	/**
	 * Modelオブジェクトを受け取り、レスポンス仕様・単数要素の内部構造を返却します。
	 *
	 * @param Model_Base $model
	 * @return array
	 * @throws Exception_Logic
	 */
	public static function format_single_element_content(Model_Base $model = null){
		if(!$model){
			return null;
		}

		if(get_class($model) != get_called_class()){
			throw new Exception_Logic('Could not format unmatched instance(' . get_class($model) . '), called by ' . get_called_class() . '.');
		}

		return $model->format_response();
	}

	/**
	 * 単数要素の名称を返却します。
	 *
	 * @return string
	 * @throws Exception_Logic
	 */
	public static function get_single_element_key(){
		// 呼び出しクラスのチェック
		$called_class = get_called_class();
		return Inflector::singularize($called_class::get_pulural_element_key());
	}

	/**
	 * 複数要素の名称を返却します。
	 *
	 * @return string
	 * @throws Exception_Logic
	 */
	public static function get_pulural_element_key(){
		// 呼び出しクラスのチェック
		$called_class = get_called_class();
		// ROOT要素の決定（未定義である場合は、テーブル名を使う）
		$pulural_element = (
			property_exists($called_class, 'response_root_element')
			? $called_class::$response_root_element
			: $called_class::$_table_name
		);
		if(!$pulural_element){
			throw new Exception_Logic('Could not decide name of root element.');
		}

		return $pulural_element;
	}

	/**
	 * レスポンス仕様に対して追加情報をマージします。
	 *
	 * @param array $root_elements
	 * @param array $merges
	 * @return array
	 * @throws Exception_Logic
	 */
	private static function format_with_merge_info(array $root_elements, array $merges = array()){
		foreach($merges as $merge){
			if(!is_array($merge)){
				throw new Exception_Logic(
					'Arguements for the merge only can be an array. merge-> ' .
					(is_object($merge) ? 'get_class(' . get_class($merge) . ')' : 'echo as string(' . $merge . ')')
				);
			}

			$root_elements = array_merge($root_elements, $merge);
		}
		return $root_elements;
	}

	/**
	 * Modelの格納された配列とカラム名を受け取り、カラム値を数値添字配列に格納して返却します。<br />
	 * ※重複は排除されます。<br />
	 * ※空の値は除外されます。
	 *
	 * @param array $ary
	 * @param string $column_name
	 * @return array array(0=>カラム値1, 1=>カラム値2, 2=>カラム値3, …)
	 */
	public static function extract_array_values_by_column_name(array $ary, $column_name){
		$combined_ary = static::array_combine_per_column_name($ary, $column_name);
		if(!$combined_ary){
			return array();
		}

		return array_keys($combined_ary);
	}

	/**
	 * Modelの格納された配列とカラム名を受け取り、カラム値毎に関連付いた一次元配列に組み替えて返却します。<br />
	 * ※受け取った配列にカラム値の重複がある場合、返却値は後方の要素で上書きされ重複が排除されます。<br />
	 * ※受け取った配列にカラム値の重複がある場合、並び順については、前方のカラム値に従います。
	 *
	 * @param array $ary
	 * @param string $column_name
	 * @return array array(カラム名の値=>Modelのインスタンス)
	 * @throws Exception_Logic
	 */
	public static function array_combine_per_column_name(array $ary, $column_name){
		$return_ary = array();
		foreach($ary as $model){
			// チェック
			static::check_array_key_column_value($column_name, $model);
			$value = $model->get($column_name);
			if(!$value){
				continue;
			}

			$return_ary[$model->get($column_name)] = $model;
		}
		return $return_ary;
	}

	/**
	 * Modelの格納された配列とカラム名を受け取り、カラム値毎に関連付いた多次元配列に組み替えて返却します。<br />
	 * ※受け取った配列にカラム値の重複がある場合、該当カラムには複数のModelが格納されます。
	 *
	 * @param array $ary
	 * @param string $column_name
	 * @return array array(カラム名の値=>array(Modelのインスタンス1, Modelのインスタンス2, …), array(…), … )
	 * @throws Exception_Logic
	 */
	public static function array_multi_combine_per_column_name(array $ary, $column_name){
		$return_ary = array();
		foreach($ary as $model){
			// チェック
			static::check_array_key_column_value($column_name, $model);
			$value = $model->get($column_name);
			if(!$value){
				continue;
			}

			if(!isset($return_ary[$value])){
				$return_ary[$value] = array();
			}
			$return_ary[$value][] = $model;
		}
		return $return_ary;
	}

	/**
	 * 「Model::query」をオーバーライドし、Queryに共通の検索条件を追加して返却します。<br />
	 * ※共通の検索条件<br />
	 * 1. 削除フラグ
	 *
	 * @param array $options
	 * @return Query
	 */
	public static function query($options = array()){
		$query = parent::query($options);
		if(!($query instanceof Orm\Query)){
			return $query;
		}

//		if(!self::$is_use_query_cache){
			// キャッシュを無効にする
			$query->from_cache(false);
//		}
		$query->where(array('delete_flg'=>static::DELETE_FLG_FALSE));
		return $query;
	}

	public static function query_from_cache($bool){
		static::$is_use_query_cache = (boolean)$bool;
	}

	/**
	 * デフォルトのQueryを取得します。
	 *
	 * @param array $options
	 * @return Query
	 */
	public static function query_default($options = array()){
		return parent::query($options);
	}

	/**
	 * クラス名、メソッド名、引数を受け取り、静的メソッドを実行します。<br />
	 *
	 * @param type $class_name クラス名
	 * @param type $method_name メソッド名
	 * @param array $arguments メソッドへの引数（配列）
	 * @return type
	 * @throws type
	 * @throws Exception
	 */
	public static function static_method_as_transaction($class_name, $method_name, array $arguments = array()){
		if(!$class_name){
			throw new Exception_Logic('Empty class name is not callable.');
		}

		if(!class_exists($class_name)){
			throw new Exception_Logic("Suplied class name '${class_name}' is not found.");
		}

		if(!method_exists($class_name, $method_name)){
			throw new Exception_Logic("Suplied method name '${class_name}'::'${method_name}' is not found.");
		}

		$ret = null;
		$called_class = get_called_class();
		// debug環境用に開始時刻を記録する
		$start_time = microtime(true);
		try{
			// 開始
			DB::start_transaction();
			Log::debug('[sql:'.$called_class.']Start transaction');
			// 実行
			$ret = forward_static_call_array(array($class_name, $method_name), $arguments);
			Log::debug('[sql:'.$called_class.']Commit transaction | exectime : '.
				(isset($start_time) ? ceil((microtime(true) - $start_time) * 1000) : '')
			);
			// 確定
			DB::commit_transaction();
		}catch(Exception $exc){
			// 取り消し
			DB::rollback_transaction();
			Log::debug('[sql:'.$called_class.' Rollback transaction | caused by: ' . $exc->getMessage() .
			' | trace:' . "\n" . $exc->getTraceAsString());
			throw $exc;
		}

		return $ret;
	}

	/**
	 * Modelの配列を受け取り、一括更新を行います。
	 *
	 * @param array $models
	 * @return void
	 * @throws Exception
	 */
	public static function save_all(array $models){
		if(!$models || !is_array($models)){
			return;
		}

		$called_class = get_called_class();
		// 配列を0からの連番で振りなおす
		$models = array_values($models);
		try{
			Log::debug('[sql:'.$called_class.'] Starting ' . __METHOD__ . '.');
			$model_count = count($models);
			for ($i = 0; $i < $model_count; $i++){
				$model = $models[$i];
				if(!($model instanceof Model_Base)){
					throw new Exception_Logic("Invalid instance detected. info = " . print_r($model, true));
				}

				$model->save();
			}
			Log::debug('[sql:'.$called_class.'] ' . __METHOD__ . ' is successfly finished.');
		}catch(Exception $exc){
			Log::debug('[sql:'.$called_class.']!!!Failed!!! '.DB::last_query());
			Log::debug('[sql:'.$called_class .  ']' .$exc->getMessage());
			throw $exc;
		}
	}

	/**
	 * インスタンス、メソッド名、引数を受け取り、インスタンス・メソッドをトランザクション処理として実行します。<br />
	 *
	 * @param object $instance
	 * @param string $method_name
	 * @param array $arguments
	 * @return mixed
	 */
	public static function instance_method_as_transaction($instance, $method_name, array $arguments = array()){
		return static::static_method_as_transaction(
			'Model_Base',
			'call_instance_method',
			array($instance, $method_name, $arguments)
		);
	}

	/**
	 * インスタンス・メソッドを実行します。<br />
	 * ※instance_method_as_transactionからstatic_method_as_transactionを介して呼び出され、トランザクション処理として実行されます。
	 *
	 * @param object $instance
	 * @param string $method_name
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 */
	private static function call_instance_method($instance, $method_name, array $arguments = array()){
		if(!$instance){
			throw new Exception_Logic('Empty instance is not callable.');
		}

		if(!is_object($instance)){
			throw new Exception_Logic("Value of none object is not callable. instance=" . print_r($instance, true));
		}

		if(!method_exists($instance, $method_name)){
			throw new Exception_Logic("Suplied method " . get_class($instance) . "->${method_name} is not callable.");
		}

		return call_user_func_array(array($instance, $method_name), $arguments);
	}

	/**
	 * Modelの配列を受け取り、トランザクション処理として一括更新を行います。
	 *
	 * @param array $models
	 * @return mixed
	 */
	public static function save_all_as_transaction(array $models){
		return static::static_method_as_transaction('Model_Base', 'save_all', array($models));
	}

	/**
	 * 空文字をNULLへ変換します。
	 *
	 * @param string $mixed
	 * @return null|string
	 */
	public static function blank_to_null($mixed){
		if(!is_scalar($mixed)){
			return $mixed;
		}

		return ($mixed === '' ? null : $mixed);
	}

	/**
	 * 与えられた引数が、Modelかどうか判定します。
	 *
	 * @param mixed $model_class
	 * @return boolean
	 */
	public static function is_valid_model_class($model_class){
		return is_subclass_of($model_class, 'Model_Base');
	}

	/**
	 * Modelインスタンスとカラム名を受け取り、配列の添字として使用できるかどうかチェックします。<br />
	 * ※当該メソッドは、チェック不通過の場合、Exception_Logicを投棄します。
	 *
	 * @param string $name
	 * @param Model_Base $model
	 * @throws Exception_Logic
	 */
	private static function check_array_key_column_value($name, Model_Base $model){
		if(!$model){
			throw new Exception_Logic('Model_Base could not be empty.');
		}

		if(!static::is_valid_model_class($model)){
			throw new Exception_Logic(
				'Illegal instance. instanceof=' .
				(is_object($model) ? get_class($model) : $model)
			);
		}

		$value = $model->get($name);
		if(is_null($value)){
			// NULLはスキップ
			return;
		}

		if(!is_scalar($value)){
			throw new Exception_Logic("The value '${value}' for the array key only must be scalar.");
		}
	}
}