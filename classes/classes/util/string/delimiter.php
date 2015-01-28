<?php
/**
 * 区切り文字解析
 * 
 * @author k-kawaguchi
 */
class Util_String_Delimiter{

    /**
     * 対象文字列
     * 
     * @var string 
     */
    private $str = null;
    /**
     * バリデータ
     * 
     * @var string/array 
     */
    private $validator = null;
    /**
     * 区切り文字
     * 
     * @var type 
     */
    private $delimiter = null;
    /**
     * 例外名称<br />
     * デフォルト: Exception_Paramerror
     * 
     * @var string 
     */
    private $exception_name = 'Exception_Paramerror';
    /**
     * 解析結果
     * 
     * @var type 
     */
    private $result_ary = null;

    /**
     * コンストラクタ
     * 
     * @param string $str
     */
    public function __construct($str){
        $this->str = $str;
    }

    /**
     * 1ワード毎に適用するバリデータを設定します。<br />
     * ※バリデータの形式は、PHP関数「forward_static_call」の第1引数と同様の形式です。<br />
     * http://www.php.net/manual/ja/function.forward-static-call.php
     * 
     * @param string/array $validator バリデータ
     * @throws Exception_Logic
     */
    public function set_validator($validator){
        if(!is_null($this->validator)){
            throw new Exception_Logic("Validator is already set.");
        }

        $this->validator = $validator;
    }

    /**
     * 区切り文字を設定します。
     * 
     * @param string $delimiter
     * @throws Exception_Logic
     */
    public function set_delimiter($delimiter){
        if(!is_null($this->delimiter)){
            throw new Exception_Logic("Delimiter is alreaddy set.");
        }

        if(is_null($delimiter) || !is_scalar($delimiter) || $delimiter == ''){
            throw new Exception_Logic('Illegal arguments. delimiter=' . print_r($delimiter));
        }

        $this->delimiter = $delimiter;
    }

    /**
     * バリデーションエラー時に発生させたい例外の名称を設定します。
     * 
     * @param string $exception_name
     * @throws Exception_Logic
     */
    public function set_error_exception($exception_name){
        if(!class_exists($exception_name)){
            throw new Exception_Logic('Illegal arguments. exception_name=' . print_r($exception_name));
        }

        $this->exception_name = $exception_name;
    }

    /**
     * 解析を実行します。
     * 
     * @return array 
     * @throws mixed
     * @throws Exception_Logic
     */
    public function parse(){
        if(is_array($this->result_ary)){
            return $this->result_ary;
        }

        // 空の場合
        if(empty($this->str)){
            $this->result_ary = array();
            return array();
        }

        // 配列に変換する
        $exception_name = $this->exception_name;
        $parse = explode($this->delimiter, $this->str);
        if(!is_array($parse)){
            throw new $exception_name("Could not parse string '" . $this->str ."' use delimiter '" . $this->delimiter . "'.");
        }

        // バリデータの妥当性を確認する
        if(is_null($this->validator)){
            return $parse;
        }

        $this->result_ary = array();
        if(is_array($this->validator)){
            if(!isset($this->validator[0]) || !class_exists($this->validator[0])){
                throw new Exception_Logic('Suplied validator is not callable.');
            }

            if(!isset($this->validator[1]) || !method_exists($this->validator[0], $this->validator[1])){
                throw new Exception_Logic('Suplied validator is not callable.');
            }
        }

        // バリデーション
        foreach($parse as $separated){
            if(!forward_static_call($this->validator, $separated)){
                throw new $exception_name("Could not parse string '" . $this->str ."' use delimiter '" . $this->delimiter . "'.");
            }

            $this->result_ary[] = $separated;
        }
        return $this->result_ary;
    }

    /**
     * 区切り文字を指定し、文字列を配列へ分割します。
     * 
     * @param string $str 文字列
     * @param string $validator バリデータ（省略可能　booleanを返値とする関数名およびarray(クラス名, メソッド名)の形式）
     * @param string $delimiter 区切り文字（デフォルト： 「,」）
     * @return array
     */
    public static function parse_delimited_string($str, $validator = null, $delimiter = ','){
        $parser = new Util_String_Delimiter($str);
        $parser->set_delimiter($delimiter);
        $parser->set_validator($validator);
        return $parser->parse();
    }

}