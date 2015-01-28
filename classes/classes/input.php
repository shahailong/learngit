<?php
/**
 * Inputクラスへ補完的な機能を追加します。
 * 
 * @author k-kawaguchi
 */
class Input extends Fuel\Core\Input{
    /**
     * static変数が保持する内容を削除します。
     */
    public static function cleanup(){
        static::$json = null;
        static::$xml = null;
        static::$php_input = null;
        static::$detected_uri = null;
        static::$detected_ext = null;
        static::$input = null;
        static::$put_patch_delete = null;
    }
}
