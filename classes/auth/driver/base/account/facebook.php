<?php
/**
 * Facebookアカウントに固有な認証ドライバ<br />
 *
 * @author k-kawaguchi
 * @package app
 * @extends Auth_Driver_Base_Account
 */
class Auth_Driver_Base_Account_Facebook extends Auth_Driver_Base_Account{

    /**
     * FacebookAPI「me」を用いた認証結果
     * 
     * @var boolean 
     */
    private $is_me_checked = false;
    /**
     * FacebookのユーザID<br />
     * ※桁数が大きいため、intへのキャストは避けるべきです。
     * 
     * @var real 
     */
    private $facebook_id = null;
    /**
     * アクセストークン
     * 
     * @var string 
     */
    private $access_token = null;

    /**
     * FacebookのユーザIDを取得します。
     * 
     * @return real
     */
    public function get_facebook_id(){
        return $this->facebook_id;
    }

    /**
     * アクセストークンを取得します。
     * 
     * @return string
     */
    public function get_access_token(){
        return $this->access_token;
    }

    /**
     * 認証情報をFacebookへ、問い合わせ済みかどうか判定します。
     * 
     * @return boolean
     */
    public function is_me_checked(){
        return ($this->is_me_checked === true);
    }

    /**
     * アカウントModelを設定します。
     * 
     * @param Model_Base $account_model
     */
    public function set_account_model(Model_Base $account_model){
        if(is_null($account_model)){
            throw new \AuthException('account_model not must be NULL.');
        }

        if(!is_null($this->user_id) && !is_null($account_model->user_id)){
            if($this->user_id != $account_model->user_id){
                throw new \AuthException('Unmatch account_model.');
            }

            $this->user_id = $account_model->user_id;
        }
        $this->account_model = $account_model;
    }

    /**
     * アカウントModelを取得します。
     * 
     * @return Model_Base
     * @throws \AuthException
     */
    public function get_account_model(){
        if(!is_null($this->account_model)){
            return $this->account_model;
        }

        if(is_null($this->facebook_id)){
            return null;
        }

        $this->account_model = Model_User_Account_Facebook::get_by_facebook_id($this->facebook_id);
        if(!is_null($this->account_model)){
            $this->user_id = $this->account_model->user_id;
            if(!is_null($this->user_id) && $this->user_id != $this->account_model->user_id){
                throw new \AuthException('Unmatch account_model.');
            }
        }

        return $this->account_model;
    }

    // TODO 実装しなくても良いかもしれない
    /**
     * TODO 未実装
     */
    public function issue_onetimetoken_values(){
        return null;
    }

    /**
     * TODO 未実装
     */
    public function get_confirm_url(){
    }

    /**
     * TODO 未実装
     * @param Model_Register_Credential $register_credential_model
     */
    public function init_register_credential_model(Model_Register_Credential $register_credential_model){
    }

    /**
     * loginメソッドの引数を解析します。
     * 
     * @param array $args
     */
    private function parse_login_args(array $args){
        foreach(array('facebook_id', 'access_token', 'expires_in', 'last_login_at') as $indx=>$name){
            $this->$name = (isset($args[$indx]) ? $args[$indx] : null);
        }
    }

    /**
     * ログイン
     * 
     * @param mixed $facebook_id
     * @param string $access_token
     * @param mixed $expires_in （省略可能）
     * @param string $last_login_at ログイン日時（省略可能）
     * @return boolean
     * @throws \AuthException
     */
    public function login(){
        $this->parse_login_args(func_get_args());
        if(is_null($this->facebook_id) || is_null($this->access_token) /*|| is_null($this->expires_in)*/){
            throw new \AuthException('Illegal arguments.');
        }

        // Facebookへの問い合わせが未実施であったら確認する
        if(!$this->is_me_checked()){
            if(!$this->connect_check_me($this->facebook_id, $this->access_token)){
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
        $this->login_base($this->last_login_at);
        $account_model->access_token = $this->access_token;
        $account_model->expires_in = $this->expires_in;
        // ログイン成功
        $this->is_authorized = true;
        return true;
    }

    /**
     * FacebookAPIと疎通して、Facebookユーザ情報の妥当性を確認します。
     * 
     * @param real $facebook_id
     * @param string $access_token
     */
    public function connect_check_me($facebook_id, $access_token){
        $this->facebook_id = $facebook_id;
        $this->access_token = $access_token;
        $user_json = @file_get_contents('https://graph.facebook.com/me?access_token=' . $access_token);
        Log::debug("Logging response of Facebook API 'me'. json=${user_json}");
        if(!$user_json){
            return false;
        }

        $user_ary = json_decode($user_json, true);
        if(!isset($user_ary['id'])){
            Log::error('Error response ->' . $user_json);
            return false;
        }

        if((string)$user_ary['id'] != (string)$facebook_id){
            return false;
        }

        $this->is_me_checked = true;
        return true;
    }
}