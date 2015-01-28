<?php

/**
 * 画像系の汎用機能
 *
 * @author k-kawaguchi
 */
include_once(APPPATH . "vendor/colors.inc.php");
include_once(APPPATH . "vendor/GifManipulator.php");

// S3関連のSDKの読み込み
require_once("AWSSDKforPHP/aws.phar");
use Aws\S3\S3Client;
use Aws\Common\Enum\Region;
use Aws\S3\Exception\S3Exception;
use Guzzle\Http\EntityBody;

class Util_Image {

    /**
     * 画像格納ディレクトリのパーミッション
     */
    const IMAGE_DIR_PERMISSION = 0733;

    /**
     * 画像ファイルのパーミッション
     */
    const IMAGE_FILE_PERMISSION = 0766;

    /**
     * 画像シンボリックリンクのパーミッション
     */
    const IMAGE_LINK_PERMISSION = 0777;

    /**
     * 画像格納ディレクトリのユーザ識別子
     */
    const IMAGE_DIR_OWNER_NAME = 'apache';

    /**
     * 画像ファイルのユーザ識別子
     */
    const IMAGE_FILE_OWNER_NAME = 'apache';

    /**
     * 画像格納ディレクトリのグループ識別子
     */
    const IMAGE_DIR_GROUP_NAME = '3rdapp';

    /**
     * 画像ファイルのグループ識別子
     */
    const IMAGE_FILE_GROUP_NAME = '3rdapp';

    /**
     * URLハッシュ対象種別：公開設定
     */
    const HASH_TARGET_TYPE_SETTING = 1;

    /**
     * URLハッシュ対象種別：グループ
     */
    const HASH_TARGET_TYPE_GROUP = 2;

    /**
     * URLハッシュ対象種別：プロフィール
     */
    const HASH_TARGET_TYPE_PROFILE = 3;

    /**
     * サポートする拡張子一覧
     *
     * @var array
     */
    private static $support_extensions = array();

    /**
     * 「Content-Type」-画像形式定数の対応表
     *
     * @var array
     */
    private static $content_type_indexes = array();

    /**
     * 画像形式定数-拡張子の対応表
     *
     * @var array
     */
    private static $image_type_indexes = array();

    /**
     * 拡張子-画像形式定数の対応表
     *
     * @var array
     */
    private static $extension_indexes = array();

     /**
     * 画像をS3に転送します。
     *
     * @param String $key 転送先ファイルパス
     * @param String $tmpfile 転送元ファイルパス
     *
     */
    public static function image_forward_to_s3($key,$tmpfile) {

    	// キー、シークレットキー、リージョンを指定
    	$client = S3Client::factory(array(
    			'key' => Config::get('s3_accesskeyid'),
    			'secret' => Config::get('s3_secretaccesskey'),
    			'region' => Region::AP_NORTHEAST_1
    			));

        try {
        	$result = $client->putObject(array(
        			'Bucket' => Config::get('s3_public_bucket_name'),
        			'Key' => $key,
        			'Body' => EntityBody::factory(fopen($tmpfile, 'r')),
        	));

        } catch (S3Exception $e) {
        	throw new Exception_Service("S3 Image File Put Error (Key:" .$key .") ERROR:" .$e->getMessage() , Constants_Code::IMAGE_S3_UPLOAD_FAILED);
        }

        // 一時ファイルの削除
        @unlink($tmpfile);

    }

    /**
     * アップロードされたファイル情報を受け取り、一時保存したファイルパスを返します。<br />
     *
     * @param array $fileinfo
     * @return String $file_path
     * @throws Exception_Service
     */
    public static function get_photo_temp_file_path(array $fileinfo) {

        // ファイル情報の妥当性チェック
        static::check_uploaded_file($fileinfo);

        // 拡張子判別
        $extension = Util_Image::get_extension($fileinfo['tmp_name'], $fileinfo['name']);
        if (is_null($extension)) {
        	throw new Exception_Service('Could not get file extension from the path ' . $fileinfo['tmp_name'] . ' or file ' . $fileinfo['name'] . '.', Constants_Code::UNSUPPORTED_IMAGE_EXTENSION);
        }
        //サポートしている拡張子かどうか
        if (is_null(static::extension_to_image_type($extension))) {
        	throw new Exception_Service('Could not get file extension from the path ' . $fileinfo['tmp_name'] . ' or file ' . $fileinfo['name'] . '.', Constants_Code::UNSUPPORTED_IMAGE_EXTENSION);
        }

        // システムテンポラリーから、一時保存ディレクトリに移動
        list($micro, $Unixtime) = explode(" ", microtime());
        $sec = $micro + date("s", $Unixtime); // 秒"s"とマイクロ秒を足す
        $file_name = str_replace(".","",date("YmdHi", $Unixtime).$sec);

        $file_path = Config::get('temp_path') ."photo_" .$file_name ."." .$extension;

        if(!move_uploaded_file($fileinfo['tmp_name'], $file_path)){
        	throw new Exception_Service('Could not move file from ' . $fileinfo['tmp_name'] . ' to ' . $file_path . '.', Constants_Code::IMAGE_MOVE_FAILED);
        }

        return $file_path;

    }

    /**
     * アップロードされたファイル情報の妥当性をチェックします。
     *
     * @param array $info
     * @return boolean
     * @throws Exception_Service
     */
    public static function check_uploaded_file(array $info) {
        if (isset($info['error'])) {
            $error_no = (int) $info['error'];
            switch ($error_no) {
                case UPLOAD_ERR_OK :
                    break;
                case UPLOAD_ERR_INI_SIZE :
                case UPLOAD_ERR_FORM_SIZE :
                    throw new Exception_Service('Size of the uploaded file exceeded limit. error_no=' . $error_no, Constants_Code::IMAGE_UPLOAD_SIZE_EXCESS);
                default :
                    throw new Exception_Service('Failed to upload image file. error_no=' . $error_no, Constants_Code::IMAGE_UPLOAD_ERROR);
            }
        }

        if (!isset($info['name'])) {
            throw new Exception_Service('The parameter name must be specified.', Constants_Code::IMAGE_UPLOAD_ERROR);
        }

        if (!isset($info['tmp_name'])) {
            throw new Exception_Service('The parameter tmp_name is not apear.', Constants_Code::IMAGE_UPLOAD_ERROR);
        }

        if (!file_exists($info['tmp_name'])) {
            throw new Exception_Service("The temp file '" . $info['tmp_name'] . "' is not exists in server.", Constants_Code::IMAGE_UPLOAD_ERROR);
        }

        return true;
    }

    /**
     * サポートする画像形式定数の一覧を返却します。
     */
    public static function get_support_image_types() {
        return Config::get('image.support_image_types', array());
    }

    /**
     * 拡張子別称の対応表を返却します。
     */
    public static function get_extension_alias_indexes() {
        return Config::get('image.extension_alias_indexes', array());
    }

    /**
     * リサイズをサポートする画像形式定数の一覧を返却します。
     *
     * @return array
     */
    public static function get_resizable_image_types() {
        return Config::get('image.resizable_image_types', array());
    }

    /**
     * サポートする画像拡張子の一覧を返却します。<br />
     * ※当該一覧表は、アルファベット大文字の名称を含まない。
     */
    public static function get_support_extensions() {
        if (static::$support_extensions) {
            return static::$support_extensions;
        }

        $support_extensions = array_keys(
                array_flip(
                        array_merge(
                                array_map('image_type_to_extension', static::get_support_image_types(), array(false)), array_keys(static::get_extension_alias_indexes())
                        )
                )
        );
        sort($support_extensions);
        static::$support_extensions = $support_extensions;
        return $support_extensions;
    }

    /**
     * 拡張子-画像形式定数の対応表を返却します。<br />
     * ※当該対応表は、拡張子の別称を含む。
     *
     * @return array array(拡張子=>画像形式定数, …)
     */
    public static function get_extension_indexes() {
        if (static::$extension_indexes) {
            return static::$extension_indexes;
        }

        $extension_indexes = array_flip(static::get_image_type_indexes());
        foreach (static::get_extension_alias_indexes() as $alias => $extension) {
            if (!isset($extension_indexes[$extension])) {
                continue;
            }

            $extension_indexes[$alias] = $extension_indexes[$extension];
        }
        ksort($extension_indexes);
        static::$extension_indexes = $extension_indexes;
        return $extension_indexes;
    }

    /**
     * 画像形式定数-拡張子の対応表を返却します。<br />
     * ※当該対応表は、拡張子の別称を含まない。
     *
     * @return array array(画像形式定数=>拡張子, …)
     */
    public static function get_image_type_indexes() {
        if (static::$image_type_indexes) {
            return static::$image_type_indexes;
        }

        $support_image_types = static::get_support_image_types();
        $image_type_indexes = array_combine(
                $support_image_types, array_map('image_type_to_extension', $support_image_types, array(false))
        );
        ksort($image_type_indexes);
        static::$image_type_indexes = $image_type_indexes;
        return $image_type_indexes;
    }

    /**
     * 「Content-Type」-画像形式定数の対応表を返却します。<br />
     * ※「application/octet-stream」に対応する画像形式定数には「0」を格納する。
     */
    public static function get_content_type_indexes() {
        if (static::$content_type_indexes) {
            return static::$content_type_indexes;
        }

        $extension_indexes = static::get_extension_indexes();
        $content_type_indexes = array_combine(
                array_map(
                        'image_type_to_mime_type', $extension_indexes
                ), $extension_indexes
        );
        $content_type_indexes['application/octet-stream'] = 0;
        foreach ($extension_indexes as $extension => $image_type) {
            $content_type = 'image/' . $extension;
            if (!isset($content_type_indexes[$content_type])) {
                $content_type_indexes[$content_type] = $image_type;
            }
        }
        ksort($content_type_indexes);
        static::$content_type_indexes = $content_type_indexes;
        return $content_type_indexes;
    }

    /**
     * 拡張子を受け取り、画像形式定数を返却します。<br />
     * ※当該メソッドは、拡張子の別称を判別する。
     *
     */
    public static function extension_to_image_type($extension) {
        $extension = static::fix_extension($extension);
        $indexes = static::get_extension_indexes();
        if (!isset($indexes[$extension])) {
            return null;
        }

        return $indexes[$extension];
    }

    /**
     * 拡張子を受け取り、Content-Typeを返却します。<br />
     * ※当該メソッドは、拡張子の別称を判別する。<br />
     * ※判別に失敗した場合、「application/octet-stream」を返却する。
     *
     */
    public static function extension_to_content_type($extension) {
        $image_type = static::extension_to_image_type($extension);
        if (is_null($image_type)) {
            return 'application/octet-stream';
        }

        return image_type_to_mime_type($image_type);
    }

    /**
     * 画像形式定数を受け取り、該当の画像がサポートされているかどうか判定する。
     *
     */
    public static function is_image_type_supported($image_type) {
        if (!$image_type) {
            return false;
        }

        $image_type_indexes = static::get_image_type_indexes();
        return isset($image_type_indexes[$image_type]);
    }

    /**
     * 拡張子を補正します。<br />
     * ①両端の半角スペースの除外<br />
     * ②ドット「.」の除外（例：.png→png）<br />
     * ③アルファベット小文字への変換（例：PNG→png）<br />
     * ④別称変換（例：jpg→jpeg）
     */
    private static function fix_extension($extension) {
        $extension = strtolower(trim(trim($extension), '.'));
        $extension_alias_indexes = static::get_extension_alias_indexes();
        if (isset($extension_alias_indexes[$extension])) {
            return $extension_alias_indexes[$extension];
        }

        return $extension;
    }

    /**
     * ファイルパスおよびURLから拡張子を抽出します。<br />
     * ※当該メソッドは、画像データの形式如何に関わらずファイルの名称のみを評価します。<br />
     *
     */
    public static function get_extension_from_path($file_or_url) {
        $pathinfo = pathinfo($file_or_url);

        if (!isset($pathinfo['extension'])) {
            return null;
        }

        $extension = static::fix_extension($pathinfo['extension']);

        return $extension;
    }

    /**
     * 画像データの内容を解析し、拡張子を判別して返却します。<br />
     *
     */
    public static function get_extension_from_real_data($file_or_url) {
        $type = @exif_imagetype($file_or_url);
        if ($type === false) {
            return null;
        }

        return image_type_to_extension($type, false);
    }

    /**
     * 画像の拡張子を判別します。<br />
     * ※任意引数「ファイルの名称」を指定した場合は、こちらから優先的に判別対象とします（判別できない場合は、実データが参照されます）<br />
     *
     * @param string $file_path 実データのパス
     * @param string $file_name ファイルの名称（省略可能、一時データの保存先などが拡張子を含まない場合に本来のファイル名を指定する）
     * @return string
     */
    public static function get_extension($file_path, $file_name = null) {
        $file_name = (is_null($file_name) ? $file_path : $file_name);
        $extension = static::get_extension_from_path($file_name);
        if ($extension) {
            return $extension;
        }

        return static::get_extension_from_real_data($file_path);
    }

    /**
     * 画像形式定数を受け取り、リサイズをサポートしているかどうか判定します。
     *
     * @param int $imagetype
     * @return boolean
     */
    public static function is_image_type_resizable($imagetype) {
        if (!$imagetype) {
            return false;
        }

        $flip_ary = array_flip(static::get_resizable_image_types());
        return isset($flip_ary[$imagetype]);
    }

    /**
     * 画像ファイルのパスを受け取り、画像リソースを生成して返却します。<br />
     * ※Bitmapの場合、処理に10数秒程度の時間を要する場合がある。
     *
     */
    public static function get_image_resource($path) {
        $image_type = @exif_imagetype($path);
        switch ($image_type) {
            case IMAGETYPE_PNG :
                return @imagecreatefrompng($path);
            case IMAGETYPE_JPEG :
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_GIF :
                return @imagecreatefromgif($path);
            case IMAGETYPE_XBM :
                return @imagecreatefromxbm($path);
            case IMAGETYPE_WBMP :
                return @imagecreatefromwbmp($path);
            case IMAGETYPE_BMP :
                return static::imagecreatefrombmp($path);
        }

        return false;
    }

    /**
     * 画像変換を行う。<br />
     * ※Bitmapへの変換はサポートしない。
     *
     * @param int $to_image_type 変換する画像形式定数
     * @param string $from_path
     * @param string $to_path
     * @return boolean
     */
    public static function createimage($to_image_type, $from_path, $to_path) {
        $function_map = array(
            IMAGETYPE_PNG => 'imagepng',
            IMAGETYPE_JPEG => 'imagejpeg',
            IMAGETYPE_GIF => 'imagegif',
            IMAGETYPE_XBM => 'imagexbm',
            IMAGETYPE_WBMP => 'imagewbmp'
        );
        if (!isset($function_map[$to_image_type])) {
            return false;
        }

        $resource = static::get_image_resource($from_path);
        if (!$resource) {
            return false;
        }

        $function_name = $function_map[$to_image_type];
        return @$function_name($resource, $to_path);
    }

    /**
     * 画像のファイル名を返却します。
     *
     * @param int $image_id
     * @param string $extension
     * @return string
     */
    public static function get_image_file_name($image_id, $extension) {
        return "${image_id}.${extension}";
    }

    /**
     * 画像ファイルの権限設定を補正します。<br />
     * http://jp1.php.net/manual/ja/function.chmod.php
     *
     * @param string $file_path
     * @param string $owner
     * @param int $permission 8進数表記の数字（PHP関数「chmod」の第2引数と同一の仕様）
     */
    public static function fix_image_file_permission($file_path, $owner = null, $permission = null) {
        $owner = (is_null($owner) ? static::IMAGE_FILE_OWNER_NAME : $owner);
        $permission = (is_null($permission) ? static::IMAGE_FILE_PERMISSION : $permission);
        // 権限変更（成功判定は行わない）
        @chmod($file_path, $permission);
        @chown($file_path, $owner);
        @chgrp($file_path, static::IMAGE_FILE_GROUP_NAME);
    }

    /**
     * 画像のサイズを返却します。<br />
     * ※FuelPHPの「Image->sizes()」は、画像データのファイル名が拡張子を含まない場合にサイズを判別できない問題があるので使わない。<br />
     *
     *
     * @param string $path
     * @return stdClass
     * @throws Exception_Logic
     */
    public static function get_image_sizes($path) {
        // FuelPHPの「Image::load($path)->sizes()」と同一構造の戻り値を作る
        $sizes = new stdClass();
        $sizes->width = null;
        $sizes->height = null;
        if (is_file($path) && !file_exists($path)) {
            return $sizes;
        }

        list($sizes->width, $sizes->height) = @getimagesize($path);
        return $sizes;
    }

    /**
     * URLから取得した画像データを一時ファイルへ保存します。
     *
     * @param string $url
     * @return string 一時ファイルのパス
     */
    private static function save_url_image_to_tempfile($url) {
        $contents = @file_get_contents($url);
        if (!$contents) {
            return null;
        }

        $temp_path = Util_Common::get_unique_temp_file_path($path);
        if (0 >= (int) @file_put_contents($temp_path, $contents)) {
            @unlink($temp_path);
            return null;
        }

        return $temp_path;
    }

}
