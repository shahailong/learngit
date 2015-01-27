<?php
/**
 * 内部ロジックに関したエラー<br />
 * ・予期しないデータ不整合<br />
 * ・メソッド、関数での引数不正<br />
 * ・メソッド、関数の返値および処理内容が期待通りではない等
 * @author k-kawaguchi
 */
class Exception_Logic extends Exception{
    public function __construct($message = null, Exception $previous = null){
        parent::__construct($message, $previous);
    }
}