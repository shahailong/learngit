<?php
/**
 * Orm\Observerの「after_」メソッドをオーバーライドしてSQLをログに書き出します。
 * @author k-kawaguchi
 */
class Observer_Logging extends Orm\Observer{

    private $load_last_query = null;
    private $save_last_query = null;
    private $delete_last_query = null;

    /**
     * ORM読み込みの後に呼び出され、SELECTクエリをログに書き出します。
     * 
     * @param object $obj
     */
    public function after_load($obj){
        $query = DB::last_query();
        if($query !== $this->load_last_query){
            Log::debug('[sql:'.  get_class($obj) .']'.$query);
        }
        $this->load_last_query = $query;
    }

    /**
     * ORM削除後に呼び出され、DELETEクエリをログに書き出します。
     * 
     * @param object $obj
     */
    public function after_delete($obj){
        $query = DB::last_query();
        if($query !== $this->delete_last_query){
            Log::debug('[sql:'.  get_class($obj) .']'.$query);
        }
        $this->delete_last_query = $query;
    }

    /**
     * ORM保存後に呼び出され、UPDATEクエリ或いはINSERTクエリをログに書き出します。
     * 
     * @param object $obj
     */
    public function after_save($obj){
        $class = get_class($obj);
        $query = DB::last_query();
        switch(true){
            case ($obj instanceof Model_Image_Temporary) :
                $query = 'Save binary to `' . $class::table() . '` key info -> ' . print_r($obj->get_pk_values(), true);
        }
        if($query !== $this->save_last_query){
            Log::debug('[sql:'.  $class .']'.$query);
        }
        $this->save_last_query = $query;
    }
}