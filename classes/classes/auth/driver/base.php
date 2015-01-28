<?php
/**
 * 認証ドライバ<br />
 *
 * @author k-kawaguchi
 * @package app
 * @extends \Auth\Auth_Driver
 */
class Auth_Driver_Base extends \Auth\Auth_Driver{

    /**
     * ログイン証明コード：ユーザIDキー名
     */
    const LOGIN_CREDENTIAL_USER_ID_KEY = 'user_id';

    /**
     * ログイン証明コード：ログイントークン・キー名
     */
    const LOGIN_CREDENTIAL_LOGIN_TOKEN_KEY = 'token';

    /**
     * ログイン証明コード：ランダム文字キー名<br />
     * （未使用）
     */
    const LOGIN_CREDENTIAL_NONCE_KEY = 'nonce';

    /**
     * ログイン証明コード：ログイン日時キー名
     */
    const LOGIN_CREDENTIAL_LOGIN_AT_KEY = 'login_at';

    /**
     * 認証キャッシュ：認証結果キー名
     */
    const RESULT_CACHE_BOOLEAN_KEY = 'boolean';

    /**
     * 認証キャッシュ：認証日時キー名
     */
    const RESULT_CACHE_TIME_KEY = 'integer';

    /**
     * インスタンス
     *
     * @var Auth_Driver_Base
     */
    private static $instance = null;

    /**
     * メールアドレス
     *
     * @var string
     */
    //protected $email = null;

    /**
     * 認証結果
     *
     * @var boolean
     */
    protected $is_authorized = false;
    /**
     * ユーザID
     *
     * @var int
     */
    protected $user_id = null;

    /**
     * 最終ログイン日時
     *
     * @var string
     */
    protected $last_login_at = null;

    /**
     * ログイントークン
     *
     * @var string
     */
    protected $login_token = null;

    /**
     * 一過性ランダム文字<br />
     * （未使用）
     *
     * @var string
     */
    protected $nonce = null;

    /**
     * ログイン証明コード
     *
     * @var string
     */
    protected $login_credential = null;

    /**
     * ログイン情報Model
     *
     * @var Model_User_Login
     */
    protected $user_login_model = null;

    /**
     * ユーザ情報Model
     *
     * @var Model_User
     */
    protected $user_model = null;

    /**
     * サインイン情報Model
     *
     * @var array(Model_User_Signin)
     */
    protected $user_signin_model_list = array();

    /**
     * 解析済ログイン証明コード
     *
     * @var array
     */
    protected $parsed_login_credential = array();

    /**
     * 直接的なインスタンス化は許容しません。
     *
     * @throws \AuthException
     */
    protected function __construct(){
        $config['id'] = get_called_class();
        if(!Config::get('login_token_pass_phrase') || !Config::get('login_token_pass_phrase')){
            throw new \AuthException('Config of pass phrase is not enough.');
        }

        parent::__construct($config);
    }

    /**
     * インスタンスを生成して返却します。
     *
     * @param array $config
     * @return Auth_Driver_Base
     */
    public static function forge(array $config = array()){
        if(!is_null(static::$instance)){
            return static::$instance;
        }

        static::$instance = new Auth_Driver_Base();
        return static::$instance;
    }

    /**
     * インスタンスをクリアします。
     */
    public static function clear(){
        static::$instance = null;
    }

    /**
     * 認証済かどうか判定します。
     *
     * @return boolean
     */
    public function is_authorized(){
        return ($this->is_authorized === true);
    }

    /**
     * ユーザIDを取得します。
     *
     * @return int
     */
    public function get_user_id(){
        return $this->user_id;
    }

    /**
     * ユーザIDを設定します。
     *
     * @param type $user_id
     * @throws \AuthException
     */
    public function set_user_id($user_id){
        if(!is_null($this->user_id)){
            throw new \AuthException('The user_id could not be duplicate. existent=' . $this->user_id);
        }

        $this->user_id = $user_id;
    }

    /**
     * メールアドレスを設定します。
     *
     * @param string $email
     */
    /* public function set_email($email){
        $this->email = $email;
    } */

    /**
     * メールアドレスを取得します。
     *
     * @return string
     */
    /* public function get_email(){
        if(is_null($this->email)){
            $this->get_user_model();
        }
        return $this->email;
    } */

    /**
     * ログイン・トークンを取得します。
     *
     * @return string
     */
    public function get_login_token(){
        return $this->login_token;
    }

    /**
     * ログイン日時を取得します。
     *
     * @return string
     */
    public function get_last_login_at(){
        return $this->last_login_at;
    }

    /**
     * ログイン証明コードを取得します。
     *
     * @return string
     */
    public function get_login_credential(){
        return $this->login_credential;
    }

    /**
     * ログイン証明コードを設定します。
     *
     * @param string $login_credential
     * @throws \AuthException
     */
    public function set_login_credential($login_credential){
        if(!is_null($this->login_credential)){
            throw new \AuthException('The login_credential could not be duplicate. existent=' . $this->login_credential);
        }

        $this->login_credential = $login_credential;
    }

    /**
     * インスタンスが保持しているModelを配列で返却します。
     *
     * @return array
     */
    public function get_all_model_list(){
        $return_ary = array();
        if($this->user_model){
            $return_ary[] = $this->user_model;
        }
        if($this->user_login_model){
            $return_ary[] = $this->user_login_model;
        }
        if(!empty($this->user_signin_model_list)){
            $return_ary = array_merge($return_ary, $this->user_signin_model_list);
        }
        return $return_ary;
    }

    /**
     * ユーザプロフィール情報を取得します。
     *
     * @return Model_User
     */
    public function get_user_model(){
        if($this->user_model){
            return $this->user_model;
        }

        if(is_null($this->user_id)){
            // TODO 例外にすべきか？
            return null;
        }

        $this->user_model = Model_User::get_by_id($this->user_id);
        if(is_null($this->user_model)){
            return null;
        }

        return $this->user_model;
    }

    /**
     * ユーザ情報を設定します。
     *
     * @param Model_User $user_model
     * @throws \AuthException
     */
    public function set_user_model(Model_User $user_model){
        if(is_null($user_model) || is_null($user_model->id)){
            throw new \AuthException('Illegal argument.');
        }

        $this->user_id = $user_model->id;
        $this->user_model = $user_model;
    }

    /**
     * ユーザ・ログイン情報を取得します。
     *
     * @return Model_User_Login
     */
    public function get_user_login_model(){
        if($this->user_login_model){
            return $this->user_login_model;
        }

        if(is_null($this->user_id)){
            // TODO 例外にすべきか？
            return null;
        }

        $this->user_login_model = Model_User_Login::get_by_user_id($this->user_id);
        if(is_null($this->user_login_model)){
            return null;
        }

        // ログイン日時の設定
        $this->last_login_at = $this->user_login_model->last_login_at;
        return $this->user_login_model;
    }

    /**
     * ユーザ・ログイン情報を設定します。
     *
     * @param string $last_login_at
     * @return Model_User_Login
     * @throws \AuthException
     */
    public function set_user_login_model(Model_User_Login $user_login_model){
        if(is_null($user_login_model)){
            throw new \AuthException('Illegal argument.');
        }

        $this->user_login_model = $user_login_model;
    }

    /**
     * アカウント種別を指定し、サインイン情報を取得します。
     *
     * @param int $account_type
     * @return Model_User_Signin
     */
    public function get_user_signin_model($account_type){
        if(isset($this->user_signin_model_list[$account_type])){
            return $this->user_signin_model_list[$account_type];
        }

//        $user_signin_model = Model_User_Signin::get_by_user_id_and_account_type($this->user_id, $account_type);
        $this->user_signin_model_list[$account_type] = $user_signin_model;
//        return $user_signin_model;
		return;
    }

    /**
     * ユーザ・サインイン情報を設定します。
     *
     * @param Model_User_Signin $user_signin_model
     * @throws \AuthException
     */
    public function set_user_signin_model(Model_User_Signin $user_signin_model){
        if(is_null($user_signin_model)){
            throw new \AuthException('Illegal argument.');
        }

        $account_type = $user_signin_model->account_type;
        $this->user_signin_model_list[$account_type] = $user_signin_model;
    }

    /**
     * ログイン証明コードの妥当性を確認し、妥当であれば認証済ユーザの情報を初期化します。<br />
     * ※認証不通過の場合、「AuthException」が発生します。
     *
     * @param void
     * @return boolean
     * @throws \AuthException
     */
    public function check_login_credential(){
        // ログイン証明コードの解析処理
        if(is_null($this->login_credential)){
            throw new \AuthException('The credential is empty.');
        }

        $decoded = self::decode_login_credential($this->login_credential);
        if(!$decoded){
            throw new \AuthException("Failed to decode for suplied credential '" . $this->login_credential . "'.");
        }

        $parsed = json_decode($decoded, true);
        if(!is_array($parsed)){
            throw new \AuthException("Failed to parse as json for decoded credential '${decoded}'. suplied(before decode)='${credential}'.");
        }

        if(!isset($parsed[self::LOGIN_CREDENTIAL_USER_ID_KEY]) || !isset($parsed[self::LOGIN_CREDENTIAL_LOGIN_TOKEN_KEY])
            || !isset($parsed[self::LOGIN_CREDENTIAL_LOGIN_AT_KEY])
        ){
            throw new \AuthException('Could not found expected key name for parsed credential. array=' . print_r($parsed, true));
        }

        // ログイン証明コードのチェック処理
        $this->parsed_login_credential = $parsed;
        $this->user_id = (int)$parsed[self::LOGIN_CREDENTIAL_USER_ID_KEY];
        $this->login_token = (string)$parsed[self::LOGIN_CREDENTIAL_LOGIN_TOKEN_KEY];
        $this->last_login_at = (string)$parsed[self::LOGIN_CREDENTIAL_LOGIN_AT_KEY];
        // 有効な設定値が指定されていれば、有効期限の判定を行う
        $expire_days = (string)Config::get('login_credential_expire_days');
        if(ctype_digit($expire_days)){
            $expire_at = date('Y-m-d H:i:s', (((int)$expire_days * 24 * 60 * 60) + strtotime($this->user_login_model->last_login_at)));
            if($expire_at < date('Y-m-d H:i:s')){
                throw new \AuthException("The login credential(" . $this->login_credential . ") is expired at '${expire_at}'.");
            }
        }

        // ログイン情報をDBの内容と突き合わせる
        $this->user_login_model = $this->get_user_login_model();
        if(!$this->user_login_model){
            throw new \AuthException("Suplied user_id '" . $this->user_id . "' is invalid, could not found record of `user_logins`.");
        }

        if(!self::validate_user_login_model($this->login_token, $this->last_login_at, $this->user_login_model)){
            throw new \AuthException("Login token is expired. user_login_model=" . print_r($this->user_login_model, true));
        }

        // ユーザ情報の存在チェック
        $this->user_model = $this->get_user_model();
        if(!$this->user_model){
            throw new \AuthException("Suplied user_id '" . $this->user_id . "' is invalid, could not found record of `users`.");
        }

        // 認証通過
        $this->is_authorized = true;
        // 認証結果をキャッシュする
        //$this->cache_authorized_result($this->login_credential);
        return true;
    }

    /**
     * ログイントークンを発行します。<br />
     * ※必ず「ログイン証明コード発行」処理の内部で実行されます。
     *
     * @param string $last_login_at
     * @return string
     * @throws \AuthException
     */
    private function issue_login_token($last_login_at = null){
        // 重複発行のチェック
        if(!is_null($this->login_token)){
            throw new \AuthException("Could not issue duplicated login_token. existent=" . $this->login_token);
        }

        $this->last_login_at = (is_null($last_login_at) ? date('Y-m-d H:i:s') : $last_login_at);
        $this->login_token = self::generate_login_token($this->user_id, $this->last_login_at);
        return $this->login_token;
    }

    /**
     * ログイン証明コードを発行します。
     *
     * @param int $user_id
     * @param string $last_login_at
     * @return string
     * @throws \AuthException
     */
    public function issue_login_credential($last_login_at = null){
        // 重複発行のチェック
        if(!is_null($this->login_credential)){
            throw new \AuthException("Could not issue duplicated login_credential. existent=" . $this->login_credential);
        }

        if(is_null($this->user_id)){
            throw new \AuthException("user_id is could not be NULL.");
        }

        // ログイン・トークンの発行
        $this->issue_login_token($last_login_at);
        if(is_null($this->login_token)){
            throw new \AuthException("Could not get login_token.");
        }

        // ログイン証明コードの発行
        $this->login_credential = self::encode_login_credential(
            json_encode(
                array(
                    self::LOGIN_CREDENTIAL_LOGIN_TOKEN_KEY => $this->login_token,
                    // ユーザIDは文字としてエンコードする
                    self::LOGIN_CREDENTIAL_USER_ID_KEY     => (string)$this->user_id,
                    self::LOGIN_CREDENTIAL_LOGIN_AT_KEY    => $this->last_login_at
                )
            )
        );
        return $this->login_credential;
    }

    /**
     * 認証済みのユーザをログアウトさせます。
     *
     * @param string $logout_date
     * @throws \AuthException
     */
    public function logout($logout_date = null){
        if(!$this->is_authorized()){
            throw new \AuthException("Request of the logout is not authorized.");
        }

        $this->user_login_model = $this->get_user_login_model();
        if(!$this->user_login_model){
            throw new \AuthException("Request of the logout not has user_login_model.");
        }

        // ログアウト
        $this->user_login_model->token = null;
        $this->user_login_model->last_logout_at	= (is_null($logout_date) ? date('Y-m-d H:i:s') : $logout_date);
        // サインアウト
        //$this->signout_all();
        // キャッシュの削除
        //$this->delete_authorized_result($this->get_login_credential());
    }

    /**
     * ログイン処理の内、共通の部分を実行します。
     *
     * @param string $last_login_at
     */
    protected function login_base($last_login_at, $is_new_credential = true){
        $login_model = $this->get_user_login_model();
        // 既にログイン済であれば、credentialは同一とする
        if($login_model && !is_null($login_model->token)){
            $this->last_login_at = $login_model->last_login_at;
        }else{
            $this->last_login_at = $last_login_at;
        }
        if($is_new_credential){
            // ログイン証明コードの発行
            $this->issue_login_credential($this->last_login_at);
        }
        $user_login_model = $this->get_user_login_model();
        // ログイン日時の設定
        $user_login_model->last_login_at = $this->last_login_at;
        // ログイントークンの設定
        $user_login_model->token = $this->get_login_token();
        $this->set_user_login_model($user_login_model);
    }

    /**
     * ログイン証明コードを暗号化します。
     *
     * @param string $credential
     * @return string
     */
    private static function decode_login_credential($credential){
        return Crypt::decode($credential, Config::get('login_credential_pass_phrase'));
    }

    /**
     * ログイン証明コードを複合します。
     *
     * @param string $source
     * @return string
     */
    private static function encode_login_credential($source){
        return Crypt::encode($source, Config::get('login_credential_pass_phrase'));
    }

    /**
     * ログイントークンを生成します。
     *
     * @param int $user_id
     * @param string $last_login_at
     * @param string $nonce
     * @param string $pass_phrase
     * @return string
     */
    private static function generate_login_token($user_id, $last_login_at, /*$nonce,*/ $pass_phrase = null){
        return sha1(
            implode(
                '_',
                array(
                    (is_null($pass_phrase) ? Config::get('login_token_pass_phrase') : $pass_phrase),
                    (string)$user_id,
                    $last_login_at
                )
            )
        );
    }

    /**
     * 認証キャッシュのキーを生成します。<br />
     * ※「[パスフレーズ]_[ログイン証明コード]_[ユーザID]」のMD5<br />
     * ※任意のユーザに関して、「パスフレーズ」「ログイン証明コード」が既知でない限り、キャッシュされた認証結果を参照することはできません。
     *
     * @param string $login_credential
     * @param string $pass_phrase
     * @return string
     */
    private static function generate_authorization_result_cache_key($login_credential, $user_id, $pass_phrase){
        return md5("${pass_phrase}_${login_credential}_${user_id}");
    }

    /**
     * 受け取ったログイン・トークンについて、user_loginsのレコードの情報と突き合わせて妥当性を判定します。
     *
     * @param string $login_token
     * @param string $nonce
     * @param Model_User_Login $user_login_model
     * @return boolean
     */
    private static function validate_user_login_model($login_token, $last_login_at, Model_User_Login $user_login_model){
        if(is_null($user_login_model)){
            return false;
        }

        if(is_null($user_login_model->token)){
            return false;
        }

        // トークンの一致チェック
        $login_token = (string)$login_token;
        if($login_token != (string)$user_login_model->token){
            return false;
        }

        // ログイン日時の一致チェック
        if($user_login_model->last_login_at != $last_login_at){
            return false;
        }

        // 同一トークンを再生成可能であること
        if($login_token != (string)self::generate_login_token($user_login_model->user_id, $user_login_model->last_login_at/*, $nonce*/)){
            return false;
        }

        return true;
    }

    /**
     * 直接的なインスタンス生成は、サポートしません。
     *
     * @param type $instance
     * @throws \AuthException
     */
    public static function instance($instance = null){
        throw new \AuthException('Could not directly generate instance.');
    }

    /**
     * ゲストのログイン等の機能は、サポートしません。
     *
     * @throws \AuthException
     */
    public function guest_login(){
        throw new \AuthException("Called method '" . __METHOD__ . "' is not supported.");
    }

}