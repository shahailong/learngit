<?php
/**
 * 仕様違反<br />
 * @author k-kawaguchi
 */
class Exception_Service extends Exception_Base{

    /**
     * メッセージと、Constants_Codeにて定義したエラーコードを受け取ります。<br />
     * ※「エラーコード」は、「1」以上のみが許容されます。それ以外は、全て「UNKNOWN_ERROR」へ補正されます。
     * 
     * @param string $message
     * @param int $code エラーコード
     * @param Exception $previous
     */
    public function __construct($message = null, $code = Constants_Code::UNKNOWN_ERROR, Exception $previous = null){
        $code = ((ctype_digit((string)$code) && (int)$code > 0) ? (int)$code : Constants_Code::UNKNOWN_ERROR);
        parent::__construct($message, $code, $previous);
    }

}
