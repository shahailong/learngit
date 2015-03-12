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
class Controller_Ajax extends Controller_Rest
{
	const RETURN_OK = '2000';						// OK
	const BANNER_DISPLAY_FLAG_0 = 0;				//特集バナー表示フラグ  0:非表示　1:表示'


	/**
	 * 特集新規作成
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_createSpecialCollection()
	{
		Log::error('createSpecialCollection start');
		
		//$val = Validation::forge();
		
		$name = Input::post('name');
		$introduction = Input::post('introduction');
		$start_date = Input::post('start_date');
		$end_date = Input::post('end_date');

		
		Log::error('name:'.$name." introduction:".$introduction." start_date:".$start_date." end_date:".$end_date);

		$data = Model_Specialcollectiontemp::create_special_collection($name,$introduction,$start_date,$end_date);
		
		header('content-type: application/json; charset=utf-8');
		$result = array('result_code' => self::RETURN_OK,
						'data' => $data
                        );
        
                
        parent::response(json_encode($result), $http_status = null);
	}
	
	/**
	 * 本番反映
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_releaseSpecialCollection()
	{
		Log::error('releaseSpecialCollection start');
		
		//$val = Validation::forge();
		
		Model_Specialcollection::copy_to_special_collection();
		Model_Specialcollectionlinked::copy_to_special_collection_linked();
		Model_Specialworkexclusion::update_special_work_exclusion(0);
		
		header('content-type: application/json; charset=utf-8');
		$result = array('result_code' => self::RETURN_OK
                        );
        
                
        parent::response(json_encode($result), $http_status = null);
	}

}
