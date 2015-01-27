<?php
/**
 * Twitterアカウントに関する振る舞いを記述するクラス
 * @author k-kawaguchi
 */
class Domain_User_Account_Twitter extends Domain_Base{

    const AUTH_TYPE_NEW = 1;
    const AUTH_TYPE_EXISTENT = 2;
    const AUTHORIZATION_EXPIRE_HOUR = 1;

    private $auth_driver;

    /**
     * コンストラクタ
     * @param type $id
     */
    public function __construct(Controller $context, Auth_Driver_Base_Account_Twitter $auth_driver){
        if(is_null($context) || is_null($auth_driver)){
            throw new Exception_Logic('Illegal argument.');
        }

        $this->auth_driver = $auth_driver;
        parent::__construct($context);
    }

     /**
     * アカウント登録のDBトランザクション<br />
     * ※Twitter認証情報を予めTwitterへ問い合わせて確認済みとして下さい。
     *
     * @param string $nickname
     * @param string $device_info
     * @param string $os
     * @return Auth_Driver_Base_Account_Twitter
     * @throws Exception_Service
     * @throws Exception_Logic
     */
    protected function register_account_db_update($nickname, $device_info,$os){
        // 認証ドライバの取得
        $auth_driver = $this->auth_driver;
        // 登録日時
        $registered_at = date('Y-m-d H:i:s');

        // Twitter認証情報をDBと突き合わせる
        $twitter_id = $auth_driver->get_twitter_id();
        $oauth_token = $auth_driver->get_oauth_token();
        $oauth_token_secret = $auth_driver->get_oauth_token_secret();
        $twitter_account_model = Model_User_Account_Twitter::get_by_twitter_id($twitter_id);
        if($twitter_account_model){
            // 既に登録済みであれば、重複登録は許可しない
            if(Model_User::get_by_id($twitter_account_model->user_id)){
                throw new Exception_Service("The twitter informations(twitter_id:${twitter_id} / oauth_token:" . $twitter_account_model->oauth_token . " / oauth_token_secret=" . $twitter_account_model->oauth_token_secret . ") are already registered as the another user(id: " . $twitter_account_model->user_id . ").", Constants_Code::TWITTER_ALREADY_REGISTERED);
            }
        }else{
            $twitter_account_model = Model_User_Account_Twitter::forge();
            $twitter_account_model->twitter_id = $twitter_id;
        }

		// 新規にユーザ情報を作成する
        $user_model = Model_User::new_record(
	        		$nickname,
	        		Model_User::LOGIN_TYPE_TWITTER
	        		);
        $user_model->save();
        $user_id = $user_model->id;

        // Twitterアカウント情報の設定
		$user_account_twitter_model = Model_User_Account_Twitter::new_record(
					$user_id,
					$twitter_id,
					$oauth_token,
//					$oauth_verifier,
					NULL,
					$oauth_token_secret
	        		);
        $user_account_twitter_model->save();

//        // Twitterアカウントの作成
//        $twitter_account_model->oauth_token = $oauth_token;
//        $twitter_account_model->oauth_token_secret = $oauth_token_secret;
//
//
//        // TwitterアカウントとユーザIDを関連付け、アカウント情報を設定する
//        $user_id = $user_model->id;
//        $twitter_account_model->user_id = $user_id;
//        $twitter_account_model->save();
//        $auth_driver->set_account_model($twitter_account_model);
//
//
//        // ユーザ情報の設定
//        $auth_driver->set_user_model($user_model);
//
//        // サインイン情報の設定
//        $auth_driver->set_user_signin_model(Model_User_Signin::new_record(
//            $user_id,
//            Model_User_Signin::ACCOUNT_TYPE_TWITTER,
//            Model_User_Signin::SIGNIN_FLG_FALSE
//        ));

        // ログイン情報の設定
        $auth_driver->set_user_login_model(Model_User_Login::new_record(
            $user_id,
            null,
            $registered_at
        ));

        // ログインさせる
        if(!$auth_driver->login(
            $twitter_id,
            $oauth_token,
            $oauth_token_secret,
            null,
            $registered_at
        )){
            // 以上の処理で設定したパラメータが正しければ、このエラーは発生しない
            throw new Exception_Logic('The login for the twitter account(twitter_id: ' . $auth_driver->get_twitter_id() . ') may not be failed.');
        }
		// ログイン履歴を残す
        $log_user_login_model = Model_Log_User_Login::new_record(
        		$user_id,
        		$device_info,
				$os
        );
        $log_user_login_model->save();


        // TODO メール送信

        // 認証系のデータを全て更新する
        Model_Base::save_all($auth_driver->get_all_model_list());

        return $auth_driver;
    }

    /**
     * アカウント登録のDBトランザクション<br />
     * ※Twitter認証情報を予めTwitterへ問い合わせて確認済みとして下さい。
     *
     * @param string $email
     * @param string $display_name
     * @param string $registerd_at
     * @return Auth_Driver_Base_Account_Twitter
     * @throws Exception_Service
     * @throws Exception_Logic
     */
//    protected function register_account_db_update($display_name, $registerd_at = null){
//        $auth_driver = $this->auth_driver;
//        // 登録日時
//        $registered_at = (is_null($registerd_at) ? date('Y-m-d H:i:s') : $registerd_at);
//        // Twitter認証情報をDBと突き合わせる
//        $twitter_id = $auth_driver->get_twitter_id();
//        $oauth_token = $auth_driver->get_oauth_token();
//        $oauth_token_secret = $auth_driver->get_oauth_token_secret();
//        $twitter_account_model = Model_User_Account_Twitter::get_by_twitter_id($twitter_id);
//        if($twitter_account_model){
//            // 既に登録済みであれば、重複登録は許可しない
//            if(Model_User::get_by_id($twitter_account_model->user_id)){
//                throw new Exception_Service("The twitter informations(twitter_id:${twitter_id} / oauth_token:" . $twitter_account_model->oauth_token . " / oauth_token_secret=" . $twitter_account_model->oauth_token_secret . ") are already registered as the another user(id: " . $twitter_account_model->user_id . ").", Constants_Code::TWITTER_ALREADY_REGISTERED);
//            }
//        }else{
//            $twitter_account_model = Model_User_Account_Twitter::forge();
//            $twitter_account_model->twitter_id = $twitter_id;
//        }
//        // 新規にユーザ情報を作成する
//        $user_model = Model_User::new_record(null, $display_name);
//        $user_model->save();
//        $user_model->image_hash = Model_Image_Hash::create_hash('Model_User', $user_model->id);
//        $user_model->save();
//        // Twitterアカウントの作成
//        $twitter_account_model->oauth_token = $oauth_token;
//        $twitter_account_model->oauth_token_secret = $oauth_token_secret;
//        // TwitterアカウントとユーザIDを関連付け、アカウント情報を設定する
//        $user_id = $user_model->id;
//        $twitter_account_model->user_id = $user_id;
//        $twitter_account_model->save();
//        $auth_driver->set_account_model($twitter_account_model);
//        // ユーザ情報の設定
//        $auth_driver->set_user_model($user_model);
//        // サインイン情報の設定
//        $auth_driver->set_user_signin_model(Model_User_Signin::new_record(
//            $user_id,
//            Model_User_Signin::ACCOUNT_TYPE_TWITTER,
//            Model_User_Signin::SIGNIN_FLG_FALSE
//        ));
//        // ログイン情報の設定
//        $auth_driver->set_user_login_model(Model_User_Login::new_record(
//            $user_id,
//            null,
//            $registered_at
//        ));
//        // ログインさせる
//        if(!$auth_driver->login(
//            $twitter_id,
//            $oauth_token,
//            $oauth_token_secret,
//            null,
//            $registered_at
//        )){
//            // 以上の処理で設定したパラメータが正しければ、このエラーは発生しない
//            throw new Exception_Logic('The login for the twitter account(twitter_id: ' . $auth_driver->get_twitter_id() . ') may not be failed.');
//        }
//
//        // TODO メール送信
//
//        // 認証系のデータを全て更新する
//        Model_Base::save_all($auth_driver->get_all_model_list());
//        // 設定情報の登録
//        Model_User_Setting::new_record($user_id)->save();
//        return $auth_driver;
//    }

    /**
     * Twitterアカウントに対する登録処理を行います。
     *
     * @param mixed $twitter_id
     * @param string $oauth_token
     * @param string $oauth_token_secret
     * @param string $nickname
     * @param string $device_info
     * @return Auth_Driver_Base_Account_Twitter
     */
    public function register_account($twitter_id, $oauth_token, $oauth_token_secret, $nickname, $device_info,$os){
        $auth_driver = $this->auth_driver;
        // DB更新よりも前にTwitter疎通を完了させる（トランザクション処理への負荷を軽減させるため）
        if(!$auth_driver->connect_check_verify_credentials($twitter_id, $oauth_token, $oauth_token_secret)){
            throw new Exception_Service('Failed to verify credentials to Twitter for the suplied parameters. params-> ' . print_r(array($twitter_id, $oauth_token, $oauth_token_secret), true), Constants_Code::AUTH_TWITTER_FAILED);
        }

        // DBトランザクション
        $auth_driver = $this->method_as_transaction('register_account_db_update', array(
            $nickname,
            $device_info,
			$os
        ));
        $user_model = $auth_driver->get_user_model();
        Model_User::filter_response_records($auth_driver->get_user_id(), array($user_model));
        return Model_User::format_single_element(
            $user_model,
            // ログインコード
            array('credential'=>$auth_driver->get_login_credential(),
				'valuepush_alias' => str_pad($user_model->id, 20, "0", STR_PAD_LEFT))
        );
    }
     /**
     * Twitterアカウントに対する登録処理を行います。
     *
     * @param string $email
     * @param mixed $twitter_id
     * @param string $oauth_token
     * @param string $oauth_verifier
     * @param string $oauth_token_secret
     * @return Auth_Driver_Base_Account_Twitter
     */
//    public function register_account($display_name, $twitter_id, $oauth_token, $oauth_token_secret){
//        $auth_driver = $this->auth_driver;
//        // DB更新よりも前にTwitter疎通を完了させる（トランザクション処理への負荷を軽減させるため）
//        if(!$auth_driver->connect_check_verify_credentials($twitter_id, $oauth_token, $oauth_token_secret)){
//            throw new Exception_Service('Failed to verify credentials to Twitter for the suplied parameters. params-> ' . print_r(array($twitter_id, $oauth_token, $oauth_token_secret), true), Constants_Code::AUTH_TWITTER_FAILED);
//        }
//
//        // DBトランザクション
//        $auth_driver = $this->method_as_transaction('register_account_db_update', array(
//            $display_name,
//            date('Y-m-d H:i:s')
//        ));
//        $user_model = $auth_driver->get_user_model();
//        Model_User::filter_response_records($auth_driver->get_user_id(), array($user_model));
//        return Model_User::format_single_element(
//            $user_model,
//            // ログインコード
//            array('credential'=>$auth_driver->get_login_credential())
//        );
//    }

    /**
     * Twitterアカウントのログイン
     *
     * @param mixed $twitter_id
     * @param string $oauth_token
     * @param string $oauth_token_secret
     * @param string $device_info
     * @return Auth_Driver_Base_Account_Twitter
     * @throws Exception_Service
     */
    public function login($twitter_id, $oauth_token, $oauth_token_secret,$device_info,$os){
        $now_date = date('Y-m-d H:i:s');

		if(is_null(Model_User_Account_Twitter::get_by_twitter_id($twitter_id))){
			throw new Exception_Service('The twitter user(twitter_id: ' . $twitter_id . ') attempted login is not registered yet.', Constants_Code::TWITTER_ACCOUNT_NONEREGISTER);
		}
        // ログインする
        if(!$this->auth_driver->login($twitter_id, $oauth_token, $oauth_token_secret, null, $now_date)){
            throw new Exception_Service('Failed to login for-> twitter_id=' . $this->auth_driver->get_twitter_id(), Constants_Code::AUTH_TWITTER_FAILED);
        }

		// ログイン履歴を残す
        $log_user_login_model = Model_Log_User_Login::new_record(
        		$this->auth_driver->get_user_id(),
        		$device_info,
				$os
        );
        $log_user_login_model->save();

        $auth_driver = $this->check_authenticate_result();
        // 更新内容をqueへ追加する
        $this->add_list_to_save($auth_driver->get_all_model_list());
        $user_model = $auth_driver->get_user_model();
        Model_User::filter_response_records($auth_driver->get_user_id(), array($user_model));
        return Model_User::format_single_element(
            $user_model,
            // ログインコード
            array('credential'=>$auth_driver->get_login_credential(),
				'valuepush_alias' => str_pad($user_model->id, 20, "0", STR_PAD_LEFT))
        );
    }

    /**
     * Twitterアカウント連携
     *
     * @param int $twitter_id
     * @param string $oauth_token
     * @param string $oauth_token_secret
     * @return array
     * @throws Exception_Logic
     * @throws Exception_Service
     */
    /* public function signin($twitter_id, $oauth_token, $oauth_token_secret){
        if(!$this->auth_driver->is_authorized()){
            throw new Exception_Logic("The '" . __METHOD__ . "' only supported for authorized context.");
        }

        $user_id = $this->auth_driver->get_user_id();
        if($this->auth_driver->is_signin()){
            throw new Exception_Service("The user of reqiested singin (user_id: ${user_id}) is already signed in.", Constants_Code::ACCOUNT_ALREADY_SIGNIN);
        }

        $now_date = date('Y-m-d H:i:s');
        $account_model = Model_User_Account_Twitter::get_by_user_id($this->auth_driver->get_user_id());
        if(!$account_model){
            $account_model = Model_User_Account_Twitter::new_record(
                $twitter_id,
                $oauth_token,
                null,
                $oauth_token_secret
            );
            $account_model->user_id = $user_id;
        }elseif($twitter_id != $account_model->twitter_id){
            throw new Exception_Service("Already exists another account of 'Twitter'(id: " . $account_model->twitter_id . ").", Constants_Code::ACCOUNT_UNMATCH);
        }

        $this->auth_driver->set_account_model($account_model);
        $signin_model = Model_User_Signin::get_by_user_id_and_account_type($user_id, Model_User_Signin::ACCOUNT_TYPE_TWITTER);
        if(!$signin_model){
            $signin_model = Model_User_Signin::new_record($user_id, Model_User_Signin::ACCOUNT_TYPE_TWITTER);
        }
        $this->auth_driver->set_user_signin_model($signin_model);
        // サインインする
        $this->auth_driver->signin($twitter_id, $oauth_token, $oauth_token_secret, null, $now_date);
        $auth_driver = $this->check_authenticate_result();
        // 更新内容をqueへ追加する
        $this->add_list_to_save($auth_driver->get_all_model_list());
        $user_model = $auth_driver->get_user_model();
        Model_User::filter_response_records($auth_driver->get_user_id(), array($user_model));
        return Model_User::format_single_element(
            $user_model,
            // ログインコード
            array('credential'=>$auth_driver->get_login_credential())
        );
    } */

    /**
     * Twitter連携解除
     *
     * @param int $twitter_id
     * @return array
     * @throws Exception_Logic
     * @throws Exception_Service
     */
    /* public function signout($twitter_id){
        if(!$this->auth_driver->is_authorized()){
            throw new Exception_Logic("The '" . __METHOD__ . "' only supported for authorized context.");
        }

        $user_id = $this->auth_driver->get_user_id();
        $account_model = Model_User_Account_Twitter::get_by_user_id($this->auth_driver->get_user_id());
        if(!$account_model){
            throw new Exception_Service("", Constants_Code::TWITTER_ACCOUNT_NONEREGISTER);
        }

        if((string)$account_model->twitter_id != (string)$twitter_id){
            throw new Exception_Service("Suplied twitter_id(${twitter_id}) of the acccount unmatch with existent(twitter_id: " . $account_model->twitter_id . ").", Constants_Code::ACCOUNT_UNMATCH);
        }

        $this->auth_driver->set_twitter_id($twitter_id);
        if(!$this->auth_driver->is_signin()){
            throw new Exception_Service("The user of requested singout (user_id: ${user_id}) is not sign in target account.", Constants_Code::ACCOUNT_NOTSIGNIN);
        }

        // Twitterのみの1つしか連携を行っていない場合は、これ以上解除できない
        if($this->auth_driver->is_account_last_signin($this->auth_driver->get_user_signin_model(Model_User_Signin::ACCOUNT_TYPE_TWITTER))){
            throw new Exception_Service("Could not signout any more accounts. The 'Twitter' is last one.", Constants_Code::ACCOUNT_NEUTRAL_SIGNIN);
        }

        // サインアウトする
        $this->auth_driver->signout();
        // 更新内容をqueへ追加する
        $this->add_list_to_save($this->auth_driver->get_all_model_list());
        $user_model = $this->auth_driver->get_user_model();
        Model_User::filter_response_records($this->auth_driver->get_user_id(), array($user_model));
        return Model_User::format_single_element(
            $user_model,
            // ログインコード
            array('credential'=>$this->auth_driver->get_login_credential())
        );
    } */

    private function check_authenticate_result(){
        $auth_driver = $this->auth_driver;
        if(!$auth_driver->is_authorized()){
            if($auth_driver->is_verify_credentials_checked() && !$auth_driver->get_user_model()){
                // Twitterの認証情報に問題が無く、かつ、アカウントが未登録の場合、アカウント登録を促すエラーを発生させる
                throw new Exception_Service('The twitter user(twitter_id: ' . $auth_driver->get_twitter_id() . ') attempted login is not registered yet.', Constants_Code::TWITTER_ACCOUNT_NONEREGISTER);
            }

            throw new Exception_Service('Failed to login for-> twitter_id=' . $auth_driver->get_twitter_id(), Constants_Code::AUTH_TWITTER_FAILED);
        }

        return $auth_driver;
    }

    /**
     * 認証を開始要求を行い、TwitterログインページのURLを生成します。
     *
     * @return string
     * @throws Exception_Service
     */
    /* public function create_request_url($auth_type){
        $now_date = date('Y-m-d H:i:s');
        $auth_driver = $this->auth_driver;
        // 使い捨てトークンを発行する
        $onetimetoken_model = Model_Onetimetoken::save_unique_record(
            null,
            static::calculate_authorization_expired_at($now_date)
        );
        // TwitterOAuthにてPhpErrorExceptionが発生する可能性がある（Consumer関連の情報に誤りがある場合に限り）
        try{
            $auth_driver->set_onetimetoken_model($onetimetoken_model);
            $request_url = $auth_driver->get_request_url();
            // トークンパラメータに認証種別を追加する
            $onetimetoken_model->values = $auth_driver->issue_onetimetoken_values(array('auth_type'=>$auth_type));
            // トークンパラメータを保存する
            $onetimetoken_model->save();
            return $request_url;
        } catch (Exception $e) {
            // エラーになったら、トークンを消す
            $onetimetoken_model->delete();
            throw new Exception_Service(
                'Request of twitter authorization is failed. cused by: ' . $e->getMessage() .
                ' / trace: ' . $e->getTraceAsString(),
                Constants_Code::REQUEST_TWITTER_FAILED,
                $e
            );
        }
    } */

    /**
     * Twitter認証の終了確認を行い、リダイレクト先のURLを返却する。<br />
     * ※TwitterのOAuth認証のcallback_urlにて実行される処理です（Twitterからリダイレクトされます）。<br />
     * ※以下のパターンの認証終了確認を受け付けます：<br />
     * 1. 既存ユーザに対するログイン認証<br />
     * 2. 新規ユーザに対する登録処理前の認証<br />
     *
     * @param string $onetimetoken
     * @param string $oauth_token
     * @param string $oauth_verifier
     * @return string 次の処理へリダイレクトするURL
     */
    /* public function confirm_auth_create_redirect_url($onetimetoken, $oauth_token, $oauth_verifier){
        // 現在時刻
        $now_date = date('Y-m-d H:i:s');
        $onetimetoken_model = Model_Onetimetoken::get_as_onetime($onetimetoken, $now_date);
        if(!$onetimetoken_model){
            throw new Exception_Service('Expired authorization.', Constants_Code::TWITTER_AUTH_EXPIRED);
        }

        // 認証処理の開始（Twitterとの通信処理とトランザクション処理は単位を分離して実行します）
        try{
            $auth_driver = $this->auth_driver;
            $auth_driver->set_onetimetoken_model($onetimetoken_model);
            // Twitterトークン認証（通信）
            $auth_driver->connect_access_token($oauth_token, $oauth_verifier);
            // トランザクション処理を実行する
            return $this->method_as_transaction('confirm_auth_db_update', array($now_date));
        } catch (\AuthException $e) {
            throw new Exception_Service(
                'The authorization of twitter is failed. cused by: ' . $e->getMessage() .
                ' / trace: ' . $e->getTraceAsString(),
                Constants_Code::AUTH_TWITTER_FAILED,
                $e
            );
        }
    } */

    // TODO Twitter疎通とDB更新を別途の単位に分離する必要がある
    /**
     * Twitter認証終了確認のDB更新を行います。<br />
     * ※トランザクション処理を行って下さい。
     *
     * @param string $now_date
     * @return string 確認終了のURL
     * @throws Exception_Logic
     */
    protected function confirm_auth_db_update($now_date = null){
        $now_date = (is_null($now_date) ? date('Y-m-d H:i:s') : $now_date);
        $auth_driver = $this->auth_driver;
        $twitter_account_model = $auth_driver->get_account_model();
        if(!$twitter_account_model){
            // 新規ユーザの場合もこの段階でアカウント情報だけを作成する（新規登録処理の際に一致チェックする）
            $twitter_account_model = Model_User_Account_Twitter::forge();
            $twitter_account_model->twitter_id = $auth_driver->get_twitter_id();
        }
        // Twitter認証情報の更新
        $twitter_account_model->oauth_token = $auth_driver->get_oauth_token();
        $twitter_account_model->oauth_token_secret = $auth_driver->get_oauth_token_secret();
        $auth_driver->set_account_model($twitter_account_model);
        $is_new = is_null($twitter_account_model->user_id);
        $parsed_onetimetoken_values = $auth_driver->get_parsed_onetimetoken_values();
        $auth_type = (isset($parsed_onetimetoken_values['auth_type']) ? $parsed_onetimetoken_values['auth_type'] : null);
        // 新規登録の場合
        if($auth_type == static::AUTH_TYPE_NEW){
            // アカウント情報を更新queへ追加する
            $auth_driver->get_account_model()->save();
            // 既存ユーザ
            if(!$is_new){
                // 既存ユーザが意図的に「新規登録」ボタン等を押下した場合
                return Constants_Common::URL_SCHEME_PROTOCOL . '://' .
                    Constants_Common::URL_SCHEME_TWITTER_REGISTER .
                    '?result_code=' . Constants_Code::TWITTER_ALREADY_REGISTERED
                ;
            }

            // 新規登録画面のURLスキームを返却する
            return Constants_Common::URL_SCHEME_PROTOCOL . '://' .
                Constants_Common::URL_SCHEME_TWITTER_REGISTER .
                '?result_code=' . Constants_Code::SUCCESS .
                '&twitter_id=' . $auth_driver->get_twitter_id() .
                '&oauth_token=' . $auth_driver->get_oauth_token() .
                '&oauth_verifier=' . $auth_driver->get_oauth_verifier() .
                '&oauth_token_secret=' . $auth_driver->get_oauth_token_secret()
            ;
        }

        // 認証種別が新規でも既存でもない場合、エラー
        if($auth_type != static::AUTH_TYPE_EXISTENT){
            // 使い捨てトークンに保持したパラメータが正常なものであれば、このエラーは発生しない
            throw new Exception_Logic('Invalid auth_type(' . $auth_type . ').');
        }

        if($is_new){
            // 未登録ユーザが意図的に「既存アカウントでログイン」等のボタンを押下した場合に発生する
            return Constants_Common::URL_SCHEME_PROTOCOL . '://' .
                Constants_Common::URL_SCHEME_TWITTER_FINISH .
                '?result_code=' . Constants_Code::TWITTER_ACCOUNT_NONEREGISTER
            ;
        }

        // ログインさせる
        if(!$auth_driver->login(
            $auth_driver->get_twitter_id(),
            $auth_driver->get_oauth_token(),
            $auth_driver->get_oauth_token_secret(),
            $now_date
        )){
            // 以上の処理で設定したパラメータが正しければ、このエラーは発生しない
            throw new Exception_Logic('The login for the twitter account(twitter_id: ' . $auth_driver->get_twitter_id() . ') may not be failed.');
        }

        // その場で更新する
        $auth_driver->get_account_model()->save();
        $auth_driver->get_user_login_model()->save();
        Model_Base::save_all($auth_driver->get_user_signin_model_list());
        // ログイン完了処理のURLスキームを返却する
        return Constants_Common::URL_SCHEME_PROTOCOL . '://' .
            Constants_Common::URL_SCHEME_TWITTER_FINISH .
            '?result_code=' . Constants_Code::SUCCESS .
            '&3rdapp_credential=' . $auth_driver->get_login_credential() .
            '&twitter_id=' . $auth_driver->get_twitter_id() .
            '&oauth_token=' . $auth_driver->get_oauth_token() .
            '&oauth_verifier=' . $auth_driver->get_oauth_verifier()
        ;
    }

    /**
     * Twitter認証の有効期限を算出して返却します。
     *
     * @param string $registered_at
     * @return string
     */
    private static function calculate_authorization_expired_at($registered_at = null){
        return date('Y-m-d H:i:s', (strtotime($registered_at) + (static::AUTHORIZATION_EXPIRE_HOUR * 60 * 60)));
    }
}
