<?php
/**
 * おもフォトに関する振る舞いを記述するクラス
 *
 * @author m.yoshitake
 */
class Domain_Omophoto extends Domain_Base{

	private $auth_driver = null;

	/**
	 * コンストラクタ
	 * @param Controller_Rest_Base $context
	 * @param Auth_Driver_Base $auth_driver
	 */
	public function __construct(Controller $context, Auth_Driver_Base $auth_driver){
		parent::__construct($context);
//		if(is_null($auth_driver) || !$auth_driver->is_authorized()){
//			throw new Exception_Logic('Failed to construct ' . __CLASS__ . ', suplied Auth_Driver_Base is illegal state.');
//		}

		$this->auth_driver = $auth_driver;
	}

	/**
	 * 画像の転送とDB更新を同一の単位で実行します。
	 *
	 * @param array $photo_fileinfo
	 * @param String $tmpfile_path
	 * @param Int $photo_id
	 * @param Char $title
	 *
	 * @return Model_User_Photo
	 * @throws Exception_Service
	 */
	protected function upload_photo_db_update(array $omophotofile, $tmpfile_path, $photo_id, $title){
		$user_id = $this->auth_driver->get_user_id();

		$original_photo_data = Model_Photo::get_by_id($photo_id);

		if(!$original_photo_data){
		  throw new Exception_Service("omophoto original image is not found.", Constants_Code::OMOFHOTO_PHOTO_NOTFOUND);
		}

		// 拡張子
		$extension = Util_Image::get_extension($omophotofile['tmp_name'], $omophotofile['name']);

		// 画像サイズ
		$sizes = Util_Image::get_image_sizes($tmpfile_path);

		// ファイル名
		$file_name = date("YmdHis") ."." .$extension;

		// DB新規登録
		$omophoto_record = Model_Omophoto::new_record(
				$user_id,
				$photo_id,
				$file_name,
				$title,
				$omophotofile['size'],
				$sizes->width,
				$sizes->height
		);
		$omophoto_record->save();

		// DB新規登録
		$Notification_record = Model_User_Notification::new_record(
						$original_photo_data->user_id,
						'1',
						$user_id,
						$photo_id,
						$omophoto_record->id

		);
		$Notification_record->save();

		$record_directory_key = Config::get('s3_public_omophoto_directory_name') ."/" .$omophoto_record->id ."/" .$omophoto_record->image_file_name;
		// S3に転送
		$result = Util_Image::image_forward_to_s3($record_directory_key,$tmpfile_path);

		$omophoto_record->excutor_user_id = $user_id;
		return $omophoto_record;
	}

	/**
	 * フォト(ネタ画像)のアップロード<br />
	 *
	 * @param array $omophotofile おもフォトファイル
	 * @param int $photo_id フォトID
	 * @param char $title おもフォトタイトル
	 *
	 * @return array
	 * @throws Exception_Service
	 */
	public function omophoto_upload(array $omophotofile = array(),$photo_id=null,$title = null){

		if(!$omophotofile){
			throw new Exception_Service("Input 'omophotofile' file is not found.", Constants_Code::UPLOAD_IMAGE_NOTFOUND);
		}

		// フォト画像一時保存パスの取得
		$tmpfile_path = Util_Image::get_photo_temp_file_path($omophotofile);

			// ファイル転送・DB登録
			$photo_record = $this->method_as_transaction(
			'upload_photo_db_update',
				array(
					$omophotofile,
					$tmpfile_path,
					$photo_id,
					$title
				)
			);
		// レスポンス内容に追加情報を付加する
		Model_Omophoto::filter_response_records($this->auth_driver->get_user_id(), array($photo_record->id=>$photo_record));

		// レスポンス内容をフォーマットする
		return Model_Omophoto::format_single_element($photo_record);
	}

	 /**
	 * おもフォトに評価ポイントを挿入、または更新します。
	 *
	 * @param int $omophoto_id
	 * @param int $points
	 *
	 * @return Model_User_Omophoto
	 * @throws Exception_Service
	 */
	protected function point_omophoto_db_update($omophoto_id,$points){
		$user_id = $this->auth_driver->get_user_id();

		$omophoto_data = Model_Omophoto::get_by_id($omophoto_id);

		if(!$omophoto_data){
		  throw new Exception_Service("The real data of the omophoto(omophoto_id: ${omophoto_id}) is not found.", Constants_Code::OMOFHOTO_NOTFOUND);
		}

		$appraisal_data = Model_Omophoto_Appraisal::get_omophoto_appraisals_data($omophoto_id,$user_id);

		if(empty($appraisal_data)){
			// 評価データ挿入
			$omophoto_record = Model_Omophoto_Appraisal::new_record(
							$omophoto_id,
							$omophoto_data->user_id,
							$user_id,
							$points
			);
			$omophoto_record->save();

			// お知らせ挿入
			$Notification_record = Model_User_Notification::new_record(
							$omophoto_data->user_id,
							'2',
							$user_id,
							NULL,
							$omophoto_id

			);
			$Notification_record->save();

		}else{

			DB::update(Model_Omophoto_Appraisal::table())
				->set(array(
							'points'=>$points,
							'updated_at'=>DB::expr('NOW()')
			))
				->where('omophoto_id', '=', $omophoto_id)
				->where('action_user_id', '=', $user_id)
				->execute();

		}

		return $omophoto_data;
	}

	 /**
	 * ユーザーマスターの更新・ランクの昇格を行います
	 *
	 * @param int $user_id
	 *
	 * @return Model_User_Omophoto
	 * @throws Exception_Service
	 */
	protected function user_rank_db_update($user_id){
		$action_user_id = $this->auth_driver->get_user_id();

		$user_data = Model_User::lock_user_data($user_id);

		if(!$user_data){
		  throw new Exception_Service("The real data of the user_id(user_id: ${user_id}) is not found.", Constants_Code::TARGET_USER_NOTFOUND);
		}

		$points = Model_Omophoto_Appraisal::get_total_points_by_user($user_id);

		$user_rank = Model_Rank::get_point_rank($points);

		//ランク比較
		if($user_data[0]['rank_id'] != $user_rank->id){

			if(Model_Rank::is_upgrade($user_data[0]['rank_id'],$user_rank->id)){

				//ユーザーマスターの更新
				DB::update(Model_User::table())
				->set(array(
				'rank_id'=> $user_rank->id,
				'updated_at'=>DB::expr('NOW()')
				))
				->where('id', '=', $user_id)
				->execute();

				// ランク更新ログ挿入
				$omophoto_record = Model_Log_User_Rank::new_record(
						$user_id,
						$user_data[0]['rank_id'],
						$user_rank->id
				);
				$omophoto_record->save();

				// お知らせ挿入
				$Notification_record = Model_User_Notification::new_record(
						$user_id,
						'3',
						NULL,
						NULL,
						NULL

				);
				$Notification_record->save();

			}
		}

		return $points;
	}

	 /**
	 * おもフォトへのいいね
	 *
	 * @param int $omophoto_id
	 * @param int $points
	 *
	 * @return int points
	 * @throws Exception_Paramerror
	 */
	public function omophoto_appraised($omophoto_id,$points){

			// 評価データの登録・更新
			$omophoto_record = $this->method_as_transaction(
			'point_omophoto_db_update',
				array(
					$omophoto_id,
					$points
				)
			);

			// ユーザーマスターの更新・ランク更新ログの挿入
			$user_record = $this->method_as_transaction(
			'user_rank_db_update',
				array(
					$omophoto_record->user_id
				)
			);

			$result = array('points' => $points);

	   return $result;
	}

	/**
	 * おもフォト一覧(新着順)
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $photo_id
	 * @param int $official_flg
	 * @param int $no_cache
	 *
	 * @return Model_Omophoto
	 * @throws Exception_Paramerror
	 */
	public function get_omophoto_list($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache){
		return Model_Omophoto::omophoto_lists($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache);
	}

	/**
	 * おもフォト一覧(人気順)
	 *
	 * @param int $need_num
	 * @param int $target_at
	 * @param int $max_id
	 * @param int $type
	 * @param int $user_id
	 * @param int $official_flg
	 * @param int $no_cache
	 *
	 * @return Model_Omophoto_*
	 * @throws Exception_Paramerror
	 */
	public function get_omophoto_ranking($need_num, $target_at, $max_id, $type, $user_id, $official_flg, $no_cache){
		switch ($type){
			case 1:
			case 2:
			case 3:
				return Model_Omophoto_Ranking_Term::omophoto_ranking($need_num, $target_at, $max_id, $type, $user_id, $official_flg, $no_cache);
				break;
			default :
				return Model_Omophoto_Ranking_Total::omophoto_ranking($need_num, $target_at, $max_id, $user_id, $official_flg, $no_cache);
				break;
		}
	}

	/**
	 * 指定ユーザーが評価したおもフォト一覧(評価順)
	 *
	 * @param int $need_num
	 * @param int $omophoto_id
	 * @param int $max_id
	 * @return Model_Omophoto_Appraisal
	 * @throws Exception_Paramerror
	 */
	public function get_omophoto_user_appraised_list($need_num, $max_id, $user_id){
		return Model_Omophoto_Appraisal::omophoto_user_appraised_list($need_num,$max_id,$user_id);
	}

	/**
	 * 指定おもフォトへの評価者一覧(新着順)
	 *
	 * @param int $need_num
	 * @param int $omophoto_id
	 * @param int $max_id
	 * @return Model_Omophoto_Appraisal
	 * @throws Exception_Paramerror
	 */
	public function get_omophoto_appraisers($need_num, $max_id, $omophoto_id){
	   return Model_Omophoto_Appraisal::appraiser_lists($need_num, $max_id, $omophoto_id);
	}

}