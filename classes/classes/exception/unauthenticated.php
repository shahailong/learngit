<?php
/**
 * 認証エラー
 * @author k-kawaguchi
 */
class Exception_Unauthenticated extends Exception_Base{

    public function __construct($message = null, Exception $previous = null){
        parent::__construct($message, Constants_Code::UNAUTHENTICATED, $previous);
    }

}
