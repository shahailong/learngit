<?php
/**
 * Facebookアカウント、URLスキーム連携
 */
class Controller_Account_Entrance_Facebook_Scheme extends Controller_Rest_Base{

    /**
     * デバッグ環境でのみ実行できるようにします。
     * 
     * @throws Exception_Notimplemented
     */
    public function before(){
        if(!Util_Debug::is_debug_enable()){
            throw new HttpNotFoundException();
        }

        parent::before();
    }

    /**
     * Facebook認証開始
     */
    public function post_request(){
        // TODO Facebookログインページへのリダイレクトが必要になったら実装する
    }

    /**
     * Facebook認証終了確認
     */
    public function get_callback(){
        // 空のステータスコード200とする
    }
}