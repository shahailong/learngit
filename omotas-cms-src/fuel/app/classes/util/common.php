<?php
/**
 * 汎用Utilクラス
 * @author 
 */
 
include_once(APPPATH . "vendor/colors.inc.php");
include_once(APPPATH . "vendor/GifManipulator.php");

// S3関連のSDKの読み込み
require_once("AWSSDKforPHP/aws.phar");
use Aws\S3\S3Client;
use Aws\Common\Enum\Region;
use Aws\S3\Exception\S3Exception;
use Guzzle\Http\EntityBody;


class Util_Common{

    /**
     * フォルダ削除
     * 
     * @param real $dir
     * @return real 
     */
    public static function deldir($dir){
		//フォルダ下のファイルを削除
		$dh=opendir($dir);
		while ($file=readdir($dh)) {
		  if($file!="." && $file!="..") {
		    $fullpath=$dir."/".$file;
		    if(!is_dir($fullpath)) {
		        unlink($fullpath);
		    } else {
		        Util_Common::deldir($fullpath);
		    }
		  }
		}

		closedir($dh);
		//フォルダを削除
		if(Config::get('upload_path') == $dir){
			return true;
		}
		if(rmdir($dir)) {
		  return true;
		} else {
		  return false;
		}
	}
	
	
    /**
     * Zipファイル解凍
     * 
     * @param real $file
     * @return real 
     */
    public static function unzipfile($specialId,$file,$YmdHis){
		//特集紹介ページ
		$html_file = "";
		//フォルダ下のファイルを削除
		$unzip = new \Unzip;
        try
        {
            //$file_locations = $unzip->extract('upload/pc_page.zip');
            $file_locations = $unzip->extract($file);
            foreach( $file_locations as $file_path )
            {
                //upload/1/content.html
                $file_path = mb_convert_encoding($file_path, 'UTF-8', 'SJIS-win');
                Log::error('unzipfile file_path:'.$file_path);
                
                // upload/1/content.html→1/content.html
                $upload_path_len = strlen(Config::get('upload_path'));
                $file_path_no_upload = substr($file_path,$upload_path_len);
                Log::error('unzipfile file_path_no_upload:'.$file_path_no_upload);
                
                $record_directory_key = Config::get('s3_public_special_collection_directory_name') ."/" .$specialId ."/page_detail/" .$YmdHis."/".$file_path_no_upload;
                Log::error('unzipfile record_directory_key:'.$record_directory_key);
				// S3に転送
				$result = Util_Common::file_forward_to_s3($record_directory_key,$file_path);
				
				$uploadfileName = basename($file_path);
				//ファイル名がcontent.htmlのＵＲＬを生成
                if($uploadfileName == Config::get('special_collection_page_file_name')){
					$html_file = Config::get('media_cdn_url').$record_directory_key;
				}
				//解凍したファイルを削除
	            //unlink($file_path);
            }
            //zipファイル削除
            //unlink($file);
        }
        catch( \Exception $e )
        {
            Log::error('unzipfileZIP解凍エラーです'.$e);
        }
        return $html_file;
    }
    
     /**
     * ファイルをS3に転送します。
     *
     * @param String $key 転送先ファイルパス
     * @param String $tmpfile 転送元ファイルパス
     *
     */
    public static function file_forward_to_s3($key,$tmpfile) {

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
        	throw new Exception_Service("S3 File Put Error (Key:" .$key .") ERROR:" .$e->getMessage() , Constants_Code::IMAGE_S3_UPLOAD_FAILED);
        }

        // 一時ファイルの削除
        //@unlink($tmpfile);

    }
    
     /**
     * 特集バナーアップロード処理
     *
     * @param String $specialId 特集ＩＤ
     * @param String $uploadfile アップロードファイル
     * @param String $bannerImageName バナー名、ＳＰの場合、banner_sp.jpg,ＰＣの場合、banner_pc.jpg
     *
     */
    public static function upload_special_banner_image($specialId,$uploadfile,$bannerImageName,$YmdHis) {

		$banner_image_url = "";
		
		Log::error('upload_special_banner_image start specialId:'.$specialId.' bannerImageName:'.$bannerImageName);
		
		Log::error('filetype:'.$uploadfile["type"].' fileSize:'.$uploadfile["size"].' fileerror:'.$uploadfile["error"]." tmpName:".$uploadfile["tmp_name"]);
		//ファイルタイプチェック
		if (($uploadfile["type"] == "image/jpeg")
		|| ($uploadfile["type"] == "image/pjpeg"))
		{
			if ($uploadfile["error"] > 0)
			{
				Log::error('upload_special_banner_image Return Code:'.$uploadfile["error"]);
			}
			else
		    {
				///var/www/omotas_admin_dev/public/upload
				$saveFile = Config::get('upload_path') . $uploadfile["name"];
				Log::error('upload_special_banner_image saveFile :'.$saveFile);
				if (file_exists($saveFile))
				{
					Log::error('upload_special_banner_image already exists:'.$uploadfile["name"]);
				}
				else
				{
					move_uploaded_file($uploadfile["tmp_name"],$saveFile);
					//ファイルをS3に転送します。
					$record_directory_key = Config::get('s3_public_special_collection_directory_name') ."/" .$specialId ."/" .$YmdHis."/".$bannerImageName;
            	    Log::error('upload_special_banner_image record_directory_key:'.$record_directory_key);
					// S3に転送
					$result = Util_Common::file_forward_to_s3($record_directory_key,$saveFile);
					
					$banner_image_url = Config::get('media_cdn_url').$record_directory_key;
				}
			}
		}
		else
		{
			Log::error('upload_special_banner_image Invalid file :');
		}
		Log::error('upload_special_banner_image end banner_image_url:'.$banner_image_url);
		return $banner_image_url;

    }	
     /**
     * Zipファイルアップロード処理
     *
     * @param String $specialId 特集ＩＤ
     * @param String $uploadfile アップロードファイル
     * @param String $bannerImageName バナー名、ＳＰの場合、banner_sp.jpg,ＰＣの場合、banner_pc.jpg
     *
     */
    public static function upload_zip_file($specialId,$uploadfile,$YmdHis) {
		
		$html_file = "";

		Log::error('upload_zip_file start specialId:'.$specialId." YmdHis:".$YmdHis);
		
		Log::error('filetype:'.$uploadfile["type"].' fileSize:'.$uploadfile["size"].' fileerror:'.$uploadfile["error"]." tmpName:".$uploadfile["tmp_name"]);
		//ファイルタイプチェック
		if ($uploadfile["type"] == "application/zip")
		{
			if ($uploadfile["error"] > 0)
			{
				Log::error('upload_zip_file Return Code:'.$uploadfile["error"]);
			}
			else
		    {
				///var/www/omotas_admin_dev/public/upload
				$saveFile = Config::get('upload_path') . $uploadfile["name"];
				Log::error('upload_zip_file saveFile :'.$saveFile);
				if (file_exists($saveFile))
				{
					Log::error('upload_zip_file already exists:'.$uploadfile["name"]);
				}
				else
				{
					move_uploaded_file($uploadfile["tmp_name"],$saveFile);
					//Zipファイル解凍&S3に転送
					$html_file = Util_Common::unzipfile($specialId,$saveFile,$YmdHis);
				}
			}
		}
		else
		{
			Log::error('upload_zip_file Invalid file :');
		}
		Log::error('upload_zip_file end html_file:'.$html_file);
		
		return $html_file;

    }	
}


