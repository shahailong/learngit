<?php
/**
 * IDページャ
 * 
 * @author k-kawaguchi
 */
class Util_Pagenation_Id extends Util_Pagenation_Base{

    const DEFAULT_PER_PAGE_COUNT = 20;

    /**
     * 検索条件<br />
     * ※取得条件を随時追加します。
     * 
     * @var Database_Query_Builder_Select 
     */
    private $query = null;
    /**
     * 初期検索時ょ受け
     * 
     * @var Database_Query_Builder_Select 
     */
    private $init_query = null;
    /**
     * 上限件数
     * 
     * @var int 
     */
    private $count = null;
    /**
     * クエリ発行時の実際のLIMIT<br />
     * ※「次ページの最大ID」を得るために、「上限件数+1」件の取得を行います。
     * 
     * @var int 
     */
    private $actual_limit_count = null;
    /**
     * ID名
     * 
     * @var string 
     */
    private $id_name = null;
    /**
     * 最大ID値
     * 
     * @var mixed
     */
    private $max_id = null;
    /**
     * 次ページの最大ID値
     * 
     * @var mixed 
     */
    private $next_max_id = null;
    /**
     * 取得結果
     * 
     * @var array 
     */
    private $result_ary = array();
    /**
     * 最後までページングを行った場合の総件数
     * 
     * @var int 
     */
    private $total_count = null;
    /**
     * 次ページの先頭レコード
     * 
     * @var Model 
     */
    private $last_record = null;
    /**
     * 取得条件凍結フラグ<br />
     * ※TRUEの場合、これ以上、取得条件は変化しません。
     * 
     * @var boolean 
     */
    private $is_query_terminated = false;

    /**
     * コンストラクタ
     * 
     * @param Database_Query_Builder_Select $query 検索条件
     * @throws Exception_Logic
     */
    public function __construct(Database_Query_Builder_Select $query){
        if(is_null($query)){
            throw new Exception_Logic('Illegal argument.');
        }

        $this->query = $query;
        $this->init_query = clone $query;
    }

    /**
     * 上限件数を設定します。
     * 
     * @param type $count
     */
    public function set_count($count){
        if(is_null($count)){
            $count = static::DEFAULT_PER_PAGE_COUNT;
        }
        $this->count = (int)$count;
        $this->actual_limit_count = ($this->count + 1);
    }

    /**
     * IDのカラム名を設定します。<br />
     * ※通常は、id、photo_id、user_id等のカラム名をstringで渡します。<br />
     * ※テーブルのJOIN等を行っている関係で、取得Modelと異なるテーブルのカラム名を指定する場合は以下のように配列を渡して下さい。<br />
     * 配列形式：array(テーブル名, カラム名)
     * 
     * @param mixed $id_name
     */
    public function set_id_name($id_name){
        if(is_null($id_name)){
            return;
        }

        if(is_array($id_name) && (!isset($id_name[0]) || !isset($id_name[1]))){
            throw new Exception_Logic('Illegal argument, the id_name specifie array must has table name and column name. print_r->' . print_r($id_name, true));
        }

        $this->id_name = $id_name;
    }

    /**
     * 最大IDを設定します。
     * 
     * @param mixed $max_id
     * @return void
     */
    public function set_max_id($max_id){
        if(is_null($max_id)){
            return;
        }

        $this->max_id = $max_id;
    }

    /**
     * 次ページの最大IDを取得します。
     * 
     * @return mixed
     */
    public function get_next_max_id(){
        return $this->next_max_id;
    }

    /**
     * 最後までページングを行った場合の総件数を取得します。
     * 
     * @return int
     */
    public function get_total_count(){
        if(!is_null($this->total_count)){
            return (int)$this->total_count;
        }

        $result = DB::select(DB::expr('COUNT(*) AS `total_rows`'))->from(DB::expr('(' .
                $this->init_query->compile()
            . ') AS `counted_results`'))
            ->execute()
            ->as_array()
        ;
        $this->total_count = (isset($result[0]['total_rows']) ? (int)$result[0]['total_rows'] : 0);
        return $this->total_count;
    }

    /**
     * ページ補足情報を取得します。
     * 
     * @param array $need_items 必要項目（省略可能）
     * @return type
     */
    public function get_page_info(array $need_items = array('next_max_id', 'current_count', 'total_count')){
        $return_ary = array();
        $methods = array(
            'next_max_id'=>'get_next_max_id',
            'current_count'=>'get_current_count',
            'total_count'=>'get_total_count'
        );
        foreach($need_items as $item){
            if(isset($methods[$item])){
                $return_ary[$item] = call_user_func(array($this, $methods[$item]));
            }
        }
        return $return_ary;
    }

    /**
     * 1ページ分のModelを配列で取得します。
     * 
     * @param string $class_name
     * @param string $key_column
     * @return array
     * @throws Exception_Logic
     */
    public function get_list($class_name, $key_column = null){
        if($this->result_ary){
            return $this->get_combine_result_array($key_column);
        }

        // Modelクラスのチェック
        static::check_model_class($class_name);
        $this->query = $this->get_last_query($class_name);
        $this->result_ary = $this->query
            ->as_object($class_name)
            ->execute()
            ->as_array()
        ;
        $get_count = count($this->result_ary);
        if($get_count < $this->actual_limit_count){
            return $this->get_combine_result_array($key_column);
        }

        if($get_count > $this->actual_limit_count){
            throw new Exception_Logic('The get_count is over fllow. get_count=' . $get_count);
        }

        $last_index = ($this->actual_limit_count - 1);
        if(!isset($this->result_ary[$last_index]) || !is_subclass_of($this->result_ary[$last_index], 'Model_Base')){
            throw new Exception_Logic('Illegal value for last record.');
        }

        $this->last_record = $this->result_ary[$last_index];
        $this->next_max_id = $this->last_record->get((
            is_null($key_column) 
            ? (
                is_array($this->id_name) 
                ? $this->id_name[1] 
                : $this->id_name
            ) 
            : $key_column
        ));
        unset($this->result_ary[$last_index]);
        return $this->get_combine_result_array($key_column);
    }

    /**
     * 1ページ分の件数を取得します。
     * 
     * @return int
     */
    public function get_current_count(){
        return count($this->result_ary);
    }

    /**
     * 「検索条件」に必要な取得条件を付加して返却します。
     * 
     * @param type $class_name
     * @return type
     */
    public function get_last_query($class_name){
        if($this->is_query_terminated === true){
            return $this->query;
        }

        // 1行多く取得する
        if(!is_null($this->actual_limit_count)){
            $this->query->limit($this->actual_limit_count);
        }
        // 並び順はIDの降順で固定
        if(!is_null($this->id_name)){
            $id_name = (
                is_array($this->id_name) 
                ? $this->id_name[0] . '.' . $this->id_name[1] 
                : $class_name::table() . '.' . $this->id_name
            );
            $this->query->order_by($id_name, 'DESC');
            // 最新ID
            if(!is_null($this->max_id)){
                $this->query->where($id_name, '<=', $this->max_id);
            }
        }
        $this->is_query_terminated = true;
        return $this->query;
    }

    /**
     * 取得結果を「array(ID値1=>Model1, ID値2=>Model2, …)」の構造に組み替えます。
     * 
     * @param string $key_column IDのカラム名（省略可能）
     * @return array
     * @throws Exception_Logic
     */
    private function get_combine_result_array($key_column = null){
        if(!is_array($this->result_ary) || !isset($this->result_ary[0])){
            return array();
        }

        $combine_ary = array();
        $id_name = (is_null($key_column) ? $this->id_name : $key_column);
        if(!is_string($id_name)){
            throw new Exception_Logic("'key_column' is not nullable argument for array specified id_name.");
        }

        foreach($this->result_ary as $record){
            $id = $record->get($id_name);
            $combine_ary[$id] = $record;
        }
        $this->result_ary = $combine_ary;
        return $this->result_ary;
    }

    /**
     * Modelのクラス名の妥当性を確認します。
     * 
     * @param string $class_name
     * @throws Exception_Logic
     */
    private static function check_model_class($class_name){
        if(!class_exists($class_name)){
            throw new Exception_Logic('Ilegal argument. ->' . $class_name);
        }

        if(!is_subclass_of($class_name, 'Model_Base')){
            throw new Exception_Logic("The suplied class name '${class_name}' is not sub class of Model_Base.");
        }
    }

}