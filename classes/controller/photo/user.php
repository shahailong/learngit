<?php
/**
 * ログイン状態におけるフォトに関するリクエストを受け付けるController
 * ※要認証
 *
 * @author a-kido
 */
class Controller_Photo_User extends Controller_Rest_Base_Authorized{

	/**
	 * 51：フォト登録 POST /photo/user/upload
	 */
	public function post_upload(){
		Validation_Base::init(__METHOD__);
		$photofile_info = Input::file('photofile', array());

		$domain = new Domain_Photo($this, $this->get_auth_driver());
		$this->response(
			$domain->photo_upload(
				$photofile_info
			)
		);
	}

}
