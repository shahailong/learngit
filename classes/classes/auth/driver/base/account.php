<?php
/**
 * アカウント種別毎に固有な認証ドライバの基底クラス<br />
 *
 * @author k-kawaguchi
 * @package app
 * @extends Auth_Driver_Base
 */
abstract class Auth_Driver_Base_Account extends Auth_Driver_Base{

    /**
     * アカウントModel
     *
     * @var Model
     */
    protected $account_model = null;

    /**
     * 使い捨てトークンModel
     *
     * @var Model_Onetimetoken
     */
    protected $onetimetoken_model = null;

    /**
     * メール認証確認URL<br />
     * （未使用）
     *
     * @var string
     */
    protected $confirm_url = null;

    /**
     * メール認証確認値<br />
     * （未使用）
     *
     * @var string
     */
    protected $str_onetimetoken_values = null;

    /**
     * 解析済メール認証確認値<br />
     * （未使用）
     *
     * @var array
     */
    protected $parsed_onetimetoken_values = null;

    /**
     * 受け取ったAuth_Driver_Baseを複製し、インスタンスを初期化します。
     *
     * @param Auth_Driver_Base $base_driver
     * @throws \AuthException
     */
    public function __construct(Auth_Driver_Base $base_driver){
        if(is_null($base_driver)){
            throw new \AuthException("Suplied arguement is NULL.");
        }

        $object_vars = get_object_vars($base_driver);
        if(!is_array($object_vars)){
            throw new \AuthException("Could not get properties. base_driver=" . print_r($base_driver, true));
        }

        // プロパティの値をAuth_Driver_Baseから複製する
        foreach($object_vars as $name=>$value){
            // 「\Orm\Model」の「clone」は、プライマリキーがNULLにリセットする特性があるのでこの場合のみ分岐する
            $this->$name = (is_object($value) && !($value instanceof \Orm\Model) ? clone $value : $value);
        }
    }

    /**
     * @param アカウントのModel_Base
     */
    abstract public function set_account_model(Model_Base $account_model);

    /**
     *
     */
    abstract public function get_account_model();

    /**
     * 登録時証明コードを生成する処理の実装を必須とする。<br />
     * ※引数は、可変長として下さい。
     *
     * @return string
     */
    abstract public function issue_onetimetoken_values();

    /**
     * ログイン<br />
     * ※引数は、可変長として下さい。<br />
     * ※内部でissue_login_credentialを呼び出して、ログイン証明コードの発行を行って下さい。
     */
    abstract public function login();

    /**
     * メール認証確認URL
     */
    //abstract public function get_confirm_url();

    /**
     * 登録認証情報を設定します。
     *
     * @param Model_Register_Credential $register_credential_model
     * @throws \AuthException
     */
    /* public function set_onetimetoken_model(Model_Onetimetoken $onetimetoken_model){
        if(is_null($onetimetoken_model)){
            throw new \AuthException("Suplied arguement is NULL.");
        }

        if(is_null($onetimetoken_model->token)){
            throw new \AuthException("Illegal state arguement suplied.");
        }

        $this->onetimetoken_model = $onetimetoken_model;
    } */

    /**
     * メール認証用使い捨てトークンを取得します。
     *
     * @return Model_Onetimetoken
     */
    /* public function get_onetimetoken_model(){
        return $this->onetimetoken_model;
    } */

    /**
     * ユーザ情報を取得します。<br />
     * ※「Auth_Driver_Base->get_user_model」が取得できない場合に、メールアドレスによる検索も試みます。
     *
     * @return Model_User
     */
    public function get_user_model(){
        $this->user_model = parent::get_user_model();

        if(!is_null($this->user_model)){
            if((!is_null($this->user_model->id) && !is_null($this->user_id)) && ($this->user_model->id != $this->user_id)){
                throw new \AuthException("Unmatched user_model founcd.");
            }

            return $this->user_model;
        }

        $this->user_id = $this->user_model->id;
        return $this->user_model;
    }

    /**
     * 保持している全てのModelを配列で取得します。
     *
     * @return array
     */
    public function get_all_model_list(){
        $return_ary = parent::get_all_model_list();
        if($this->account_model){
            $return_ary[] = $this->account_model;
        }
        return $return_ary;
    }

    /**
     * 受け取ったアカウント種別が、唯一のサインインかどうか判定します。
     *
     * @param Model_User_Signin $user_signin_model
     * @return boolean
     * @throws \AuthException
     */
    public function is_account_last_signin(Model_User_Signin $user_signin_model){
        $signin_account_models = $this->get_user_signin_model_list();
        if(!$signin_account_models){
            throw new \AuthException("Not signed in any type of account.");
        }

        $target_account_type = $user_signin_model->account_type;
        if(!isset($signin_account_models[$target_account_type])){
            throw new \AuthException("Target account of type '" . $user_signin_model->account_type . "' is not signed in.");
        }

        if(count($signin_account_models) == 1){
            if($signin_account_models[$target_account_type]->account_type != $user_signin_model->account_type){
                throw new \AuthException("Unmatched account type is found.");
            }

            return true;
        }

        return false;
    }

    /**
     * 単独でのインスタンス生成を許容しません。
     *
     * @param array $config
     * @throws \AuthException
     */
    public static function forge(array $config = array()){
        throw new \AuthException('Could not directly forge instance.');
    }
}