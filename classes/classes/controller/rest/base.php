<?php
/**
 * 基底Controller。<br />
 * ※「エニッシュ」案件より、リソースを頂きました。
 *
 * @author
 * @package  app
 * @extends  Controller_Rest_Base
 */
abstract class Controller_Rest_Base extends Controller_Rest{

    /**
     * デフォルトの出力形式
     */
    const DEFAULT_RESPONSE_FORMAT = 'json';

    /**
     * 更新キュー
     *
     * @var array
     */
    private $update_que = array();
    /**
     * 認証ドライバ
     *
     * @var Auth_Driver_Base
     */
    private $auth_driver = null;

    /**
     * 認証ドライバーを保持します。
     *
     * @author k-kawaguchi
     * @param Auth_Driver_Base $auth_driver
     * @throws Exception
     */
    protected function set_auth_driver(Auth_Driver_Base $auth_driver){
        if(!is_null($this->auth_driver)){
            throw new Exception_Logic('auth_driver is already set. auth_driver=' . print_r($this->auth_driver, true));
        }

        if(is_null($auth_driver)){
            throw new Exception_Logic('auth_driver not must be NULL.');
        }

        $this->auth_driver = $auth_driver;
    }

    /**
     * 保持されている認証ドライバーを取得します。
     *
     * @author k-kawaguchi
     * @return Auth_Driver_Base
     */
    protected function get_auth_driver(){
        return $this->auth_driver;
    }

    /**
     * 当該Controllerの事前処理<br />
     * 以下の事前条件を設定する:<br />
     * 1. レスポンスフォーマット=JSON<br />
     * 2. 認証ドライバの初期化
     *
     */
    public function before(){
        Auth_Driver_Base::clear();
        // Inputが保持する内容を削除する（HMVCからの呼び出しの場合、前回呼び出し時の内容が保持されてしまうため）
        Input::cleanup();
        // Fieldsetがstaticに保持するインスタンスを削除する
        Fieldset::clear_instance();
        parent::before();
        // default のレスポンスフォーマット
        $this->format = self::DEFAULT_RESPONSE_FORMAT;
        // サーバメンテナンス判定
        if((boolean)Config::get('maintainance_flg') === true){
            // メンテナンスモードのレスポンスを強制出力させる
            $this->force_exit_maintainance();
        }

        // 認証ドライバをセットする
        $auth_driver = Auth_Driver_Base::forge();
        $this->set_auth_driver($auth_driver);
    }

    /**
     * 各actionに対して、例外のハンドリングを行い、適切なエラーレスポンスを返却します。<br />
     * ※「after」は利用しません。
     *
     * @author k-kawaguchi
     * @param string $method
     * @param array $parameter
     * @return
     */
    public function router($method, $parameter){
        $login_credential = Input::headers(Constants_Common::LOGIN_CREDENTIAL_HEADER_KEY, null);

        try{
            $auth_driver = $this->get_auth_driver();

            if($auth_driver->is_authorized() == FALSE && isset($login_credential)){
                $auth_driver->set_login_credential($login_credential);
                // 認証する
                $auth_driver->check_login_credential();
                // Modelに実行者のユーザIDを保持させる
                Model_Base::set_executor_user_id($auth_driver->get_user_id());
            }

            // actionを実行させる
            $result = parent::router($method, $parameter);
            // DB更新処理
            $this->db_update();
            // イベント「on_success」を発生させる
            Event::trigger('on_success');
            // 成功
            return $result;

        }catch (AuthException $e){
        	// エラーコード「LOGINTOKEN_INVAILD」を返却
        	$this->response_error(Constants_Code::LOGINTOKEN_INVAILD, $e->getMessage());
        }catch(HttpException $e){
            // HTTPステータスに関したエラーはそのまま上に投げる
            Log::debug('[exception] Logging exception for debug. caused by : '. get_class($e) . ' / message='. $e->getMessage() . ' / trace: ' . $e->getTraceAsString());
            throw $e;
        }catch(Exception_Base $e){
            // 独自定義のエラー（セットされているエラーコードをクライアントへ返却する）
            Log::debug('[exception] Logging exception for debug. caused by : '. get_class($e) . ' / message='. $e->getMessage() . ' / trace: ' . $e->getTraceAsString());
            $this->response_error($e->getCode(), $e->getMessage());
        }catch(Exception $e){
            // 不明なエラー（エラーコード「UNKNOWN_ERROR」を返却しつつ、エラーログを記録する）
            Log::error('[exception] Unexpected uncaught exception. caused by : '. get_class($e) . ' / message='. $e->getMessage() . ' / trace: ' . $e->getTraceAsString());
            // エラーレポートを送信する
            $this->error_report($e);
            $this->response_error(Constants_Code::UNKNOWN_ERROR, $e->getMessage());
        }
        // 失敗
        return;
    }

    /**
     * エラーレポートを送信します。
     *
     * @param Exception $e
     * @return void
     */
    protected function error_report(Exception $e){
        // エラーレポートの宛先が未設定であれば送信しない
        $receiver = (string)Config::get('error_mail_receiver', '');
        if(empty($receiver)){
            Log::info("Skipping send error report. Config parameter 'error_mail_receiver' is not set.");
            return;
        }

        Log::info("Starting send error report. to='${receiver}'");
        // メール送信（try-catchブロックを用いて、発生した例外を全て当該メソッド内部で補足する）
        $mail_util = null;
        try{
            $mail_util = new Util_Mail(explode(',', $receiver), 'error_report');
            $error_name = get_class($e);
            $host = gethostname();
            $mail_util->set_subject_data(array(
                'host'=>$host,
                'error_name'=>$error_name
            ));
            $mail_util->set_body_data(array(
                'date'=>date('Y-m-d H:i:s'),
                'host'=>$host,
                'error_name'=>$error_name,
                'error_message'=>$e->getMessage(),
                'error_trace'=>$e->getTraceAsString(),
                'url'=>((is_null(Input::server('HTTPS', null)) ? "http://" : "https://") . Input::server('HTTP_HOST', null) . Input::server('REQUEST_URI', null)),
                'request_method'=>Input::server('REQUEST_METHOD'),
                'dumped_parameters'=>print_r(Input::all(), true)
            ));
            $mail_util->send();
        }catch (Exception $e){
            Log::error("Occurred error while sending error report. caused by: " . $e->getMessage() . "\n" . "trace:" . "\n" . $e->getTraceAsString());
        }
        // Util_Mailのコンストラクタ内部で異常が発生しなければ起こらない
        if(is_null($mail_util)){
            Log::error("The instance of 'Util_Mail' for error report is not generated. to='${receiver}'");
            return;
        }

        // メール送信に失敗したらエラーログに内容を出力する
        if(!$mail_util->is_send_success()){
            Log::error(
                "Failed to send report mail. to=" . $mail_util->get_mail_name() . " / template name=" . $mail_util->get_mail_name() . " / " .
                "sublect data=" . print_r($mail_util->get_subject_data(), true) . " / " . "body data=" . print_r($mail_util->get_body_data(), true) . " / " .
                "error name=" . $mail_util->get_last_error_name() . " / " . "message=" . $mail_util->get_last_error_message() . "\n" .
                "trace: \n" . $mail_util->get_last_error_trace()
            );
            return;
        }

        Log::info("Sending error report is successfly finished. to='${receiver}'");
    }

    /**
     * メンテナンスモードのレスポンス内容を出力して強制終了します。<br />
     * ※データベースを停止させるようなメンテナンスの場合、接続に至る以前のタイミングでレスポンスを出力の上、強制終了させる必要があります。
     */
    private function force_exit_maintainance(){
        $response = $this->response_maintainance();
        if(!isset($response->body)){
            throw new Exception_Logic('Response of maintainance must contain body.');
        }

        echo $response->body;
        exit;
    }

    /**
     * メンテナンスモードのレスポンス内容を生成します。
     *
     * @return array
     */
    protected function response_maintainance(){
        return $this->response(array(
            Constants_Common::RESULT_CODE_RESPONSE_KEY=>Constants_Code::SERVER_MAINTAINANCE,
        	Constants_Common::TIME_RESPONSE_KEY=>time(),
        	'message'=>'',
            'maintainance_message'=>Config::get('maintainance_message'),
            'maintainance_started_at'=>Config::get('maintainance_started_at'),
            'maintainance_end_at'=>Config::get('maintainance_end_at')
        ));
    }

    /**
     * エラーコードを受け取りエラーレスポンスを作成します。
     *
     * @param int $code エラーコード(0以上の数字以外は、UNKNOWN_ERRORに補正する)
     * @return
     */
    protected function response_error($code = Constants_Code::UNKNOWN_ERROR, $message = ''){
        $response_ary = array();
        $response_ary[Constants_Common::RESULT_CODE_RESPONSE_KEY] = ((ctype_digit((string)$code) && (int)$code > 0) ? (int)$code : Constants_Code::UNKNOWN_ERROR);
        $response_ary['message'] = $message;

        return $this->response($response_ary);
    }

    /**
     * レスポンス<br />
     * ※結果コードと時刻を自動的に設定します。
     *
     * @param array $ary
     * @return
     */
    protected function response($data = array(), $http_status = null){
        // Modelが溜め込んでいるデータを全て消す
        Model_Base::flush_all_stocks();

        // コード値を明示的に指定しない限りは、成功と判断する（既にセットされていた場合の値の妥当性は確認しない）
        if(is_array($data)){
            // 結果コード
            $result_code = (
                isset($data[Constants_Common::RESULT_CODE_RESPONSE_KEY])
                ? $data[Constants_Common::RESULT_CODE_RESPONSE_KEY]
                : Constants_Code::SUCCESS
            );
            $data[Constants_Common::RESULT_CODE_RESPONSE_KEY] = $result_code;
            $data[Constants_Common::TIME_RESPONSE_KEY] = time();

            if($data[Constants_Common::RESULT_CODE_RESPONSE_KEY] == Constants_Code::SUCCESS){
            	$data['message'] = "";
            }

        }
        return parent::response($data, $http_status);
    }

    /**
     * queへ更新するレコードを登録します。
     *
     * @author k-kawaguchi
     * @param Model_Base $object
     * @return boolean
     */
    public function add_update_que(Model_Base $object){
        if(is_null($object)){
            return false;
        }

        $this->update_que[] = $object;
        return true;
    }

    /**
     * 更新queの内容を任意のタイミングで強制的に全て実行します。<br />
     * ※デバッグ、テストUnitでの利用を推奨します（実機能においては、用いないで下さい）。
     */
    public function force_db_update(){
        if(!Util_Debug::is_debug_enable()){
            Log::error(__METHOD__ . " is only use for debugging, but debug mode is disabled.");
        }
        $this->db_update();
    }

    /**
     * $this->update_que で保持しているオブジェクトに対し更新を行う
     *
     * FIXME: 適切なExceptionクラスの実装
     * @author
     * @throws Exception 更新処理が正常に行われなかった場合
     */
    private function db_update(){
        // queが不正な値の場合
        if(!is_array($this->update_que)){
            throw new Exception_Logic('update_que must be an array. update_que=' . print_r($this->update_que, true));
        }

        // queが空ならスキップ
        if(!$this->update_que){
            return;
        }

        // DB更新を一括して行う
        Model_Base::save_all_as_transaction($this->update_que);
        $this->update_que = array();
    }

}