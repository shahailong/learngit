<?php
/**
 * 共通Controller。
 *
 *
 * @author k-kawaguchi
 * @package  app
 * @extends  Controller
 */
class Controller_Common extends Controller{
    public function action_404(){
        return Response::forge(ViewModel::forge('common/404'), 404);
    }
    public function action_500(){
        return Response::forge(ViewModel::forge('common/500'), 500);
    }
}