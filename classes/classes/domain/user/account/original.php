<?php
/**
 * 独自アカウントに関する振る舞いを記述するクラス
 * @author k-kawaguchi
 */
class Domain_User_Account_Original extends Domain_Base{

    //const EMAIL_CONFIRM_EXPIRE_HOUR = 24;

    private $auth_driver;

    /**
     * コンストラクタ
     * @param type $id
     */
    public function __construct(Controller $context, Auth_Driver_Base_Account_Original $auth_driver){
        if(is_null($context) || is_null($auth_driver)){
            throw new Exception('Illegal argument.');
        }

        $this->auth_driver = $auth_driver;
        parent::__construct($context);
    }

    /**
     * 独自アカウントの登録を行います。<br />
     * ※登録処理完了後、該当のユーザをログインさせます。<br />
     *
     * @param string $login_id
     * @param string $password
     * @param string $nickname
     *
     * @return Auth_Driver_Base_Account_Original
     * @throws Exception_Service
     */
    protected function register_account($login_id, $password, $nickname, $device_info,$os){

    	$user_model = Model_User_Account_Original::get_by_login_id($login_id);

        $is_new_user = is_null($user_model);
        // 独自アカウントのlogin_id重複エラー
        if(!$is_new_user){
            throw new Exception_Service("The login_id '${login_id}' is already used.", Constants_Code::LOGINID_CONFLICT);
        }

        // 登録日時
        $registered_at = date('Y-m-d H:i:s');

        // 認証ドライバの取得
        $auth_driver = $this->auth_driver;

        // 新規にユーザ情報を作成する
        $user_model = Model_User::new_record(
	        		$nickname,
	        		Model_User::LOGIN_TYPE_ORIGINAL
	        		);
        $user_model->save();
        $user_id = $user_model->id;

        // ユーザ情報の設定
        $auth_driver->set_user_model($user_model);
        $auth_driver->set_login_id($login_id);
        $auth_driver->set_password_source($password);
        $auth_driver->set_registered_at($registered_at);

        // アカウント情報の設定
        $auth_driver->set_account_model(
        	Model_User_Account_Original::new_record(
	            $user_id,
	        	$login_id,
	            $auth_driver->get_password_hash(),
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
        if(!$auth_driver->login($login_id, $password, $registered_at)){
            // 上で設定した情報に誤りがなければ、このエラーは発生しない
            throw new Exception_Logic("Failed to login for new created account.");
        }

        // ログイン履歴を残す
        $log_user_login_model = Model_Log_User_Login::new_record(
        		$user_id,
        		$device_info,
				$os
        );
        $log_user_login_model->save();

        // 認証ドライバの状態をDBへ保存する
        Model_Base::save_all($auth_driver->get_all_model_list());

        // ユーザ情報を取得
        $user_model = $auth_driver->get_user_model();

		// 戻り値整形
        Model_User::filter_response_records($auth_driver->get_user_id(), array($user_model));
        return Model_User::format_single_element(
            $user_model,
            array('credential'=>$auth_driver->get_login_credential(),
                  'valuepush_alias' => str_pad($user_model->id, 20, "0", STR_PAD_LEFT)
        ));
    }


    /**
     * ユーザをログインさせます。
     *
     * @param string $login_id
     * @param string $password_source
     * @return Auth_Driver_Base_Account_Original
     * @throws Exception_Service
     */
    public function login($login_id, $password_source, $device_info,$os){
        $login_at = date('Y-m-d H:i:s');
        $auth_driver = $this->auth_driver;

        if(!$auth_driver->login($login_id, $password_source, $login_at)){
            throw new Exception_Service("The input for login is not correct. login_id=${login_id} / password=****", Constants_Code::LOGIN_INPUT_WRONG);
        }

        // ログイン履歴を残す
        $log_user_login_model = Model_Log_User_Login::new_record(
        		$auth_driver->get_user_id(),
        		$device_info,
				$os
        );
        $log_user_login_model->save();

        // 認証ドライバの状態を更新queへ保持させる
        $this->add_list_to_save($auth_driver->get_all_model_list());
        $user_model = $auth_driver->get_user_model();
        Model_User::filter_response_records($auth_driver->get_user_id(), array($user_model));
        return Model_User::format_single_element(
        	$user_model,
        	array('credential'=>$auth_driver->get_login_credential(),
        	      'valuepush_alias' => str_pad($user_model->id, 20, "0", STR_PAD_LEFT)
        ));
    }

    /**
     * パスワードを変更します
     *
     * @param int $prepassword
     * @param int $postpassword
     * @return Model_User_Omophoto
     * @throws Exception_Service
     */
    protected function password_db_update($prepassword,$postpassword){
        $user_id = $this->auth_driver->get_user_id();

//        テーブルのロック
//        $appraisal_data = Model_Omophoto_Appraisal::get_omophoto_appraisals_data($omophoto_id,$user_id);

        DB::update(Model_User_Account_Original::table())
            ->set(array(
                        'password'=>$postpassword,
                        'updated_at'=>DB::expr('NOW()')
            ))
            ->where('password', '=', $prepassword)
            ->where('user_id', '=', $user_id)
            ->execute()
            ;

//        return $omophoto_data;
    }

    /**
     * パスワード変更
     *
     * @param string $old
     * @param string $new
     * @param string $confirm
     * @throws Exception_Service
     */
    public function modify_password($old, $new, $confirm){
        $auth_driver = $this->auth_driver;

        if((string)$new != (string)$confirm){
            throw new Exception_Service("'new' unmatch with 'confirm'.", Constants_Code::PASSWORD_WRONG);
        }

        if(!$auth_driver->is_current_password_match($old)){
            throw new Exception_Service("'old' unmatch with current password.", Constants_Code::PASSWORD_UNMATCH);
        }

        if((string)$old == (string)$new){
            throw new Exception_Service("'old' and 'new' can not input same character.", Constants_Code::PASSWORD_CONFLICT);
        }

        $original_account_model = $auth_driver->get_account_model();
        $prepassword = $original_account_model->password;

        //パスワードをセット
        $auth_driver->set_password_source($new);
        //ハッシュ化したパスワード
        $postpassword = $auth_driver->get_password_hash();

        // パスワードの変更
        $this->password_db_update(
                $prepassword,
                $postpassword
        );

//        $original_account_model = $auth_driver->get_account_model();

//        $this->add_to_save($original_account_model);
    }

}