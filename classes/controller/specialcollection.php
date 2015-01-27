<?php
/**
 * Controller
 *
 * @author a-kido
 */
class Controller_Specialcollection extends Controller_Rest_Base{

	/**
	 * API：特集フォト一覧取得(新着順) GET /specialCollection/photo
	 */
	public function get_photo(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$special_collection_id = Validation_Base::get_valid('special_collection_id');
		$max_id = Validation_Base::get_valid('max_id');
		$no_cache = Validation_Base::get_valid('no_cache');
		
		if(empty($need_num) || empty($special_collection_id)){

		}
		
		$domain = new Domain_Specialcollection($this, $this->get_auth_driver());
		$this->response($domain->get_special_collection_photo($need_num, $special_collection_id, $max_id, $no_cache));
   }
   
	/**
	 * API：特集おもフォト一覧取得 GET /specialCollection/omophoto
	 */
	public function get_omophoto(){
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$special_collection_id = Validation_Base::get_valid('special_collection_id');
		$max_id = Validation_Base::get_valid('max_id');
		$order_type = Validation_Base::get_valid('order_type');
		$no_cache = Validation_Base::get_valid('no_cache');
		
		if(empty($need_num) || empty($special_collection_id) || empty($order_type)){

		}
		
		$domain = new Domain_Specialcollection($this, $this->get_auth_driver());
		$this->response($domain->get_special_collection_omophoto($need_num, $special_collection_id, $max_id, $order_type, $no_cache));
   }   
	
	/**
	 * 過去特集一覧取得 GET /specialcollection/old
	 */
	public function get_old(){
		/*
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		//$user_id = null;
		//$special_collection_id = Validation_Base::get_valid('special_collection_id');
		$max_id = Validation_Base::get_valid('max_id');
		$no_cache = Validation_Base::get_valid('no_cache');
		*/
		//if(empty($need_num)){

		//}
		
		$domain = new Domain_Specialcollection($this, $this->get_auth_driver());
		//$this->response($domain->get_specialcollection_old($need_num, $max_id, $no_cache));
		$this->response($domain->get_specialcollection_old());
	}
}