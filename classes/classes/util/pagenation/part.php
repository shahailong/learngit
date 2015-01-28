<?php
/**
 * 部分ページャ<br />
 * ※Model配列を複数のページャ単位に分割します。<br />
 * ※当該ページャ機能においては、各ページャ単位は、先頭ページのみが抽出されます。<br />
 * ※1部分単体で用いる場合「get_page()」によって結果を取得し、複数単位で用いる場合は「get_unit_page(単位ID)」によって結果を取得します。<br />
 * データ構造: <br />
 * 親インスタンス{{単位ID1=>子インスタンス1{基底ID1=>Model1, 基底ID2=>Model2, …}, 単位ID2=>子インスタンス2{基底ID1=>Model1, 基底ID2=>Model2, …}, …}}
 * 
 * @author k-kawaguchi
 */
class Util_Pagenation_Part extends Util_Pagenation_Base{

    /**
     * 次ページの最大ID値
     * 
     * @var mixed 
     */
    private $next_max_id = null;
    /**
     * 最終ページまでページングした場合の総件数
     * 
     * @var int 
     */
    private $total_count = 0;
    /**
     * 該当ページのデータ
     * 
     * @var array 
     */
    private $current_models = null;
    /**
     * 該当ページにおける次ページの先頭レコード
     * 
     * @var Model 
     */
    private $current_next_record = null;
    /**
     * 基底クエリ
     * 
     * @var Database_Query_Builder_Select 
     */
    private $base_query = null;
    /**
     * 対象データ
     * 
     * @var array 
     */
    private $models = array();
    /**
     * 単位ID値のリスト
     * 
     * @var array 
     */
    private $unit_id_values = array();
    /**
     * Model名
     * 
     * @var string 
     */
    private $model_name = null;
    /**
     * 1単位毎の件数
     * 
     * @var int 
     */
    private $count = null;
    /**
     * 単位ID名
     * 
     * @var string 
     */
    private $unit_id_name = null;
    /**
     * 基底ID名
     * 
     * @var string 
     */
    private $id_name = null;
    /**
     * 分割済みページャ・インスタンスの配列
     * 
     * @var array 
     */
    private $page_infos = null;
    /**
     * 分割済みModel配列
     * 
     * @var array 
     */
    private $parsed_models = null;

    /**
     * コンストラクタ
     * 
     * @param array $models 対象データ（Modelの配列）
     * @param Database_Query_Builder_Select $base_query 対象テーブルにおける基底クエリ（総件数を得る際に使用します）
     */
    public function __construct(array $models, Database_Query_Builder_Select $base_query = null){
        $this->models = $models;
        $this->base_query = $base_query;
    }

    /**
     * 対象データのModel名称を設定します。
     * 
     * @param type $model_name
     */
    public function set_model_name($model_name){
        $this->model_name = $model_name;
    }

    /**
     * 単位IDの名称を設定します。
     * 
     * @param string $unit_id_name
     */
    public function set_unit_id_name($unit_id_name){
        $this->unit_id_name = $unit_id_name;
    }

    /**
     * 基底IDの名称を設定します。
     * 
     * @param string $id_name
     */
    public function set_id_name($id_name){
        $this->id_name = $id_name;
    }

    /**
     * 1単位毎の件数を設定します。
     * 
     * @param int $count
     * @throws Exception_Logic
     */
    public function set_count($count){
        if((int)$count < 0){
            throw new Exception_Logic("The argument 'count' must be positive number, but '${count}' given.");
        }

        $this->count = (int)$count;
    }

    /**
     * 単位IDを指定して、ページ補足情報を取得します。
     * 
     * @param mixed $unit_id_value 単位ID値
     * @param array $need_items 必要項目（省略可能）
     * @return array
     */
    public function get_unit_page_info($unit_id_value, array $need_items = array()){
        if(is_null($this->page_infos)){
            $this->parse();
        }
        if(!isset($this->page_infos[$unit_id_value])){
            return static::empty_page_info();
        }

        return $this->page_infos[$unit_id_value]->get_page_info($need_items);
    }

    /**
     * ページ補足情報を取得します。
     * 
     * @param array $need_items 必要項目（省略可能）
     * @return array
     */
    public function get_page_info(array $need_items = array('next_max_id', 'current_count', 'total_count')){
        if(is_null($this->current_models)){
            $this->parse_current_records();
        }
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
     * 単位IDを指定して、分割単位を取得します。
     * 
     * @param mixed $unit_id_value 単位ID値
     * @return array
     */
    public function get_unit_list($unit_id_value){
        if(is_null($this->page_infos)){
            $this->parse();
        }
        if(!isset($this->page_infos[$unit_id_value])){
            return array();
        }

        return $this->page_infos[$unit_id_value]->get_list();
    }

    /**
     * 分割単位を取得します。
     * 
     * @return array
     */
    public function get_list(){
        if(is_null($this->current_models)){
            $this->parse_current_records();
        }
        return $this->current_models;
    }

    /**
     * 単位IDを指定して、ページャ単位を取得します。
     * 
     * @param mixed $unit_id_value 単位ID値
     * @return array
     */
    public function get_unit_page($unit_id_value){
        if(is_null($this->page_infos)){
            $this->parse();
        }
        if(!isset($this->page_infos[$unit_id_value])){
            return $this->get_empty_page();
        }

        return $this->page_infos[$unit_id_value]->get_page();
    }

    /**
     * ページャ単位を取得します。
     * 
     * @return array
     */
    public function get_page(){
        if(is_null($this->model_name)){
            return $this->get_page_info();
        }

        $model_name = $this->model_name;
        return $model_name::format_array($this->get_list(), $this->get_page_info());
    }

    /**
     * ページャ単位の要素名を返却します。
     * 
     * @return string
     */
    public function get_pager_name(){
        if(is_null($this->model_name)){
            $this->parse();
        }
        $model_name = $this->model_name;
        return $model_name::get_single_element_key() . '_pager';
    }

    /**
     * 該当単位を最終ページまでページングした場合の総件数を返却します。
     * 
     * @return int
     */
    public function get_total_count(){
        return (int)$this->total_count;
    }

    /**
     * 「対象データ」を分割することで得られた、該当単位の総数を返却します。
     * 
     * @return int
     */
    public function get_current_count(){
        if(!is_array($this->current_models)){
            return 0;
        }

        return count($this->current_models);
    }

    /**
     * 分割単位の次ページの最大IDを返却します。
     * 
     * @return mixed
     */
    public function get_next_max_id(){
        return $this->next_max_id;
    }

    // TODO 可能であれば、privateにしたい
    /**
     * 1単位毎の総件数を設定します。
     * 
     * @param int $total_count
     */
    public function set_total_count($total_count){
        $this->total_count = $total_count;
    }

    /**
     * 空のページ補足情報を取得します。
     * 
     * @return array
     */
    private static function empty_page_info(){
        return array(
            'next_max_id'=>null,
            'current_count'=>0,
            'total_count'=>0
        );
    }

    /**
     * 空のページャ単位を生成します。<br />
     * ※対象件数が0件のページャ単位です。
     * 
     * @return array
     */
    private function get_empty_page(){
        $page_info = static::empty_page_info();
        if(is_null($this->model_name)){
            return $page_info;
        }

        $model_name = $this->model_name;
        return $model_name::format_array(array(), $this->get_page_info());
    }

    /**
     * 分割単位を解析します。
     * 
     * @return void
     * @throws Exception_Logic
     */
    private function parse_current_records(){
        $this->current_models = array();
        $actual_get_count = ($this->count + 1);
        $slices = array_slice($this->models, 0, $actual_get_count);
        $get_count = count($slices);
        if($get_count < $actual_get_count){
            $this->current_models = $slices;
            return;
        }

        if($get_count > $actual_get_count){
            throw new Exception_Logic('The get_count is over fllow. get_count=' . $get_count);
        }

        $last_index = ($actual_get_count - 1);
        if(!isset($slices[$last_index]) || !is_subclass_of($slices[$last_index], 'Model_Base')){
            throw new Exception_Logic('Illegal value for last record.');
        }

        // 不要なデータを消す
        $this->models = array();
        $this->current_next_record = $slices[$last_index];
        $this->next_max_id = $this->current_next_record->get($this->id_name);
        unset($slices[$last_index]);
        $this->current_models = $slices;
    }

    /**
     * 「対象データ」を解析・分割します。
     * 
     * @throws Exception_Logic
     */
    private function parse(){
        if(is_null($this->id_name)){
            throw new Exception_Logic("The property 'id_name' must has value.");
        }

        if(is_null($this->unit_id_name)){
            throw new Exception_Logic("The property 'unit_id_name' must has value.");
        }

        $this->parsed_models = array();
        foreach($this->models as $model){
            if(!($model instanceof Model_Base)){
                throw new Exception_Logic('Illegal arguments.');
            }

            if(is_null($this->model_name)){
                $this->model_name = get_class($model);
            }
            if(!($model instanceof $this->model_name)){
                throw new Exception_Logic("Unmatched instance '" . get_class($model) . "' detected in suplied arguments.");
            }

            $id_value = $model->get($this->id_name);
            $unit_id_value = $model->get($this->unit_id_name);
            if(!isset($this->parsed_models[$unit_id_value])){
                $this->parsed_models[$unit_id_value] = array();
            }
            $this->parsed_models[$unit_id_value][$id_value] = $model;
        }
        // 不要になった情報を消す
        $this->models = array();
        $this->make_page_infos();
    }

    /**
     * 解析・分割された「対象データ」を元に、子インスタンスを生成します。
     * 
     * @throws Exception_Logic
     */
    private function make_page_infos(){
        $this->page_infos = array();
        if(!is_array($this->parsed_models)){
            $this->parse();
        }
        foreach($this->parsed_models as $unit_id_value=>$models){
            if(!is_array($models)){
                throw new Exception_Logic("The property 'parsed_models' has illegal value.");
            }

            // IDの降順でソートする
            krsort($models);
            // 子インスタンスの生成
            $pager = new self($models);
            $pager->set_count($this->count);
            $pager->set_id_name($this->id_name);
            $pager->set_unit_id_name($this->unit_id_name);
            $pager->set_model_name($this->model_name);
            $this->page_infos[$unit_id_value] = $pager;
            $this->unit_id_values[] = $unit_id_value;
        }
        // 不要になった情報を消す
        $this->parsed_models = array();
        $this->parse_total_counts();
    }

    /**
     * 生成された各ページャ・インスタンスへ、総件数を割り振ります。
     * 
     * @return void
     * @throws Exception_Logic
     */
    private function parse_total_counts(){
        if(!$this->unit_id_values){
            return;
        }

        if(is_null($this->page_infos)){
            $this->make_page_infos();
        }
        $model_name = $this->model_name;
        if(!$this->base_query){
            $this->base_query = DB::select()->from($model_name::table());
        }
        $results = $this->base_query->select_array(array(
                \DB::expr('COUNT(' . $this->unit_id_name . ') AS per_count'),
                $this->unit_id_name
            ), true)
            ->where($this->unit_id_name, 'in', $this->unit_id_values)
            ->group_by($this->unit_id_name)
            ->execute()
            ->as_array()
        ;
        foreach($results as $result){
            if(!isset($result[$this->unit_id_name])){
                continue;
            }

            $unit_id_value = $result[$this->unit_id_name];
            if(!isset($this->page_infos[$unit_id_value])){
                throw new Exception_Logic("page_infos must has contents of the '" . $this->unit_id_name . "=${unit_id_value}'.");
            }

            $pager = $this->page_infos[$unit_id_value];
            $pager->set_total_count((isset($result['per_count']) ? (int)$result['per_count'] : 0));
            $this->page_infos[$unit_id_value] = $pager;
        }
    }

}