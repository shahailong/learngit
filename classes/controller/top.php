<?php
/**
 * TOP画面のリストを取得するControllerです。
 * @author m.yoshitake
 */
class Controller_Top extends Controller_Rest_Base{

	/**
	 * 11：トップ GET /top/list
	 */
	public function get_list(){
	$issp = Util_Common::is_mobile_request();
	if($issp == true){
	$domain = new Domain_Top($this, $this->get_auth_driver());
	$this->response($domain->get_top_list());
	}else{
		$domain = new Domain_Top($this, $this->get_auth_driver());
		$this->response($domain->get_top_listp());
	}
	}
}