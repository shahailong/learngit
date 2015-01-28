<?php
/**
 * Fieldsetクラスへ補完的な機能を追加します。
 * 
 * @author k-kawaguchi
 */
class Fieldset extends Fuel\Core\Fieldset{

    /**
     * staticプロパティが保持するインスタンスを削除します。
     */
    public static function clear_instance(){
        static::$_instance = null;
        static::$_instances = array();
    }
    
}