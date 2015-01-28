<?php
/**
 * Facebookアカウントに関する振る舞いを記述するクラス
 * @author k-kawaguchi
 */
class Domain_User_Account_Facebook extends Domain_Base{

    private $auth_driver;

    /**
     * コンストラクタ
     * @param Controller $context
     * @param Auth_Driver_Base_Account_Facebook $auth_driver
     * @throws Exception_Logic
     */
    public function __construct(Controller $context, Auth_Driver_Base_Account_Facebook $auth_driver){
        if(is_null($context) || is_null($auth_driver)){
            throw new Exception_Logic('Illegal argument.');
        }

        $this->auth_driver = $auth_driver;
        parent::__construct($context);
    }

    /**
     * Facebookアカウント新規登録のDB更新処理です。
     *
     * @param string $email
     * @param string $display_name
     * @param real $expires_in
     * @param string $registered_at
     * @return Auth_Driver_Base_Account_Facebook
     * @throws Exception_Service
     * @throws Exception_Logic
     */
	protected function register_account_db_update($expires_in = null, $registered_at = null,$nickname,$device_info,$os){
        $registered_at = (is_null($registered_at) ? date('Y-m-d H:i:s') : $registered_at);
        $auth_driver = $this->auth_driver;
        $facebook_id = (string)$auth_driver->get_facebook_id();
        $access_token = (string)$auth_driver->get_access_token();
        $facebook_account_model = $auth_driver->get_account_model();
        if($facebook_account_model){
            throw new Exception_Service("The facebook informations(facebook_id:${facebook_id} / access_token:" . $facebook_account_model->access_token . ") are already registered as the user(id: " . $facebook_account_model->user_id . ").", Constants_Code::FACEBOOK_ALREADY_REGISTERED);
        }

		// 新規にユーザ情報を作成する
        $user_model = Model_User::new_record(
	        		$nickname,
	        		Model_User::LOGIN_TYPE_FACEBOOK
	        		);
        $user_model->save();
        $user_id = $user_model->id;

		// アカウント情報の設定
        $auth_driver->set_account_model(
        	Model_User_Account_Facebook::new_record(
					$user_id,
					$facebook_id,
					$access_token,
					$expires_in,
					$registered_at
        ));

		// ログイン情報の設定
        $auth_driver->set_user_login_model(
            Model_User_Login::new_record(
            	$user_id,
            	null,
            	$registered_at
        ));

        // ログインさせる
        if(!$auth_driver->login(
            $facebook_id,
            $access_token,
            $expires_in,
            $registered_at
        )){
            // 以上の処理で設定したパラメータが正しければ、このエラーは発生しない
            throw new Exception_Logic('The login for the facebook account(facebook_id: ' . $auth_driver->get_facebook_id() . ') may not be failed.');
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
//        // 設定情報の登録
//        Model_User_Setting::new_record($user_id)->save();
        return $auth_driver;
    }

    /**
     * Facebookアカウントの新規登録処理です。
     *
     * @param string $email
     * @param string $display_name
     * @param real $facebook_id
     * @param string $access_token
     * @param real $expires_in
     * @return Auth_Driver_Base_Account_Facebook
     * @throws Exception_Service
     */
//    public function register_account($display_name, $facebook_id, $access_token, $expires_in = null){
    public function register_account($facebook_id, $access_token, $expires_in = null,$nickname,$device_info,$os){
        $auth_driver = $this->auth_driver;
        if(!$auth_driver->connect_check_me($facebook_id, $access_token)){
            throw new Exception_Service('Result of connect_check_me for the facebook user is failed. facebook_id=' . $facebook_id . ' / access_token=' . $access_token, Constants_Code::AUTH_FACEBOOK_FAILED);
        }

        // DBトランザクション
        $auth_driver = $this->method_as_transaction(
            'register_account_db_update',
            array(
                $expires_in,
                date('Y-m-d H:i:s'),
				$nickname,
				$device_info,
				$os
            )
        );
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
     * Facebookログイン
     *
     * @param real $facebook_id
     * @param string $access_token
     * @param real $expires_in
	 * @param string $device_info
     * @return Auth_Driver_Base_Account_Facebook
     * @throws Exception_Service
     */
    public function login($facebook_id, $access_token, $expires_in = null,$device_info,$os){
        $auth_driver = $this->auth_driver;

		if(is_null(Model_User_Account_Facebook::get_by_facebook_id($facebook_id))){
			throw new Exception_Service('The facebook user(facebook_id: ' . $facebook_id . ') attempted login is not registered yet.', Constants_Code::FACEBOOK_ACCOUNT_NONEREGISTER);
		}

        if(!$auth_driver->login($facebook_id,$access_token,$expires_in,date('Y-m-d H:i:s'))){
            if($auth_driver->is_me_checked() && !$auth_driver->get_user_model()){
                // Facebookの認証情報に問題が無く、かつ、アカウントが未登録の場合、アカウント登録を促すエラーを発生させる
                throw new Exception_Service('The facebook user(facebook_id: ' . $facebook_id . ') attempted login is not registered yet.', Constants_Code::FACEBOOK_ACCOUNT_NONEREGISTER);
            }

            throw new Exception_Service('Failed to login for-> facebook_id=' . $facebook_id, Constants_Code::AUTH_FACEBOOK_FAILED);
        }
		// ログイン履歴を残す
        $log_user_login_model = Model_Log_User_Login::new_record(
        		$auth_driver->get_user_id(),
        		$device_info,
				$os
        );
        $log_user_login_model->save();

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

}