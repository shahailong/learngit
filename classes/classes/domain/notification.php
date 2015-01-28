<?php
/**
 * お知らせに関する振る舞いを記述するクラス
 *
 * @author a-kido
 */
class Domain_Notification extends Domain_Base{

	private $auth_driver = null;

	/**
	 * コンストラクタ
	 * @param Controller_Rest_Base $context
	 * @param Auth_Driver_Base $auth_driver
	 */
	public function __construct(Controller $context, Auth_Driver_Base $auth_driver){
		parent::__construct($context);
		if(is_null($auth_driver) || !$auth_driver->is_authorized()){
			throw new Exception_Logic('Failed to construct ' . __CLASS__ . ', suplied Auth_Driver_Base is illegal state.');
		}

		$this->auth_driver = $auth_driver;
	}

	/**
	 * 「お知らせ」一覧取得
	 *
	 * @param $need_num 取得件数
	 * @param $max_id
	 * @return array
	 * @throws Exception_Logic
	 */
	public function get_list($need_num,$max_id = null){
		$executor_user_id = (int)$this->auth_driver->get_user_id();
		return Model_User_Notification::notification_lists($need_num,$max_id,$executor_user_id);
	}

	/**
	 * 「お知らせ」既読切り替え
	 *
	 * @param array $notification_ids
	 * @return array
	 * @throws Exception_Logic
	 */
	public function mark_read($notification_ids=NULL,$all_read_flg){
		$executor_user_id = (int)$this->auth_driver->get_user_id();
		if($all_read_flg == 0 && (is_null($notification_ids) || empty($notification_ids))){
			throw new Exception_Service("read notification_ids is not found.", Constants_Code::PARAM_INVALID);
		}
		Model_User_Notification::notification_read($notification_ids,$all_read_flg,$executor_user_id);
		return;
	}

	/**
	 * 「お知らせ」未読件数取得
	 *
	 * @return array
	 * @throws Exception_Logic
	 */
	public function check_unread(){
		$result = array();
		$executor_user_id = (int)$this->auth_driver->get_user_id();
		$count = Model_User_Notification::get_count_unread($executor_user_id);
		$result['unread_count'] = $count;

		return $result;
	}

}