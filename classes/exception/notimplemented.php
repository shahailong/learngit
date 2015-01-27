<?php
/**
 * 機能が未実装の際に発生させる例外
 * @author k-kawaguchi
 */
class Exception_Notimplemented extends Exception_Base{
    public function __construct($message = null, Exception $previous = null){
        parent::__construct($message, Constants_Code::NOTIMPLEMENTED, $previous);
    }
}