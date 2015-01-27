<?php
/**
 * Event_Instanceクラスへ補完的な機能を追加します。
 * 
 * @author k-kawaguchi
 */
class Event extends Fuel\Core\Event{

    /**
     * 成功時に実行するコールバックの配列
     * 
     * @var array 
     */
    private static $on_success_callbacks = null;

    /**
     * 成功時に実行したい処理を登録します。
     * 
     * @param string $class_name クラス名
     * @param string $method_name メソッド名
     * @param array $arguemnts 引数
     * @throws \BadMethodCallException
     */
    public static function on_success($class_name, $method_name, array $arguemnts = array()){
        if(!method_exists($class_name, $method_name)){
            throw new \BadMethodCallException("Suplied method '${class_name}::${method_name}' is not callable."); 
        }

        if(is_null(static::$on_success_callbacks)){
            static::$on_success_callbacks = array();
        }
        static::$on_success_callbacks[] = array(
            'class_name'=>$class_name,
            'method_name'=>$method_name,
            'arguemnts'=>$arguemnts
        );
        parent::register('on_success', 'Event::execute_on_success');
    }

    /**
     * 登録されている「on_success」を一括実行します。
     */
    public static function execute_on_success(){
        if(!is_array(static::$on_success_callbacks)){
            return;
        }

        // 一括実行
        foreach(static::$on_success_callbacks as $callback_ary){
            $class_name  = $callback_ary['class_name'];
            $method_name = $callback_ary['method_name'];
            $arguemnts   = $callback_ary['arguemnts'];
            Log::debug("Executing found callback '${class_name}::${method_name}' in '" . __METHOD__ . "'.");
            forward_static_call_array(array($class_name, $method_name), $arguemnts);
        }
        // 実行し終わったコールバックを空にする
        static::$on_success_callbacks = null;
    }

}