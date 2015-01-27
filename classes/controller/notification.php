<?php
/**
 * ログイン状態におけるお知らせに関するリクエストを受け付けるController
 * ※要認証
 *
 * @author a-kido
 */
class Controller_Notification extends Controller_Rest_Base_Authorized{

	/**
	 * 61：お知らせ一覧 GET /notification/list
	 *
	 * @since  phase2
	 */
	public function get_list(){
		Validation_Base::init(__METHOD__);
		$doamain = new Domain_Notification($this, $this->get_auth_driver());
		$this->response($doamain->get_list(
			Validation_Base::get_valid('need_num'),
			Validation_Base::get_valid('max_id')
		));
	}

	/**
	 * 62：お知らせの既読変更 POST /notification/read
	 *
	 * @since  phase2
	 */
	public function post_read(){
		Validation_Base::init(__METHOD__);
		$doamain = new Domain_Notification($this, $this->get_auth_driver());
		$doamain->mark_read(
			Validation_Base::get_valid('notification_ids'),
			Validation_Base::get_valid('all_read_flg')
		);
		$this->response();
	}

	/**
	 * 63：お知らせ未読有無 GET /notification/check_unread
	 *
	 * @since  phase3
	 */
	public function get_check_unread(){
		Validation_Base::init(__METHOD__);
		$doamain = new Domain_Notification($this, $this->get_auth_driver());
		$this->response($doamain->check_unread());
	}
}