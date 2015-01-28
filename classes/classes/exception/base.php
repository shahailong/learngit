<?php
/**
 * 例外の基底クラス
 * @author k-kawaguchi
 */
abstract class Exception_Base extends Exception{

    public function __construct($message = null, $code = Constants_Code::UNKNOWN_ERROR, Exception $previous = null){
        parent::__construct($message, $code, $previous);
    }

}