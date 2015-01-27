<?php
/**
 * メール送信を行うクラスです。
 * 
 * @author k-kawaguchi
 */
class Util_Mail{

    /**
     * デフォルトのViewデータ
     * 
     * @var array 
     */
    private $default_data = array();
    /**
     * メール・ドライバ
     * 
     * @var Email_Driver_Mail 
     */
    private $mail_instance = null;
    /**
     * 宛先
     * 
     * @var string/array 
     */
    private $to_email = null;
    /**
     * メールのテンプレート名
     * 
     * @var string 
     */
    private $mail_name = null;
    /**
     * 件名のView
     * 
     * @var View 
     */
    private $subject_view = null;
    /**
     * 件名への追加埋め込みデータ
     * 
     * @var array 
     */
    private $add_subject_data = array();
    /**
     * 本文のView
     * 
     * @var View 
     */
    private $body_view = null;
    /**
     * 本文への追加埋め込みデータ
     * 
     * @var array 
     */
    private $add_body_data = array();
    /**
     * 送信結果
     * 
     * @var boolean 
     */
    private $is_success = false;
    /**
     * 最後に発生した例外
     * 
     * @var Exception 
     */
    private $last_error_throwable = null;
    /**
     * 最後に発生したエラーメッセージ
     * 
     * @var string 
     */
    private $last_error_message = null;
    /**
     * 最後に発生したエラートレース
     * 
     * @var string 
     */
    private $last_error_trace = null;

    /**
     * 「宛先」「テンプレート名」毎の単位で1インスタンスを生成して下さい。
     * 
     * @param string/array $to_email 宛先（複数の場合は配列を指定する）
     * @param string $mail_name テンプレート名
     * @param string $encording エンコード方式
     * @throws Exception_Logic
     */
    public function __construct($to_email, $mail_name, $encording = 'sjis'){
        $this->to_email = $to_email;
        $this->mail_name = (string)$mail_name;
        if(empty($this->to_email) || empty($this->mail_name)){
            throw new Exception_Logic("Illegal argument.");
        }

        // パッケージ読み込み
        if(!Package::loaded('email')){
            Package::load('email');
        }
        // TODO 「Email::forge」実行後にEmailクラスのstaticプロパティ等、悪影響因子が残留しないかどうか検証する！（FuelPHPにおいて、staticプロパティに関連するトラブルが顕著に多い！）
        // インスタンス生成
        $this->mail_instance = Email::forge($encording);
        // デフォルトのデータ
        $this->default_data  = array(
            'service_name'=>Config::get('message_service_name', ''),
            'inquiry_address'=>Config::get('message_inquiry_address', ''),
            'help_address'=>Config::get('message_help_address', ''),
            'company_name'=>Config::get('message_company_name', ''),
            'company_url'=>Config::get('message_company_url', '')
        );
    }

    /**
     * 送信に成功したかどうか調べます。
     * 
     * @return boolean
     */
    public function is_send_success(){
        return $this->is_success;
    }

    /**
     * 宛先を取得します
     * 
     * @return string
     */
    public function get_to_email($to_string = true){
        if(is_array($this->to_email) && $to_string === true){
            return implode(',', $this->to_email);
        }

        return $this->to_email;
    }

    /**
     * メールの名称を取得します。
     * 
     * @return type
     */
    public function get_mail_name(){
        return $this->mail_name;
    }

    /**
     * 最後に発生したエラーメッセージを取得します。
     * 
     * @return string
     */
    public function get_last_error_message(){
        return $this->last_error_message;
    }

    /**
     * 最後に発生したエラーのトレースを取得します。
     * 
     * @return string
     */
    public function get_last_error_trace(){
        return $this->last_error_trace;
    }

    /**
     * 最後に発生したExceptionを取得します。
     * 
     * @return Exception
     */
    public function get_last_error_throwable(){
        return $this->last_error_throwable;
    }

    /**
     * 最後に発生したExceptionの名称を取得します。
     * 
     * @return string
     */
    public function get_last_error_name(){
        if(!$this->last_error_throwable){
            return null;
        }

        return get_class($this->last_error_throwable);
    }

    /**
     * 件名に埋め込みデータを設定します。<br />
     * ※テンプレートが存在しない場合「FuelException(The requested view could not be found: ～)」等が発生します。
     * 
     * @param array $data
     */
    public function set_subject_data(array $data = array()){
        $this->add_subject_data = $data;
        $this->subject_view = \View::forge('mail/subject/' . $this->mail_name, array_merge($this->default_data, $data));
    }

    /**
     * 件名に設定されている埋め込みデータを返却します。
     * 
     * @return array
     */
    public function get_subject_data(){
        return array_merge($this->default_data, $this->add_subject_data);
    }

    /**
     * 本文に埋め込みデータを設定します。<br />
     * ※テンプレートが存在しない場合「FuelException(The requested view could not be found: ～)」等が発生します。
     * 
     * @param array $data
     */
    public function set_body_data(array $data = array()){
        $this->add_body_data = $data;
        $this->body_view = \View::forge('mail/body/' . $this->mail_name, array_merge($this->default_data, $data));
    }

    /**
     * 本文に設定さている埋め込みデータを返却します。
     * 
     * @return array
     */
    public function get_body_data(){
        return array_merge($this->default_data, $this->add_body_data);
    }

    /**
     * 送信
     * 
     * @return boolean
     * @throws Exception
     */
    public function send(){
        // TODO メールごとに任意のヘッダを設定できるように機能拡張する
        $sender = Config::get('mail_sender', null);
        if(is_null($sender)){
            throw new Exception_Logic("The config parameter 'mail_sender' is NULL.");
        }

        $this->mail_instance->from($sender);
        $this->mail_instance->to($this->to_email);
        $this->mail_instance->subject($this->get_subject_view()->render());
        $this->mail_instance->body($this->get_body_view());
        try{
            $this->mail_instance->send();
        }catch(Exception $e){
            $this->last_error_throwable = $e;
            $this->last_error_message = $e->getMessage();
            $this->last_error_trace = $e->getTraceAsString();
            throw $e;
        }

        $this->is_success = true;
        return true;
    }

    /**
     * 件名のViewを取得します。
     * 
     * @return Fuel\Core\View
     */
    private function get_subject_view(){
        if(!is_null($this->subject_view)){
            return $this->subject_view;
        }

        $this->set_subject_data();
        return $this->subject_view;
    }

    /**
     * 本文のViewを取得します。
     * 
     * @return Fuel\Core\View
     */
    private function get_body_view(){
        if(!is_null($this->body_view)){
            return $this->body_view;
        }

        $this->set_body_data();
        return $this->body_view;
    }

}