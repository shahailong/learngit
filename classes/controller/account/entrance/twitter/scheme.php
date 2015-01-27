<?php
/**
 * Twitterアカウント、URLスキーム連携
 */
class Controller_Account_Entrance_Twitter_Scheme extends Controller_Rest_Base{

    /**
     * デバッグ環境でのみ実行できるようにします。
     *
     * @throws Exception_Notimplemented
     */
    public function before(){
        if(!Util_Debug::is_debug_enable()){
            throw new HttpNotFoundException();
        }

        // このControllerは、現状「callbackurl」以外の目的では使用しない
        throw new Exception_Notimplemented();
        //parent::before();
    }

    /**
     * エラーが発生した際に、URLスキームへエラーコードを附加してリダイレクトします。
     *
     * @param int $code
     */
    protected function response_error($code = Constants_Code::UNKNOWN_ERROR, $message = ''){
        return Response::redirect(
            Constants_Common::URL_SCHEME_PROTOCOL . '://' .
                Constants_Common::URL_SCHEME_TWITTER_FINISH .
                '?result_code=' . $code,
            'location'
        );
    }

    /**
     * Twitter認証開始
     */
    /* public function post_request(){
        Validation_Base::init(__METHOD__);
        $domain = new Domain_User_Account_Twitter(
            $this,
            new Auth_Driver_Base_Account_Twitter($this->get_auth_driver())
        );
        $url = $domain->create_request_url(Validation_Base::get_valid('auth_type'));
        Log::debug('Redirect to the twitter URL ->' . $url);
        return Response::redirect($url, 'location');
    } */

    /**
     * Twitter認証終了確認
     */
    public function get_callback(){
        // Twitter向けcallback_url。空の200を返す。
        // ユーザが自分の意志で認証をキャンセルした場合、Twitterからパラメータ「denied」が返却される
        /* if(!is_null(Input::get('denied', null))){
            Log::debug('Twitter auth is canceled. parameters=' . print_r(Input::all(), true));
            $this->response_error(Constants_Code::TWITTER_AUTH_CANCELED);
            return;
        }

        Validation_Base::init(__METHOD__);
        $domain = new Domain_User_Account_Twitter(
            $this,
            new Auth_Driver_Base_Account_Twitter($this->get_auth_driver())
        );
        $url = $domain->confirm_auth_create_redirect_url(
            Validation_Base::get_valid('3rdapp_token'),
            Validation_Base::get_valid('oauth_token'),
            Validation_Base::get_valid('oauth_verifier')
        );
        Log::debug('Redirect to the URL scheme ->' . $url);
        // リダイレクトして終了
        return Response::redirect($url, 'location'); */
    	return;
    }

}