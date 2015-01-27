<?php
/**
 * デバッグ機能を提供するUtil
 * 
 * @author k-kawaguchi
 */
class Util_Debug{

    /**
     * デバッグモードが有効かどうか判定します。
     * 
     * @return boolean
     */
    public static function is_debug_enable(){
        return in_array(Fuel::$env, array('local', 'test', Fuel::DEVELOPMENT));
    }

    /**
     * デバッグ用自動フォローを実行します。
     * 
     * @param Controller $context
     * @param Auth_Driver_Base $auth_driver
     * @return boolean
     */
    public static function regist_debug_auto_follow(Controller $context, Auth_Driver_Base $auth_driver){
        if(!static::is_debug_enable()){
            return false;
        }

        $executor_user_id = $auth_driver->get_user_id();
        Log::debug('Starting regist auto_follow for debug mode.');
        $domain = new Domain_User_Account_Relation($context, $auth_driver);
        foreach(Config::get('debug_auto_follow', array()) as $user_id){
            if((string)$executor_user_id == (string)$user_id){
                continue;
            }

            $domain->switch_follow($user_id, Domain_User_Account_Relation::FOLLOW_FLG_ON);
            Log::debug("Finished regist folower(user_id: ${user_id}).");
        }
        return true;
    }

}