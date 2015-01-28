<?php
/**
 * フォトに関する振る舞いを記述するクラス
 *
 * @author a-kido
 */
class Domain_Photo extends Domain_Base{

	private $auth_driver = null;

	/**
	 * コンストラクタ
	 * @param Controller_Rest_Base $context
	 * @param Auth_Driver_Base $auth_driver
	 */
	public function __construct(Controller $context, Auth_Driver_Base $auth_driver){
		parent::__construct($context);
		/* if(is_null($auth_driver) || !$auth_driver->is_authorized()){
			throw new Exception_Logic('Failed to construct ' . __CLASS__ . ', suplied Auth_Driver_Base is illegal state.');
		} */

		$this->auth_driver = $auth_driver;
	}

	/**
	 * 画像の転送とDB更新を同一の単位で実行します。
	 *
	 * @param array $photo_fileinfo
	 * @param String $tmpfile_path
	 *
	 * @return Model_User_Photo
	 * @throws Exception_Service
	 */
	protected function upload_photo_db_update(array $photo_fileinfo, $tmpfile_path){
		$user_id = $this->auth_driver->get_user_id();

		// 拡張子
		$extension = Util_Image::get_extension($photo_fileinfo['tmp_name'], $photo_fileinfo['name']);

		// 画像サイズ
		$sizes = Util_Image::get_image_sizes($tmpfile_path);

		// ファイル名
		$file_name = date("YmdHis") ."." .$extension;

		// DB新規登録
		$photo_record = Model_Photo::new_record(
				$user_id,
				$file_name,
				$photo_fileinfo['size'],
				$sizes->width,
				$sizes->height
		);
		$photo_record->save();

		$record_directory_key = Config::get('s3_public_photo_directory_name') ."/" .$photo_record->id ."/" .$photo_record->image_file_name;

		// S3に転送
		$result = Util_Image::image_forward_to_s3($record_directory_key,$tmpfile_path);

		$photo_record->executor_user_id = $user_id;
		return $photo_record;
	}

	/**
	 * フォト(ネタ画像)のアップロード<br />
	 *
	 * @param array $photofile_info アップロードされたファイル情報
	 *
	 * @return array
	 * @throws Exception_Service
	 */
	public function photo_upload(array $photo_fileinfo = array()){

		if(!$photo_fileinfo){
			throw new Exception_Service("Input 'photofile' file is not found.", Constants_Code::UPLOAD_IMAGE_NOTFOUND);
		}

		// フォト画像一時保存パスの取得
		$tmpfile_path = Util_Image::get_photo_temp_file_path($photo_fileinfo);

			// ファイル転送・DB登録
			$photo_record = $this->method_as_transaction(
			'upload_photo_db_update',
				array(
					$photo_fileinfo,
					$tmpfile_path
				)
			);

		// レスポンス内容に追加情報を付加する
		Model_Photo::filter_response_records($this->auth_driver->get_user_id(), array($photo_record->id=>$photo_record));

		// レスポンス内容をフォーマットする
		return Model_Photo::format_single_element($photo_record);
	}

	 /**
	 * フォト一覧(新着順)
	 *
	 * @param int $need_num
	 * @param int $max_id
	 * @param int $user_id
	 * @param int $no_cache
	 *
	 * @return Model_Photo
	 * @throws Exception_Paramerror
	 */
	public function get_photo_list($need_num, $max_id, $user_id, $no_cache){
		return Model_Photo::photo_lists($need_num, $max_id, $user_id, $no_cache);
	}

}