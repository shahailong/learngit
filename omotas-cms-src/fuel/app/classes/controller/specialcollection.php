<?php
/**
 * The User Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @package  app
 * @extends  Controller_Base
 * @author Kido
 */
class Controller_SpecialCollection extends Controller_Base
{

	public function before(){
		parent::before();
	}

	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		//Log::error('action_index start');
		$data = null;
		$val = Validation::forge();
		
		$this->template->title = '特集一覧';
		$this->template->content = View::forge('specialcollection/index', array('val' => $val, 'data' => $data), false);
	}

	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_detail()
	{
		Log::error('action_detail start');
		$data = null;
		$val = Validation::forge();
		
		$specialId = Input::get('id');
		
		$data = Model_Specialcollectiontemp::get_special_collect_by_id($specialId);
		//フォトID
		$photo_data = Model_Specialcollectionlinkedtemp::get_special_collection_photo_list($specialId,$linked_type=0);
		$photo_list = "";
		foreach ($photo_data as $data_item){
			if($photo_list == ""){
				$photo_list = $data_item['linked_photo_id'];
			}else{
				$photo_list = $photo_list.",".$data_item['linked_photo_id'];
			}
		}
		
		//おもフォトID
		$omophoto_data = Model_Specialcollectionlinkedtemp::get_special_collection_photo_list($specialId,$linked_type=1);
		$omophoto_list = "";
		foreach ($omophoto_data as $data_item){
			if($omophoto_list == ""){
				$omophoto_list = $data_item['linked_photo_id'];
			}else{
				$omophoto_list = $omophoto_list.",".$data_item['linked_photo_id'];
			}
		}
		$this->template->title = '特集詳細';
		$this->template->content = View::forge('specialcollection/detail', array('data' => $data, 'photo_list' => $photo_list, 'omophoto_list' => $omophoto_list,'specialId' => $specialId), false);
	}
	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_update()
	{
		Log::error('action_update start');

		$data = null;
		$val = Validation::forge();
		
		$specialId = Input::post('specialId'); 
		$name = Input::post('name'); 
		$introduction = Input::post('introduction'); 
		$start_date = Input::post('start_date'); 
		$end_date = Input::post('end_date'); 
		$nowmark_start_date = Input::post('nowmark_start_date'); 
		$nowmark_end_date = Input::post('nowmark_end_date'); 
		$photo_list = Input::post('photo_list'); 
		$omophoto_list = Input::post('omophoto_list'); 
		$special_type = Input::post('special_type'); 
		
		
		Log::error('action_update name:'.$name.' introduction:'.$introduction.' start_date:'.$start_date." end_date:".$end_date." specialId:".$specialId." special_type:".$special_type);
		Log::error('action_update nowmark_start_date:'.$nowmark_start_date.' nowmark_end_date:'.$nowmark_end_date.' photo_list:'.$photo_list." omophoto_list:".$omophoto_list);
		
		//sp_banner_file
		//pc_banner_file
		//sp_special_page_file	
		//pc_special_page_file
		
		Util_Common::deldir(Config::get('upload_path'));
		
		$specialId = 17; 
		//現在の時刻設定
		$YmdHis = date("YmdHis");
		if($_FILES["sp_banner_file"]["error"] != 4){//4:ファイルがアップロードしない
			$sp_banner_img = Util_Common::upload_special_banner_image($specialId,$_FILES["sp_banner_file"],"banner_sp.jpg",$YmdHis);
			
		}
		if($_FILES["pc_banner_file"]["error"] != 4){//4:ファイルがアップロードしない
			$pc_banner_img = Util_Common::upload_special_banner_image($specialId,$_FILES["pc_banner_file"],"banner_pc.jpg",$YmdHis);
		}
		if($_FILES["sp_special_page_file"]["error"] != 4){//4:ファイルがアップロードしない
			$sp_banner_link = Util_Common::upload_zip_file($specialId,$_FILES["sp_special_page_file"],$YmdHis);
		}


		$data = Model_Specialcollectiontemp::get_special_collect_by_id($specialId);
		//フォトID
		$photo_data = Model_Specialcollectionlinkedtemp::get_special_collection_photo_list($specialId,$linked_type=0);
		$photo_list = "";
		foreach ($photo_data as $data_item){
			if($photo_list == ""){
				$photo_list = $data_item['linked_photo_id'];
			}else{
				$photo_list = $photo_list.",".$data_item['linked_photo_id'];
			}
		}
		
		//おもフォトID
		$omophoto_data = Model_Specialcollectionlinkedtemp::get_special_collection_photo_list($specialId,$linked_type=1);
		$omophoto_list = "";
		foreach ($omophoto_data as $data_item){
			if($omophoto_list == ""){
				$omophoto_list = $data_item['linked_photo_id'];
			}else{
				$omophoto_list = $omophoto_list.",".$data_item['linked_photo_id'];
			}
		}
		$this->template->title = '特集詳細';
		$this->template->content = View::forge('specialcollection/detail', array('data' => $data, 'photo_list' => $photo_list, 'omophoto_list' => $omophoto_list,'specialId' => $specialId), false);
	}
}
