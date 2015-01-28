<?php

/**
 * 認証済アカウントに関する振る舞いを記述するクラス
 *
 * @author k-kawaguchi
 */
class Domain_User_Account extends Domain_Base {

    private $auth_driver = null;

    /**
     * コンストラクタ
     * @param Controller_Rest_Base $context
     * @param Auth_Driver_Base $auth_driver
     */
    public function __construct(Controller $context, Auth_Driver_Base $auth_driver) {
        parent::__construct($context);
        if (is_null($auth_driver) || !$auth_driver->is_authorized()) {
            throw new Exception_Logic('Failed to construct ' . __CLASS__ . ', suplied Auth_Driver_Base is illegal state.');
        }

        $this->auth_driver = $auth_driver;
    }

    /**
     * 実行ユーザの設定情報を取得します。
     *
     * @return array
     */
    public function get_info(){
    	$user_model = $this->auth_driver->get_user_model();
    	$setting = Model_User::filter_single_response_record(array($user_model));
    	return Model_User::format_single_element($setting);
    }

    /**
     * ログアウト
     */
    public function logout() {
        $auth_driver = $this->auth_driver;
        $auth_driver->logout(date('Y-m-d H:i:s'));
        $this->add_to_save($auth_driver->get_user_login_model());
        //$this->add_list_to_save($auth_driver->get_user_signin_model_list());
    }

    /**
     * 退会処理を行います
     *
     * @return
     * @throws Exception_Service
     */
    protected function retire_db_update() {
        $action_user_id = $this->auth_driver->get_user_id();


        //ユーザーマスターロック
        $user_data = Model_User::lock_user_data($action_user_id);

        if (!$user_data) {
            throw new Exception_Service("The real data of the user_id(user_id: ${$action_user_id}) is not found.", Constants_Code::TARGET_USER_NOTFOUND);
        }

        //ユーザーマスター更新
        DB::update(Model_User::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('id', '=', $action_user_id)
                ->execute();

        //プロフィール写真データ更新
        DB::update(Model_User_Profileimage::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'image_delete_status' => 1,
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('user_id', '=', $action_user_id)
                ->where('delete_flg', '=', 0)
                ->execute();

        //退会ユーザーが投稿したフォト設定データ更新
        DB::update(Model_Photo::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'image_delete_status' => 1,
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('user_id', '=', $action_user_id)
                ->where('delete_flg', '=', 0)
                ->execute();

        //退会ユーザーが投稿したおもフォト設定データ更新
        DB::update(Model_Omophoto::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'image_delete_status' => 1,
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('user_id', '=', $action_user_id)
                ->where('delete_flg', '=', 0)
                ->execute();

        //退会ユーザーが投稿したフォトに紐づくおもフォト設定データ更新
        $photo_datas = DB::select('id')
                ->from(Model_Photo::table())
                ->where('user_id', '=', $action_user_id)
                ->execute();

        $user_photo_data[] = NULL;
        foreach ($photo_datas as $photo_data) {
            $user_photo_data[] = $photo_data['id'];
        }

        DB::update(Model_Omophoto::table())
                ->set(array(
                    'delete_flg' => 3,
                    'delete_at' => DB::expr('NOW()'),
                    'image_delete_status' => 1,
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('photo_id', 'IN', $user_photo_data)
                ->where('delete_flg', '=', 0)
                ->execute();

		//退会ユーザーのログインタイプを取得
        $user_data = DB::select('login_type')
                ->from(Model_User::table())
                ->where('id', '=', $action_user_id)
                ->execute();

		switch ($user_data[0]['login_type']) {
			case 1:
				DB::update(Model_User_Account_Original::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('user_id', '=', $action_user_id)
                ->execute();

				break;
			case 2:
				DB::update(Model_User_Account_Facebook::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('user_id', '=', $action_user_id)
                ->execute();

				break;

			case 3:
				DB::update(Model_User_Account_Twitter::table())
                ->set(array(
                    'delete_flg' => 9,
                    'delete_at' => DB::expr('NOW()'),
                    'updated_at' => DB::expr('NOW()')
                ))
                ->where('user_id', '=', $action_user_id)
                ->execute();

				break;
		}

    }

	/**
     * Push通知の設定を変更します
     *
     * @param string $push_flg
     * @throws Exception_Service
     */
    protected function user_push_db_update($push_flg=NULL){
        $result = array();
        $user_id = $this->auth_driver->get_user_id();

        //push通知設定
        DB::update(Model_User::table())
                ->set(array(
                            'push_flg'=>$push_flg,
                            'updated_at'=>DB::expr('NOW()')
            ))
                ->where('id', '=', $user_id)
                ->execute();

        $result['push_flg'] = $push_flg;
        $result['valuepush_alias'] = str_pad($user_id, 20, "0", STR_PAD_LEFT);

        return $result;
    }


     /**
     * Push通知設定変更
     *
     * @param string $push_flg
     * @throws Exception_Service
     */
    public function modify_push($push_flg){

        // pushフラグ設定変更
        $result = $this->method_as_transaction(
        'user_push_db_update',
        array(
            $push_flg
            )
        );

        return $result;
    }

	 /**
     * プロフィール画像登録・変更・削除
     *
     * @param array $imagefile
     * @param int $imagefile_delete_flg
     * @param int $tmpfile_path
     * @throws Exception_Service
     */
    public function user_profileimage_db_update($imagefile,$imagefile_delete_flg,$tmpfile_path){
		$user_id = $this->auth_driver->get_user_id();

		$user_profileimage_data = Model_User_Profileimage::lock_user_data($user_id);
		if($imagefile_delete_flg == 1 || $imagefile_delete_flg == 0 && !is_null($user_profileimage_data)){
			//$imagefile_delete_flgが1の時、削除しようとしているデータが存在しない場合
			if(is_null($user_profileimage_data)){
	          throw new Exception_Service("The profileimage data of the user_id(user_id: ${user_id}) is not found.", Constants_Code::TARGET_USER_PROFILEIMAGE_NOTFOUND);
			}

			//ユーザーマスターの更新
			DB::update(Model_User_Profileimage::table())
				->set(array(
					'delete_flg'=> 1,
					'delete_at'=>DB::expr('NOW()'),
					'image_delete_status'=> 1,
					'updated_at'=>DB::expr('NOW()')

				))
				->where('id', '=', $user_profileimage_data['id'])
				->execute();
		}

		if($imagefile_delete_flg == 0){

			// 拡張子
			$extension = Util_Image::get_extension($imagefile['tmp_name'], $imagefile['name']);

			// 画像サイズ
			$sizes = Util_Image::get_image_sizes($tmpfile_path);

			// ファイル名
			$file_name = date("YmdHis") ."." .$extension;

        	$user_profileimage_record = Model_User_Profileimage::new_record(
        			$user_id,
					$file_name,
					$imagefile['size'],
					$sizes->width,
					$sizes->height
			);

        	$user_profileimage_record->save();


			$record_directory_key = Config::get('s3_public_user_directory_name') ."/" .$user_id ."/" .$user_profileimage_record->image_file_name;
	        // S3に転送
		    $result = Util_Image::image_forward_to_s3($record_directory_key,$tmpfile_path);

		}

		return;

    }

	/**
     * プロフィール画像登録・変更・削除
     *
     * @param array $imagefile
     * @param int $imagefile_delete_flg
     * @throws Exception_Service
     */
    public function modify_profileimage($imagefile=NULL,$imagefile_delete_flg){

		$tmpfile_path = NULL;

		if($imagefile_delete_flg == 0){
			if(!$imagefile){
				throw new Exception_Service("Input 'imagefile' file is not found.", Constants_Code::UPLOAD_IMAGE_NOTFOUND);
			}
			// プロフィール画像一時保存パスの取得
			$tmpfile_path = Util_Image::get_photo_temp_file_path($imagefile);
		}

		// ファイル転送・DB登録
        $user_profileimage_record = $this->method_as_transaction(
			'user_profileimage_db_update',
            array(
                $imagefile,
                $imagefile_delete_flg,
				$tmpfile_path
            )
        );

        // 最新のユーザー情報取得
        $user_model = $this->auth_driver->get_user_model();
        $setting = Model_User::filter_single_response_record(array($user_model));
        return Model_User::format_single_element($setting);
    }

    /**
     * 退会
     */
    public function retire() {

        // 退会
        $this->method_as_transaction('retire_db_update', array());

        return;
    }

}
