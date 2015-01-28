<?php
/**
 * Twitterアカウントに固有な認証ドライバ<br />
 *
 * @author k-kawaguchi
 * @package app
 * @extends Auth_Driver_Base_Account
 */
require_once APPPATH .  'vendor/twitteroauth.php';
class Auth_Driver_Base_Account_Twitter extends Auth_Driver_Base_Account{

    const ONETIMETOKEN_KEY_NAME = '3rdapp_token';

    /**
     * TwitterAPI「verify_credentials」を用いた認証結果
     *
     * @var boolean
     */
    private $is_verify_credentials_checked = false;
    /**
     * Twitterのアクセストークン
     *
     * @var string
     */
    private $access_token = null;
    /**
     * TwitterOAuthインスタンス
     *
     * @var TwitterOAuth
     */
    private $twitteroauth_instance = null;
    /**
     * TwitterのユーザID<br />
     * ※桁数が大きいため、intへのキャストは避けるべきです。
     *
     * @var real
     */
    private $twitter_id = null;
    /**
     * oauth_token（OAuthのトークン）
     *
     * @var string
     */
    private $oauth_token = null;
    /**
     * oauth_verifier（OAuthの認証証明コード）
     *
     * @var string
     */
    private $oauth_verifier = null;
    /**
     * oauth_token_secret（OAuthの秘密鍵）
     *
     * @var string
     */
    private $oauth_token_secret = null;

    /**
     * コンストラクタで、必要な設定値やライブラリの存在をチェックします。
     *
     * @param Auth_Driver_Base $base_driver
     * @throws \AuthException
     */
    public function __construct(Auth_Driver_Base $base_driver){
        // 設定ファイルの情報が足りているか調べる
        if(!(
            Config::get('twitter_consumer_key') &&
            Config::get('twitter_consumer_secret') &&
            Config::get('twitter_request_token_url') &&
            Config::get('twitter_authorize_url') &&
            Config::get('twitter_access_token_url') &&
            Config::get('twitter_callback_url')
        )){
            throw new \AuthException('Config of twitter is not enough.');
        }

        // 必要なクラスの存在確認
        if(!class_exists('TwitterOAuth')){
            throw new \AuthException("Required class 'TwitterOAuth' is not found.");
        }

        parent::__construct($base_driver);
    }

    /**
     * Twitter認証情報が確認済みかどうか判定します。<br />
     * ※ログインが不通過になるような場合に、Twitter認証情報には問題が無いことを確認することが可能です。
     *
     * @return boolean
     */
    public function is_verify_credentials_checked(){
        return ($this->is_verify_credentials_checked === true);
    }

    /**
     * Twitterのuser_idを設定します。
     *
     * @param real $twitter_id
     */
    public function set_twitter_id($twitter_id){
        $this->twitter_id = $twitter_id;
    }

    /**
     * Twitterのuser_idを返却します。
     *
     * @return real
     */
    public function get_twitter_id(){
        if(!is_null($this->twitter_id)){
            return $this->twitter_id;
        }

        $account_model = $this->get_account_model();
        if(!$account_model){
            return null;
        }

        $this->twitter_id = $account_model->twitter_id;
        return $this->twitter_id;
    }

    /**
     * oauth_tokenを設定します。
     *
     * @param string $oauth_token
     */
    public function set_oauth_token($oauth_token){
        $this->oauth_token = $oauth_token;
    }

    /**
     * oauth_tokenを取得します。
     *
     * @return string
     */
    public function get_oauth_token(){
        return $this->oauth_token;
    }

    /**
     * oauth_token_secretを設定します。
     *
     * @param string $oauth_token_secret
     */
    public function set_oauth_token_secret($oauth_token_secret){
        $this->oauth_token_secret = $oauth_token_secret;
    }

    /**
     * oauth_token_secretを取得します。
     *
     * @return string
     */
    public function get_oauth_token_secret(){
        return $this->oauth_token_secret;
    }

    /**
     * oauth_verifierを設定します。
     *
     * @param string $oauth_token_secret
     */
    public function set_oauth_verifier($oauth_verifier){
        $this->oauth_verifier = $oauth_verifier;
    }

    /**
     * oauth_verifierを取得します。
     *
     * @return string
     */
    public function get_oauth_verifier(){
        return $this->oauth_verifier;
    }

    /**
     * 解析済みの使い捨てトークンの値を配列で返却します。
     *
     * @return array
     */
    public function get_parsed_onetimetoken_values(){
        return $this->parsed_onetimetoken_values;
    }

    /**
     * TwitterOAuthのインスタンスを返却します。
     *
     * @param string $oauth_token
     * @param string $oauth_token_secret
     * @return TwitterOAuth
     */
    public function get_twitteroauth_instance($oauth_token = null, $oauth_token_secret = null){
        if(!is_null($this->twitteroauth_instance)){
            return $this->twitteroauth_instance;
        }

        $this->twitteroauth_instance = new TwitterOAuth(
            // Consumer key
            Config::get('twitter_consumer_key'),
            // Consumer secret
            Config::get('twitter_consumer_secret'),
            $oauth_token,
            $oauth_token_secret
        );
        return $this->twitteroauth_instance;
    }

    /**
     * TwitterアカウントのModelを設定します。
     *
     * @param Model_Base $account_model
     * @throws \AuthException
     */
    public function set_account_model(Model_Base $account_model){
        if(is_null($account_model)){
            throw new \AuthException('account_model not must be NULL.');
        }

        if(!is_null($this->user_id) && !is_null($account_model->user_id)){
            if((string)$this->user_id != (string)$account_model->user_id){
                throw new \AuthException('Unmatch account_model.');
            }

            $this->user_id = $account_model->user_id;
        }
        if(!is_null($account_model->twitter_id)){
            if(!is_null($this->twitter_id) && (string)$this->twitter_id != (string)$account_model->twitter_id){
                throw new \AuthException('Unmatch account_model.');
            }

            $this->twitter_id = $account_model->twitter_id;
        }
        $this->account_model = $account_model;
    }

    /**
     * TwitterアカウントのModelを返却します。
     *
     * @return Model_User_Account_Twitter
     * @throws \AuthException
     */
    public function get_account_model(){
        if(!is_null($this->account_model)){
            return $this->account_model;
        }

        if(is_null($this->twitter_id)){
            return null;
        }

        $this->account_model = Model_User_Account_Twitter::get_by_twitter_id($this->twitter_id);
        if(!is_null($this->account_model)){
            $this->user_id = $this->account_model->user_id;
            if(!is_null($this->user_id) && $this->user_id != $this->account_model->user_id){
                throw new \AuthException('Unmatch account_model.');
            }
        }

        return $this->account_model;
    }

    /**
     * 使い捨てトークンを発行します。
     *
     * @param $merge 追加するパラメータ
     * @return string
     * @throws \AuthException
     */
    public function issue_onetimetoken_values(array $merge = array()){
        if(is_null($this->oauth_token_secret)){
            throw new \AuthException('oauth_token_secret not must be NULL.');
        }

        $ary = array('oauth_token_secret'=>$this->oauth_token_secret);
        if($merge){
            $ary = array_merge($ary, $merge);
        }
        $this->str_onetimetoken_values = json_encode($ary);
        return $this->str_onetimetoken_values;
    }

    /**
     * TODO 用途未定。不要なら消す！
     *
     * @return string
     */
    public function get_confirm_url(){
        if(!is_null($this->confirm_url)){
            return $this->confirm_url;
        }
    }

    /**
     * Twitter連携の有無を判定します。
     *
     * @return boolean
     */
    public function is_signin(){
        $signin_model = $this->get_user_signin_model(Model_User_Signin::ACCOUNT_TYPE_TWITTER);
        if(!$signin_model){
            return false;
        }

        return ($signin_model->signin_flg == Model_User_Signin::SIGNIN_FLG_TRUE);
    }

    /**
     * loginメソッドの可変長引数を解析します。
     *
     * @param array $args
     * @return array
     */
    private function parse_login_args(array $args){
        foreach(array('twitter_id', 'oauth_token', 'oauth_token_secret', 'oauth_verifier', 'last_login_at') as $indx=>$name){
            $this->$name = (isset($args[$indx]) ? $args[$indx] : null);
        }
    }

    /**
     * ログイン
     *
     * @param $twitter_id Twitterのuser_id
     * @param $oauth_token
     * @param $oauth_verifier
     * @param $oauth_token_secret
     * @param $last_login_at ログイン日時 省略可能
     * @param $is_update_token アクセストークン更新フラグ 省略可能（デフォルト：TRUE）
     * @return boolean
     * @throws \AuthException
     */
    public function login(){
        // 引数解析
        $this->parse_login_args(func_get_args());
        // 認証
        return $this->authenticate(true);
    }

    /**
     * サインイン
     *
     * @param $twitter_id Twitterのuser_id
     * @param $oauth_token
     * @param $oauth_verifier
     * @param $oauth_token_secret
     * @param $last_login_at ログイン日時 省略可能
     * @param $is_update_token アクセストークン更新フラグ 省略可能（デフォルト：TRUE）
     * @return boolean
     * @throws \AuthException
     * @return boolean
     */
    public function signin(){
        // 引数解析
        $this->parse_login_args(func_get_args());
        // 認証
        return $this->authenticate(false);
    }

    /**
     * Twitter連携を解除します。
     *
     * @throws \AuthException
     */
    public function signout(){
        if(!$this->is_authorized()){
            throw new \AuthException("Request of the signout is not authorized.");
        }

        // Twitterの認証情報を消す
        $account_model = $this->get_account_model();
        $account_model->oauth_token = null;
        $account_model->oauth_verifier = null;
        $account_model->oauth_token_secret = null;
        $this->set_account_model($account_model);
        // サインアウトへの切り替え
        $user_signin_model = $this->get_user_signin_model(Model_User_Signin::ACCOUNT_TYPE_TWITTER);
        $user_signin_model->signin_flg = Model_User_Signin::SIGNIN_FLG_FALSE;
        $this->set_user_signin_model($user_signin_model);
        /* if(!$this->is_signin_whichever()){
            throw new \AuthException("Must signed in whichever of account.");
        } */
    }

    /**
     * 認証
     *
     * @param boolean $is_new_credential 新規認証フラグ
     * @return boolean
     * @throws \AuthException
     */
    protected function authenticate($is_new_credential = true){
        $this->is_authorized = false;
        if(is_null($this->twitter_id) || is_null($this->oauth_token) || is_null($this->oauth_token_secret)){
            throw new \AuthException('Illegal arguments.');
        }

        // 認証情報をTwitterへ問い合わせる
        if($this->is_verify_credentials_checked !== true){
            if(!$this->connect_check_verify_credentials($this->twitter_id, $this->oauth_token, $this->oauth_token_secret)){
                return false;
            }
        }

        $account_model = $this->get_account_model();
        if(!$account_model || !$account_model->user_id){
            return false;
        }

        // ユーザIDを明示的に保持する
        $this->user_id = $account_model->user_id;
        $user_model = $this->get_user_model();
        if(!$user_model){
            return false;
        }

        if((string)$user_model->id !== (string)$account_model->user_id){
            throw new \AuthException('Unmatch user_id for eixstent user.');
        }

        // ログイン
        $this->login_base($this->last_login_at, $is_new_credential);
        // 以下、Twitter固有のログイン処理
//        $user_signin_model = $this->get_user_signin_model(Model_User::LOGIN_TYPE_TWITTER);
//        if(!$user_signin_model){
//            throw new \AuthException('user_signin_model is not found. twitter_id=' . $this->twitter_id);
//        }
//
//        $user_signin_model->signin_flg = Model_User_Signin::SIGNIN_FLG_TRUE;
//        $this->set_user_signin_model($user_signin_model);
        $account_model->oauth_token = $this->oauth_token;
        $account_model->oauth_verifier = $this->oauth_verifier;
        $account_model->oauth_token_secret = $this->oauth_token_secret;
        $this->set_account_model($account_model);
        // ログイン成功
        $this->is_authorized = true;
        return true;
    }

    /**
     * サインイン情報を設定します。
     *
     * @param Model_User_Signin $user_signin_model
     * @throws \AuthException
     */
    public function set_user_signin_model(Model_User_Signin $user_signin_model){
        if((string)$user_signin_model->account_type != (string)Model_User_Signin::ACCOUNT_TYPE_TWITTER){
            throw new \AuthException("The '" . __CLASS__ . "' only can receive account_type '" . Model_User_Signin::ACCOUNT_TYPE_TWITTER . "'.");
        }

        parent::set_user_signin_model($user_signin_model);
    }

    /**
     * ログインページのURLを生成する<br />
     * ※「callback_url」に使い捨てトークンを附加します。
     *
     * @return string
     * @throws \AuthException
     */
    public function get_request_url(){
        $onetimetoken_model = $this->get_onetimetoken_model();
        if(is_null($onetimetoken_model)){
            throw new \AuthException('The onetimetoken_model not must be NULL.');
        }

        $twitteroauth_instance = $this->get_twitteroauth_instance();
        $tokens = $twitteroauth_instance->getRequestToken(
            Config::get('twitter_callback_url') .
            '?' . static::ONETIMETOKEN_KEY_NAME . '=' . $onetimetoken_model->token
        );
        if(!isset($tokens['oauth_token']) || !isset($tokens['oauth_token_secret'])){
            throw new \AuthException('Could not get oauth_token or(and) oauth_token_secret. -> ' . print_r($tokens, true));
        }

        $this->oauth_token = $tokens['oauth_token'];
        $this->oauth_token_secret = $tokens['oauth_token_secret'];
        return $twitteroauth_instance->getAuthorizeURL($this->oauth_token);
    }

    /**
     * アクセストークンを直接、設定します。
     *
     * @param array $access_token
     * @throws \AuthException
     *
     */
    public function set_access_token(array $access_token){
        if(!isset($access_token['oauth_token']) || !isset($access_token['oauth_token_secret']) || !isset($access_token['user_id'])){
            throw new \AuthException('The information of access_token is not enough. access_token-> ' . print_r($access_token, true));
        }

        $this->access_token = $access_token;
    }

    /**
     * TwitterAPI「verify_credentials」と疎通し、認証結果を判定します。
     *
     * @param type $twitter_id
     * @param type $oauth_token
     * @param type $oauth_token_secret
     * @return boolean
     */
    public function connect_check_verify_credentials($twitter_id = null, $oauth_token = null, $oauth_token_secret = null){
        if(!is_null($oauth_token)){
            $this->oauth_token = $oauth_token;
        }

        if(!is_null($oauth_token_secret)){
            $this->oauth_token_secret = $oauth_token_secret;
        }

        if(!is_null($twitter_id)){
            $this->twitter_id = $twitter_id;
        }

        if(is_null($this->oauth_token) || is_null($this->oauth_token_secret) || is_null($this->twitter_id)){
            throw new \AuthException('Illegal arguments.');
        }

        // アクセス・トークンの更新を行っている場合、一致チェックをする
        if($this->access_token){
            if(!(
                (string)$this->access_token['user_id'] === (string)$this->twitter_id
                && (string)$this->access_token['oauth_token'] === (string)$this->oauth_token
                && (string)$this->access_token['oauth_token_secret'] === (string)$this->oauth_token_secret
            )){
                Log::debug('Unmatch access token. tokens=' . print_r($this->access_token, true));
                return false;
            }
        }

        // Twitterと疎通して認証する
        $twitteroauth_instance = $this->get_twitteroauth_instance($this->oauth_token, $this->oauth_token_secret);
        $verify_credentials_json = $twitteroauth_instance->OAuthRequest("https://api.twitter.com/1.1/account/verify_credentials.json","GET",array());
        Log::debug("Logging response of Twitter API 'verify_credentials'. json=" . $verify_credentials_json);
        if(!$verify_credentials_json){
            return false;
        }

        // Twitter IDの確認
        $verify_credentials = json_decode($verify_credentials_json, true);
        if(!isset($verify_credentials['id'])){
            return false;
        }

        if((string)$verify_credentials['id'] != (string)$this->twitter_id){
            return false;
        }

        // Twitterの認証情報が正常
        $this->is_verify_credentials_checked = true;
        return true;
    }

    /**
     * Twitterへ通信し、アクセス・トークンを更新する
     *
     * @param string $oauth_token
     * @param string $oauth_verifier
     * @param string $oauth_token_secret
     * @return void
     * @throws \AuthException
     */
    public function connect_access_token($oauth_token, $oauth_verifier, $oauth_token_secret = null){
        if(!is_null($this->access_token)){
            return $this->access_token;
        }

        if(!$oauth_token_secret){
            $this->parse_onetimetoken_values();
        }else{
            $this->oauth_token_secret = $oauth_token_secret;
        }
        $this->oauth_verifier = $oauth_verifier;
        $twitteroauth_instance = $this->get_twitteroauth_instance($oauth_token, $this->oauth_token_secret);
        $this->access_token = $twitteroauth_instance->getAccessToken($this->oauth_verifier);
        if(!isset($this->access_token['oauth_token']) || !isset($this->access_token['oauth_token_secret']) || !isset($this->access_token['user_id'])){
            throw new \AuthException('The information of access_token is not enough. access_token-> ' . print_r($this->access_token, true));
        }

        // 認証パラメータの更新
        $this->twitter_id = $this->access_token['user_id'];
        $this->oauth_token = $this->access_token['oauth_token'];
        $this->oauth_token_secret = $this->access_token['oauth_token_secret'];
        $this->account_model = $this->get_account_model();
        // Tiwtterアカウントを登録済みであれば、一致チェックする
        if(!is_null($this->account_model)){
            if((string)$this->account_model->twitter_id != (string)$this->twitter_id){
                throw new \AuthException('Unmatch twitter account detected. twitter_id=' . $this->twitter_id . ' / model=' . print_r($this->account_model, true));
            }

            $this->account_model->oauth_token = $this->oauth_token;
            $this->account_model->oauth_token_secret = $this->oauth_token_secret;
            $this->account_model->oauth_verifier = $this->oauth_verifier;
        }
        return $this->access_token;
    }

    /**
     * 使い捨てトークンに保持させる値を解析します。
     *
     * @throws \AuthException
     */
    private function parse_onetimetoken_values(){
        $onetimetoken_model = $this->get_onetimetoken_model();
        if(is_null($onetimetoken_model)){
            throw new \AuthException('The onetimetoken_model not must be NULL.');
        }

        $ary = json_decode($onetimetoken_model->values, true);
        if(!isset($ary['oauth_token_secret'])){
            throw new \AuthException('Could not get oauth_token_secret.');
        }

        $this->oauth_token_secret = $ary['oauth_token_secret'];
        $this->parsed_onetimetoken_values = $ary;
    }

}
