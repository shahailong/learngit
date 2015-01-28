<?php

/**
 * 
 *
 * @author k-kawaguchi
 */
class Domain_Specialcollection extends Domain_Base {
	
		const GET_SPECIAL_COLLECTION_RECORD_COUNTS = 4;
		const GET_MAX_ID_RECORD_COUNTS = 4;
		const GET_CURRENT_COUNT_RECORD_COUNTS = 4;

    private $auth_driver = null;

    /**
     * コンストラクタ
     * @param Controller_Rest_Base $context
     * @param Auth_Driver_Base $auth_driver
     */
    public function __construct(Controller $context, Auth_Driver_Base $auth_driver) {
        parent::__construct($context);
        //if (is_null($auth_driver) || !$auth_driver->is_authorized()) {
        //    throw new Exception_Logic('Failed to construct ' . __CLASS__ . ', suplied Auth_Driver_Base is illegal state.');
        //}

        $this->auth_driver = $auth_driver;
    }

	/**
	 * 特集フォト一覧取得(新着順)
	 *
	 * @param int $need_num
	 * @param int $special_collection_id
	 * @param int $max_id
	 * @param int $no_cache
	 *
	 * @return Model_special_collection_*
	 * @throws Exception_Paramerror
	 */
	public function get_special_collection_photo($need_num, $special_collection_id, $max_id, $no_cache){
		return Model_Special_Collection::get_special_photo($need_num, $special_collection_id, $max_id, $no_cache);
	}    
	
	/**
	 * 特集おもフォト一覧取得
	 *
	 * @param int $need_num
	 * @param int $special_collection_id
	 * @param int $max_id
	 * @param int $order_type
	 * @param int $no_cache
	 *
	 * @return Model_special_collection_*
	 * @throws Exception_Paramerror
	 */
	public function get_special_collection_omophoto($need_num, $special_collection_id, $max_id, $order_type,$no_cache){
		$max_target_at = null;
		Log::info("domain 111111111111111111111111".$order_type);
		if ($order_type == '2'){
			//人気順
			$max_target_at = Model_Omophoto_Ranking_Total::get_max_target_at();
		}
		return Model_Special_Collection::get_special_omophoto($need_num, $special_collection_id, $max_id, $order_type,$no_cache,$max_target_at);
	}
	
	 /**
	 * 過去特集一覧取得
	 * author : shahailong
	 * BJT :2014/01/07
	 *
	 * @return string
	 * @throws Exception_Paramerror
	 */
	 
	public function get_specialcollection_old($need_num, $max_id, $no_cache){
		//public function get_specialcollection_old(){
		return Model_Special_Collection::get_old_data($need_num, $max_id, $no_cache);
		//$result['special_collections'] = Model_Special_Collection::old_data(static::GET_SPECIAL_COLLECTION_RECORD_COUNTS);

	   //return $result;
	}    	

}