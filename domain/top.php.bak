<?php
/**
 * トップに関する振る舞いを記述するクラス
 *
 * @author m.yoshitake
 */
class Domain_Top extends Domain_Base{

	const GET_OMOPHOTO_RECORD_COUNTS = 3;
	const GET_OMOPHOTO_RANKING_RECORD_COUNTS = 4;
	const GET_PHOTO_RECORD_COUNTS = 3;
	const GET_SPECIAL_COLLECTION_RECORD_COUNTS = 4;//最大4件,edit by shahailong

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
	 * トップ画面で使うリストを取得します。
	 *
	 * @return string
	 * @throws Exception_Paramerror
	 */
	public function get_top_list(){
		$result['omophotos'] = Model_Omophoto::list_data(static::GET_OMOPHOTO_RECORD_COUNTS);
		$result['omophoto_rankings'] = Model_Omophoto_Ranking_Term::list_data(static::GET_OMOPHOTO_RANKING_RECORD_COUNTS,Model_Omophoto_Ranking_Term::TYPE_DAILY_RANKING);
		$result['photos'] = Model_Photo::list_data(static::GET_PHOTO_RECORD_COUNTS);
		$result['special_collections'] = Model_Special_Collection::list_data(static::GET_SPECIAL_COLLECTION_RECORD_COUNTS);

	   return $result;
	}

}