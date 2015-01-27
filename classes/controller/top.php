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
		$domain = new Domain_Top($this, $this->get_auth_driver());
		$this->response($domain->get_top_list());
	}
}