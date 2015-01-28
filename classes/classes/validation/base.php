<?php
/**
 * バリデーション<br />
 * プレフィックス「Validation_」で開始されるクラス名にControllerに存在するaction名と同一名称のstaticメソッドにて、バリデータを定義します。
 *
 * @author k-kawaguchi
*/
class Validation_Base extends Fuel\Core\Validation{

    private static $validator = null;
    private static $is_initialized = false;
    private static $is_validated = false;
    private static $validation_class_name = null;
    private static $controller_name = null;
    private static $action_name = null;
    private static $validated_params = array();

    /**
     * action名（action内でのPHP定数「__METHOD__」）を受け取り、当該機能の初期化を行います。
     * 
     * @param string $action_context action名（「クラス名」::「メソッド名」）
     * @param boolean $is_autovalidate 初期化の際にバリデーションを一括実行するかどうか
     * @return boolean
     * @throws Exception, Exception_Paramerror
     */
    public static function init($action_context, $is_autovalidate = true){
        // プロパティの初期化
        static::$is_initialized = false;
        static::$is_validated = false;
        static::$validated_params = array();
        static::$validator = null;
        if(is_null($action_context)){
            throw new Exception_Logic('Could not initialize validation from NULL context.');
        }

        $ary = explode('::', $action_context);
        if(!isset($ary[0])){
            throw new Exception_Logic('Invalid context. action_context=' . $action_context);
        }

        static::$controller_name = (string)$ary[0];
        if(!isset($ary[1])){
            throw new Exception_Logic('Invalid context. action_context=' . $action_context);
        }

        static::$action_name = (string)$ary[1];
        // 存在しないaction名
        if(!method_exists(static::$controller_name, static::$action_name)){
            throw new Exception_Logic('Invalid context. action_context=' . static::$controller_name . '::' . static::$action_name);
        }

        if($is_autovalidate === true){
            static::validate_all();
        }
        static::$is_initialized = true;
        return true;
    }

    /**
     * 空文字チェックのバリデーションを行います。
     * 
     * @param 入力値 $value
     * @return boolean
     */
    public static function not_blank($value){
        if(!is_null($value) && is_string($value) && $value == ''){
            return false;
        }

        return true;
    }

    /**
     * バリデーションが初期化されているかどうか判定します。
     * 
     * @return boolean
     */
    public static function is_initialized(){
        return (static::$is_initialized === true);
    }

    /**
     * 検証済みの値を取得します。
     * 
     * @param string $name
     * @return mixed
     * @throws Exception_Paramerror
     */
    public static function get_valid($name){
        if(static::$is_validated){
            return static::get_value($name);
        }

        // 全部バリデーションする
        static::validate_all();
        return static::get_value($name);
    }

    /**
     * 検証済みの全値を配列で取得します。
     * 
     * @return array
     */
    public static function get_all_valid(){
        if(!static::$is_validated){
            // 全部バリデーションする
            static::validate_all();
        }
        return static::$validated_params;
    }

    /**
     * 値を取得します。
     * 
     * @param string $name
     * @return mixed
     */
    private static function get_value($name){
        return(
            isset(static::$validated_params[$name])
            ? static::$validated_params[$name]
            : null
        );
    }

    /**
     * リクエストパラメータ全てのバリデーションを実行します。<br />
     * ※バリデータが未定義の場合、一律で検証通過となります。
     * 
     * @return boolean
     * @throws Exception_Paramerror
     */
    public static function validate_all(){
        $validator = static::get_validator();
        // バリデータが未定義
        if(!$validator){
            static::$is_validated = true;
            static::$validated_params = Input::all();
            return true;
        }

        // 通過
        if($validator->run(Input::all())){
            static::$is_validated = true;
            static::$validated_params = $validator->validated();
            return true;
        }

        // 不通過
        throw new Exception_Paramerror("Invalid parameter. error_info -> " . print_r(static::get_error_info(), true) . ' / input -> ' . print_r(Input::all(), true));
    }

    /**
     * バリデーションのエラー情報を取得します。
     * 
     * @return array
     */
    public static function get_error_info(){
        $validator = static::get_validator();
        if(!$validator){
            return array();
        }

        $ret = array();
        foreach($validator->error() as $name=>$error){
            $ret[$name] = (string)$error;
        }
        return $ret;
    }

    /**
     * Validationを取得します。
     * 
     * @return Validation
     * @throws Exception
     */
    public static function get_validator(){
        if(!is_null(static::$validator)){
            return static::$validator;
        }

        // initされていない場合
        if(!static::$controller_name || !static::$action_name){
            throw new Exception_Logic('Validation_Base is not initialized.');
        }

        // Validationを返却するメソッドが存在しない場合
        static::$validation_class_name = str_replace('Controller_', 'Validation_', static::$controller_name);
        if(!method_exists(static::$validation_class_name, static::$action_name)){
            throw new Exception_Logic('The required validator(' . static::$validation_class_name . '::' . static::$action_name . ') is not defined.');
        }

        // TODO バリデータが未定義の場合に、エラーにすべきか？スルーすべきか？
        $validator = forward_static_call_array(array(static::$validation_class_name, static::$action_name),array());
        if(is_null($validator)){
            return null;
        }

        if(!($validator instanceof Validation)){
            throw new Exception_Logic('The validator(' . static::$validation_class_name . '::' . static::$action_name . ') must be instance of Validation.');
        }

        static::$validator = $validator;
        return $validator;
    }

}