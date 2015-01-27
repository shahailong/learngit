<?php
/**
 * おもフォトに関するリクエストを受け付けるController
 *
 * @author a-kido
 */
class Controller_Omophoto extends Controller_Rest_Base{

	 /**
	 * 21：おもフォト一覧(人気順) GET /omophoto/ranking
	 */
	public function get_ranking(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$target_at = Validation_Base::get_valid('target_at');
		$user_id = null;
		$type = Validation_Base::get_valid('type');
		$official_flg = null;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_ranking($need_num, $target_at, $max_id, $type, $user_id, $official_flg, $no_cache));
	}

	 /**
	 * 22：指定ユーザーが投稿したおもフォト一覧(人気順) GET /omophoto/ranking_user
	 */
	public function get_ranking_user(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$target_at = Validation_Base::get_valid('target_at');
		$user_id = Validation_Base::get_valid('user_id');
		$type = null;
		$official_flg = null;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_ranking($need_num, $target_at, $max_id, $type, $user_id, $official_flg, $no_cache));
	}

	/**
	 * 23：おもフォト一覧(新着順) GET /omophoto/list
	 */
	public function get_list(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id = null;
		$photo_id = null;
		$official_flg = null;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_list($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache));
	}

	/**
	 * 24：指定ユーザーが投稿したおもフォト一覧(新着順) GET /omophoto/list_user
	 */
	public function get_list_user(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id = Validation_Base::get_valid('user_id');
		$photo_id = null;
		$official_flg = null;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_list($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache));
	}

	/**
	 * 25：指定ユーザーが評価したおもフォト一覧(新着順) GET /omophoto/list_user_appraised
	 *
	 * @since  phase2
	 */
	public function get_list_user_appraised(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id = Validation_Base::get_valid('user_id');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_user_appraised_list($need_num, $max_id, $user_id));
	}

	 /**
	 * 26：指定おもフォトへの評価者一覧(新着順) GET /omophoto/appraisers
	 */
	public function get_appraisers(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$omophoto_id = Validation_Base::get_valid('omophoto_id');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_appraisers($need_num, $max_id, $omophoto_id));
	}

	/**
	 * 27：指定フォトのおもフォト一覧(新着順) GET /omophoto/list_photo
	 *
	 * @since  phase3
	 */
	public function get_list_photo(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id = null;
		$photo_id = Validation_Base::get_valid('photo_id');
		$official_flg = null;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_list($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache));
	}

	/**
	 * 28：芸人が投稿したおもフォト一覧(新着順) GET /omophoto/list_officialuser
	 *
	 * @since  phase3
	 */
	public function get_list_officialuser(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$user_id = null;
		$photo_id = Validation_Base::get_valid('photo_id');
		$official_flg = Validation_Base::get_valid('official_flg');
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_list($need_num, $max_id, $user_id, $photo_id, $official_flg, $no_cache));
	}

	/**
	 * 29：芸人が投稿したおもフォト一覧(人気順) GET /omophoto/ranking_officialuser
	 *
	 * @since  phase3
	 */
	public function get_ranking_officialuser(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$target_at = Validation_Base::get_valid('target_at');
		$user_id = null;
		$official_flg = Validation_Base::get_valid('official_flg');
		$type = null;
		$no_cache = Validation_Base::get_valid('no_cache');
		$domain = new Domain_Omophoto($this, $this->get_auth_driver());
		$this->response($domain->get_omophoto_ranking($need_num, $target_at, $max_id, $type, $user_id, $official_flg, $no_cache));
	}
}