<?php
/**
 * 汎用Utilクラス
 * @author k-kawaguchi
 */
class Util_Common{

    const TEMP_PERMISSION = 0777;
    const TEMP_OWNER = 'apache';

    /**
     * 実行されているプラットフォームがWindowsかどうか判定します。
     * 
     * @return boolean
     */
    public static function is_windows(){
        $os = strtoupper(PHP_OS);
        return ($os === 'WIN32' || $os === 'WINNT');
    }

    /**
     * 実行されているプラットフォームがLinuxかどうか判定します。
     * 
     * @return boolean
     */
    public static function is_linux(){
        $os = strtoupper(PHP_OS);
        return ($os === 'LINUX');
    }

    /**
     * 10進数のRGBを受け取り、16進数を結合した色コードを返却します。
     * 
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return string
     */
    public static function rgb_to_hex_string($red, $green, $blue){
        return dechex($red) . dechex($green) . dechex($blue);
    }

    /**
     * 与えられた文字から、制御文字を除外して返却します。
     * 
     * @param string $string
     * @return string
     */
    public static function string_remove_controll($string){
        return preg_replace('/[\x00-\x1f\x7f]/', '', $string);
    }

    /**
     * 重複しない一時ファイルの名称を返却します。<br />
     * ※ファイル名の形式： md5(「元になるファイル名の名称」)_md5(「ユニークなランダム文字」).「拡張子」
     * 
     * @param string $file_name 元になるファイル名の名称
     * @param string $extension 拡張子（省略可能）
     * @return string 一時ファイルの名称
     */
    public static function get_unique_temp_file_path($file_name, $extension = null){
        // 一時ファイル保存先のチェック
        $temp_dir = static::check_temp_dir();
        $is_detect_more = true;
        $file_path = null;
        $file_name_hash = md5($file_name);
        $extension = (is_null($extension) ? '' : '.' . $extension);
        while($is_detect_more){
            $unique_hash = md5("${file_name_hash}_" . uniqid() . '_' . rand());
            $file_path   = $temp_dir . $file_name_hash . '_' . $unique_hash . $extension;
            // 他のファイルと名前が重複したら再試行
            $is_detect_more = file_exists($file_path);
        }
        return $file_path;
    }

    /**
     * 
     * @param type $contents
     * @param type $file_name
     * @param type $extension
     * @param type $owner
     * @param type $permission
     * @return type
     * @throws Exception_Logic
     * @throws Exception
     */
    public static function get_unique_temp_file_path_put_contents($contents, $file_name, $extension = null, $owner = null, $permission = null){
        if(!$contents){
            throw new Exception_Logic('Suplied contents can not be empty.');
        }

        $temp_path = static::get_unique_temp_file_path($file_name, $extension);
        if((int)@file_put_contents($temp_path, $contents) <= 0){
            throw new Exception("Failed to put contents to the path '${temp_pat}'.");
        }

        $owner = (is_null($owner) ? static::TEMP_OWNER : $owner);
        $permission = (is_null($permission) ? static::TEMP_PERMISSION : $permission);
        Util_Image::fix_image_file_permission($temp_path, $owner, $permission);
        return $temp_path;
    }

    public static function get_unique_temp_dir($name){
        // 一時ファイル保存先のチェック
        $temp_dir = static::check_temp_dir();
        $is_detect_more = true;
        $path = null;
        $name_hash = md5($name);
        while($is_detect_more){
            $unique_hash = md5("${name_hash}_" . uniqid() . '_' . rand());
            $path = $temp_dir . $name_hash . '_' . $unique_hash . '/';
            $is_detect_more = file_exists($path);
        }
        return $path;
    }

    /**
     * 一時ファイルのディレクトリの存在確認を行い、無ければ作成します。
     * 
     * @throws Exception
     */
    public static function check_temp_dir(){
        $temp_dir = Config::get('temp_path');
        if(!is_dir($temp_dir)){
            if(!@mkdir($temp_dir, 0777, true)){
                throw new Exception("Could not make directory '" . $temp_dir . "'.");
            }
        }
        return '/' . trim($temp_dir, '/') . '/';
    }

    /**
     * バイト数を受け取り、キロバイトへ変換します。
     * 
     * @param real $byte
     * @return real キロバイト
     */
    public static function byte_to_kbyte($byte){
        return ((real)$byte / 1024);
    }

    /**
     * バイト数を受け取り、メガバイトへ変換します。
     * 
     * @param real $byte
     * @return real メガバイト
     */
    public static function byte_to_mbyte($byte){
        return (static::byte_to_kbyte($byte) / 1024);
    }

    /**
     * バイト数を受け取り、ギガバイトへ変換します。
     * 
     * @param real $byte
     * @return real ギガバイト
     */
    public static function byte_to_gbyte($byte){
        return (static::byte_to_mbyte($byte) / 1024);
    }

    public static function kbyte_to_byte($kbyte){
        return ((real)$kbyte * 1024);
    }

    public static function mbyte_to_byte($mbyte){
        return (static::kbyte_to_byte($mbyte) * 1024);
    }

    public static function gbyte_to_byte($gbyte){
        return (static::mbyte_to_byte($gbyte) * 1024);
    }

    public static function seconds_to_minutes($seconds){
        return ((real)$seconds / 60);
    }

    public static function seconds_to_hours($seconds){
        return (static::seconds_to_minutes($seconds) / 60);
    }

    public static function seconds_to_days($seconds){
        return (static::seconds_to_hours($seconds) / 24);
    }

    public static function seconds_to_weeks($seconds){
        return (static::seconds_to_days($seconds) / 7);
    }

    public static function seconds_to_months($seconds){
        return (static::seconds_to_years($seconds) * 12);
    }
  
    public static function seconds_to_years($seconds){
        return (static::seconds_to_days($seconds) / 365);
    }

    public static function minutes_to_seconds($minutes){
        return ((real)$minutes * 60);
    }

    public static function hours_to_seconds($hours){
        return (static::minutes_to_seconds(60) * $hours);
    }

    public static function days_to_seconds($days){
        return (static::hours_to_seconds(24) * $days);
    }

    public static function weeks_to_seconds($weeks){
        return (static::days_to_seconds(7) * $weeks);
    }

    public static function months_to_seconds($months){
        return ((static::years_to_seconds(1) / 12) * $months);
    }

    public static function years_to_seconds($years){
        return (static::days_to_seconds(365) * $years);
    }
    
    	
	/**
	 * UESR AGENT
	 *
	 * @return boolean
	 */
	
	public static function is_mobile_request()   
    {   
      $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';   
      $mobile_browser = '0';   
      if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))   
        $mobile_browser++;   
      if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))   
        $mobile_browser++;   
      if(isset($_SERVER['HTTP_X_WAP_PROFILE']))   
        $mobile_browser++;   
      if(isset($_SERVER['HTTP_PROFILE']))   
        $mobile_browser++;   
      $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));   
      $mobile_agents = array(   
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',   
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',   
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',   
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',   
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',   
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',   
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',   
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',   
            'wapr','webc','winw','winw','xda','xda-'   
            );   
      if(in_array($mobile_ua, $mobile_agents))   
        $mobile_browser++;   
      if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)   
        $mobile_browser++;   
      // Pre-final check to reset everything if the user is on Windows   
      if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)   
        $mobile_browser=0;   
      // But WP7 is also Windows, with a slightly different characteristic   
      if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)   
        $mobile_browser++;   
      if($mobile_browser>0)   
        return true;   
      else  
        return false;   
    }

}