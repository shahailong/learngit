<?php
/**
 * フォトに関するリクエストを受け付けるController
 *
 * @author a-kido
 */
class Controller_Photo extends Controller_Rest_Base{

	/**
	 * 41：フォト一覧(新着順) GET /photo/list
	 */
	public function get_list(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id=NULL;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Photo($this, $this->get_auth_driver());
		$this->response($domain->get_photo_list($need_num, $max_id, $user_id, $no_cache));
   }

	/**
	 * 42：指定ユーザーが投稿したフォト一覧(新着順) GET /photo/list_user
	 */
	public function get_list_user(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id = Validation_Base::get_valid('user_id');
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Photo($this, $this->get_auth_driver());
		$this->response($domain->get_photo_list($need_num, $max_id, $user_id, $no_cache));
   }
}