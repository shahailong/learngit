<?php
/**
 * Domainの基底クラス<br />
 * ※「エニッシュ」案件より、リソースを頂きました。
 *
 * @author
 * @package  app
 * @extends  Controller_Rest_Base
 */
abstract class Domain_Base{

    private $context;

    /**
     * コンストラクタ
     * 
     * @author
     * @param Controller_Rest_Base $context
     * @param array $options
     */
    public function __construct(Controller $context){
        if(!$context){
            throw new Exception_Logic('Illegal context suplied for ' . __CLASS__ . '::__construct. context= ' . print_r($context, true));
        }

        $this->context = $context;
    }

    /**
     * コンテキストとして、コントローラのインスタンスを返却します。
     * 
     * @return Controller_Rest_Base
     */
    public function get_context(){
        return $this->context;
    }

    /**
     * トランザクション処理用に、更新されたオブジェクトを格納する
     * 
     * @author
     * @param mixed $object 更新されたModel_Baseオブジェクト
     */
    protected function add_to_save(Model_Base $object){
        $this->context->add_update_que($object);
    }

    /**
     * Modelの配列リストを、一括更新queへ追加します。
     * 
     * @author k-kawaguchi
     * @param array $objects
     */
    protected function add_list_to_save(array $objects){
        foreach($objects as $object){
            $this->context->add_update_que($object);
        }
    }

    /**
     * Modelの配列を受け取り、トランザクション処理で保存します。
     * 
     * @param array $model_ary
     * @param boolean $is_transaction
     * @return void
     */
    public function save_all_as_transaction(array $model_ary, $is_transaction = true){
        if($is_transaction === false){
            Model_Base::save_all($model_ary);
            return;
        }

        $this->method_as_transaction('save_all_as_transaction', array($model_ary, false));
    }

    /**
     * メソッド名と引数を受け取り、これをトランザクション処理として実行します。<br />
     * ※メソッドの修飾子は、protectedまで実行可能です（privateは実行できません）。
     * ※1メソッドで、更新処理を完結させて下さい（1メソッド内で、全てのsaveおよびdeleteを行って下さい）。<br />
     * ※add_to_save、add_list_to_saveによって「一括更新que」に保持された内容は、当該トランザクション処理では無視されます。<br />
     * ※「一括更新que」に保持された内容は、Controllerの終了処理にて、別単位のトランザクション処理で更新されます（「一括更新que」と当該メソッドの併用は好ましくないものと思われます）。<br />
     * 
     * @author k-kawaguchi
     * @param string $mehtod_name
     * @param array $arguments
     * @throws Exception
     */
    public function method_as_transaction($method_name, array $arguments = array()){
        try{
            return Model_Base::instance_method_as_transaction($this, 'call_instance_method', array($method_name, $arguments));
        } catch (Exception $ex) {
            Log::debug("Caught exception while executing '" . get_class($this) . "->${method_name}'. message=" . $ex->getMessage() . " / trace:\n " . $ex->getTraceAsString());
            throw $ex;
        }
    }

    /**
     * インスタンス・メソッドを呼び出します。
     * 
     * @param string $method_name
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    public function call_instance_method($method_name, array $arguments = array()){
        if(!method_exists($this, $method_name)){
            throw new Exception_Logic("Suplied method " . get_class($this) . "->${method_name} is not callable.");
        }

        return call_user_func_array(array($this, $method_name), $arguments);
    }

}