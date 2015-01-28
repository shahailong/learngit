<?php
/**
 * 認証の基底Controllerです。<br />
 * ※認証済であることを保証させたいControllerは、これを継承します。
 *
 * @author k-kawaguchi
 * @package app
 * @extends Controller_Rest_Base
 */
abstract class Controller_Rest_Base_Authorized extends Controller_Rest_Base{

    /**
     * ログイン証明コード認証の通過後に、基底処理に遷移を引き渡します。<br />
     *
     * @param string $method
     * @param array $parameter
     * @return mixed
     * @throws \AuthException
     */
    public function router($method, $parameter){
        try{
            $auth_driver = $this->get_auth_driver();
            if(!$auth_driver){
                throw new \AuthException('Could not get auth_driver.');
            }

            // ログイン証明コードが存在すれば取得する
            $login_credential = null;
            /* if(Fuel::$env == 'test'){
                // UnitTestの場合、$_SERVERから取得する
                $login_credential = (isset($_SERVER[Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY]) ? $_SERVER[Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY] : null);
            }else{
                // 通常の場合は、HTTPヘッダないしはセッション
                $login_credential = Input::headers(Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY, null);
                $session_credential = Session::get(Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY, null);
                if(!$login_credential){
                    $login_credential = $session_credential;
                }elseif($session_credential && ($login_credential != $session_credential)){
                    Session::set(Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY, $login_credential);
                }
            } */
            $login_credential = Input::headers(Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY, null);

            $auth_driver->set_login_credential($login_credential);
            // 認証する
            $auth_driver->check_login_credential();
            // Modelに実行者のユーザIDを保持させる
            Model_Base::set_executor_user_id($auth_driver->get_user_id());
        }catch(\AuthException $e){
            // 認証エラー
            Log::debug('[exception] Logging exception for debug. caused by : '. $e->getMessage() . ' / trace: ' . $e->getTraceAsString());
            return $this->response_unauthorized();
        }

        return parent::router($method, $parameter);
    }

    /**
     * 認証エラーが発生した際のレスポンスを返却します。<br />
     * ※継承元で、認証エラーが発生した際の挙動を変更したい場合は、このメソッドをオーバーライドして下さい。
     *
     * @return mixed
     */
    protected function response_unauthorized(){
        return $this->response_error(Constants_Code::UNAUTHENTICATED,'Login required');
    }

}