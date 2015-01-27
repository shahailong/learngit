<?php
/**
 * 独自アカウントに固有な認証ドライバ<br />
 *
 * @author k-kawaguchi
 * @package app
 * @extends Auth_Driver_Base_Account
 */
class Auth_Driver_Base_Account_Original extends Auth_Driver_Base_Account{

    /**
     * 「this->login」の引数インデックス：メールアドレス
     */
    //const LOGIN_ARG_EMAIL_INDEX = 0;
    /**
     * 「this->login」の引数インデックス：パスワード
     */
    //const LOGIN_ARG_PASSWORD_INDEX = 1;

    /**
     * 符号化パスワード
     *
     * @var string
     */
    private $password = null;

    /**
     * 入力パスワード<br />
     * （符号化される前のパスワード）
     *
     * @var string
     */
    private $password_source = null;

    /**
     * ログインID<br />
     *
     * @var string
     */
    private $login_id = null;

    /**
     * 登録日時<br />
     * ※パスワードの符号化に使用されます。
     *
     * @var string
     */
    private $registered_at = null;

    /**
     * アカウントModel
     *
     * @var Model_User_Account_Original
     */
    protected $account_model = null;

    /**
     * ログインIDを設定します。
     *
     * @param string $login_id
     */
    public function set_login_id($login_id){
    	$this->login_id = $login_id;
    }

    /**
     * 登録日時を設定します。
     *
     * @param string $registered_at
     */
    public function set_registered_at($registered_at){
        $this->registered_at = $registered_at;
    }

    /**
     * 入力パスワードを設定します。
     *
     * @param string $password_source
     */
    public function set_password_source($password_source){
        $this->password_source = $password_source;
        $this->password = null;
    }

    /**
     * ハッシュ化されたパスワードを取得します。
     *
     * @return string
     * @throws \AuthException
     */
    public function get_password_hash(){
        if(!is_null($this->password)){
            return $this->password;
        }

        if(is_null($this->password_source) || is_null($this->registered_at)){
            throw new \AuthException('password_source or registered_at is NULL.');
        }

        $this->password = static::hash_password($this->password_source, $this->registered_at);
        return $this->password;
    }

    /**
     * Model_User_Account_Originalを設定します。
     *
     * @param Model_User_Account_Original $account_model
     * @throws \AuthException
     */
    public function set_account_model(Model_Base $account_model){
        if(!($account_model instanceof Model_User_Account_Original)){
            throw new \AuthException('Illegal argument.');
        }
        $this->account_model = $account_model;
        $this->login_id      = $account_model->login_id;
        $this->password      = $account_model->password;
        $this->registered_at = $account_model->registered_at;
    }

    /**
     * Model_User_Account_Originalの情報を取得します。
     *
     * @return Model_User_Account_Original
     */
    public function get_account_model(){
        if($this->account_model){
            return $this->account_model;
        }

        if(is_null($this->user_id)){
            return null;
        }

        $account_model = Model_User_Account_Original::get_by_user_id($this->user_id);
        $this->set_account_model($account_model);
        return $account_model;
    }


    /**
     * Model_User_Account_Originalの情報を取得します。
     *
     * @return Model_User_Account_Original
     */
    public function get_account_model_by_login_id(){
    	if($this->account_model){
    		return $this->account_model;
    	}

    	if(is_null($this->login_id)){
    		return null;
    	}

    	$account_model = Model_User_Account_Original::get_by_login_id($this->login_id);

    	if($account_model){
    		$this->set_account_model($account_model);
    	}

    	return $account_model;
    }


    /**
     * 登録証明コードを発行します。
     */
    public function issue_onetimetoken_values(){
        if(is_null($this->user_id) || is_null($this->login_id)){
            throw new \AuthException('Issue register credential is failed.');
        }

        $this->register_credential = Crypt::encode(
            json_encode(array('user_id'=>$this->user_id, 'login_id'=>$this->login_id)),
            Config::get('original_account_register_credential_pass_phrase')
        );
        return $this->register_credential;
    }

    /**
     * メール認証URLを返却します。<br />
     * （未使用）
     *
     * @return string
     * @throws \AuthException
     */
    /* public function get_confirm_url(){
        if(!is_null($this->confirm_url)){
            return $this->confirm_url;
        }

        if(is_null($this->onetimetoken_model)){
            throw new \AuthException('register_credential_model not must be NULL, it is must be created.');
        }

        $this->confirm_url = static::get_email_link_url(
            $this->onetimetoken_model->token
        );
        return $this->confirm_url;
    } */

    /**
     * パスワードを受け取り、既存のパスワードと一致するかどうか判定します。
     *
     * @param string $password_source
     * @return boolean
     */
    public function is_current_password_match($password_source){
        $account_model = $this->get_account_model();
        if(!$account_model){
            return false;
        }

        return (
            (string)$account_model->password
            ==
            (string)static::hash_password($password_source, $account_model->registered_at)
        );
    }

    /**
     * ログイン
     *
     * @param string $login_id ログインID
     * @param string $password_source 入力パスワード
     * @param string $last_login_at ログイン日時（省略可能）
     *
     * @throws \AuthException
     */
    public function login(){
        // 引数の解析とチェック
        $this->parse_login_args(func_get_args());
        if(is_null($this->login_id) || is_null($this->password_source)){
            throw new \AuthException('Illegal arguments.');
        }

        $account_model = $this->get_account_model_by_login_id();
        if(!$account_model){
        	return false;
        }

        if($account_model->login_id != $this->login_id){
        	return false;
        }

        if(is_null($this->get_user_id())){
        	$this->set_user_id($account_model->user_id); // 重複セットエラーを回避
        }

        $user_model = $this->get_user_model();
        if(!$user_model){
            return false;
        }

        $is_password_match = (
            (string)$account_model->password
            ==
            (string)static::hash_password($this->password_source, $account_model->registered_at)
        );
        if(!$is_password_match){
            return false;
        }

        // ログイン
        $this->login_base($this->last_login_at);
/*         $user_signin_model = $this->get_user_signin_model(Model_User_Signin::ACCOUNT_TYPE_ORIGINAL);
        if(!$user_signin_model){
            throw new \AuthException('user_signin_model is not found. login_id=' . $this->login_id);
        }

        $user_signin_model->signin_flg = Model_User_Signin::SIGNIN_FLG_TRUE;
        $this->set_user_signin_model($user_signin_model); */
        $this->is_authorized = true;
        return true;
    }

    /**
     * loginメソッドの引数を解析します。
     *
     * @param array $args
     */
    private function parse_login_args(array $args){
        foreach(array('login_id', 'password_source', 'last_login_at') as $indx=>$name){
            $this->$name = (isset($args[$indx]) ? $args[$indx] : null);
        }
    }

    /**
     * 入力パスワードをハッシュ化します。
     *
     * @param string $password_source
     * @param string $registered_at
     * @return string
     */
    private static function hash_password($password_source, $registered_at){
        return (string)sha1("${password_source}_${registered_at}");
    }

    /**
     *
     * @param string $code
     * @param string $credential
     * @return string URL
     */
/*     private static function get_email_link_url($token){
        return "http://" . Input::server('SERVER_NAME') . "/account/original/confirm?token=${token}";
    } */

}