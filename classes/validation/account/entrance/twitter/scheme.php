<?php
/**
 * 
 */
class Validation_Account_Entrance_Twitter_Scheme{

    /**
     * 
     * @return Validation
     */
    public static function post_request(){
        $validation = Validation::forge();
        $validation->add('auth_type')
            ->add_rule('required')
            ->add_rule('match_value', array(1, 2))
        ;
        return $validation;
    }

    /**
     * 
     * @return Validation
     */
    public static function get_callback(){
        // TODO
        return null;
    }

}