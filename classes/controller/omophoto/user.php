<?php
/**
 * おもフォトに関するリクエストを受け付けるController
 * ※要認証
 *
 * @author m.yoshitake
 */
class Controller_Omophoto_User extends Controller_Rest_Base{

    /**
     * おもフォトアップロードAPI
     */
    public function post_upload(){
        Validation_Base::init(__METHOD__);
        $omophotofile = Input::file('omophotofile', array());
//        $photo_id = Input::post('photo_id');
//        $title = Input::post('title');

        $domain = new Domain_Omophoto($this, $this->get_auth_driver());
        $this->response(
            $domain->omophoto_upload(
                $omophotofile,
                Validation_Base::get_valid('photo_id'),
                Validation_Base::get_valid('title')
                //$external_link
            )
        );
    }

     /**
     * おもフォトへのいいね
     */
    public function post_appraised(){
        Validation_Base::init(__METHOD__);
        $domain = new Domain_Omophoto($this, $this->get_auth_driver());
        $this->response(
            $domain->omophoto_appraised(
                Validation_Base::get_valid('omophoto_id'),
                Validation_Base::get_valid('points')
            )
        );
  }

}
