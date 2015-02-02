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
	 * UESR AGENT
	 *
	 * @return boolean
	 */
	
	public static function is_mobile_request()   
    {   
      $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';   
      $mobile_browser = '0';   
      if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))   
        $mobile_browser++;   
      if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))   
        $mobile_browser++;   
      if(isset($_SERVER['HTTP_X_WAP_PROFILE']))   
        $mobile_browser++;   
      if(isset($_SERVER['HTTP_PROFILE']))   
        $mobile_browser++;   
      $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));   
      $mobile_agents = array(   
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',   
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',   
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',   
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',   
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',   
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',   
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',   
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',   
            'wapr','webc','winw','winw','xda','xda-'   
            );   
      if(in_array($mobile_ua, $mobile_agents))   
        $mobile_browser++;   
      if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)   
        $mobile_browser++;   
      // Pre-final check to reset everything if the user is on Windows   
      if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)   
        $mobile_browser=0;   
      // But WP7 is also Windows, with a slightly different characteristic   
      if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)   
        $mobile_browser++;   
      if($mobile_browser>0)   
        return true;   
      else  
        return false;   
    }

	/**
	 * 過去特集一覧取得 GET /specialcollection/old
	 */
	public function get_old(){
		
		Validation_Base::init(__METHOD__);
		$need_num = Validation_Base::get_valid('need_num');
		$max_id = Validation_Base::get_valid('max_id');
		$no_cache = Validation_Base::get_valid('no_cache');
		
		if(empty($need_num)){

		}
		
				$issp = self::is_mobile_request();
		if($issp == true){
			
		$domain = new Domain_Specialcollection($this, $this->get_auth_driver());
		//$this->response($domain->get_specialcollection_old($need_num, $max_id, $no_cache));
	$this->response($domain->get_specialcollection_old($need_num, $max_id, $no_cache));
}else{
	
	}
	}
}