<?php
/**
 * リクエスト・パラメータの不正
 * @author k-kawaguchi
 */
class Exception_Paramerror extends Exception_Base{
    public function __construct($message = null, Exception $previous = null){
        parent::__construct($message, Constants_Code::PARAM_INVALID, $previous);
    }
}