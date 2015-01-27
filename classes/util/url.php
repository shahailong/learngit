<?php
/**
 * URLに関した汎用機能
 *
 * @author a-kido
 */
class Util_Url{

	/**
	 * ユーザープロフ用URLを返却します。
	 *
	 * @param string $user_id
	 * @param string $image_file_name
	 *
	 * @return string
	 * @throws Exception_Logic
	 */
	public static function get_profileimage_url($user_id = null, $image_file_name = null){
		if(is_null($user_id) || is_null($image_file_name)){
			return "";
		}

		return Config::get('media_cdn_url') .Config::get('s3_public_user_directory_name')."/" .$user_id ."/" .$image_file_name;

	}

	/**
	 * フォト画像用URLを返却します。
	 *
	 * @param string $photo_id
	 * @param string $image_file_name
	 *
	 * @return string
	 * @throws Exception_Logic
	 */
	public static function get_photoimage_url($photo_id = null, $image_file_name = null){
		if(is_null($photo_id) || is_null($image_file_name)){
			return "";
		}

		return Config::get('media_cdn_url') .Config::get('s3_public_photo_directory_name')."/" .$photo_id ."/" .$image_file_name;

	}

	/**
	 * おもフォト画像用URLを返却します。
	 *
	 * @param string $omophoto_id
	 * @param string $image_file_name
	 *
	 * @return string
	 * @throws Exception_Logic
	 */
	public static function get_omophotoimage_url($omophoto_id = null, $image_file_name = null){
		if(is_null($omophoto_id) || is_null($image_file_name)){
			return "";
		}

		return Config::get('media_cdn_url') .Config::get('s3_public_omophoto_directory_name')."/" .$omophoto_id ."/" .$image_file_name;

	}

}