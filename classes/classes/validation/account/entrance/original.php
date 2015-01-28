<?php

class Validation_Account_Entrance_Original{

    /**
     * 独自アカウント・ログインAPI
     */
    public static function post_login(){
        $validation = Validation::forge();
        $validation->add_callable('customvalidation');

        $validation->add('login_id')
            ->add_rule('required')
            ->add_rule('min_length', 4)
            ->add_rule('max_length', 15)
            ->add_rule('valid_rule1')
        ;
        $validation->add('password')
            ->add_rule('required')
            ->add_rule('min_length', 8)
            ->add_rule('max_length', 20)
            ->add_rule('valid_rule1')
        ;
        $validation->add('device_info')
        ->add_rule('required')
        ->add_rule('min_length', 1)
        ->add_rule('max_length', 1000)
        ;
		$validation->add('os')
			->add_rule('required')
			->add_rule('min_length', 1)
			->add_rule('max_length', 1000)
        ;
        return $validation;
    }

    /**
     * 独自アカウント・会員登録API
     */
    public static function post_register(){
        $validation = Validation::forge();
        $validation->add_callable('customvalidation');

        $validation->add('login_id')
            ->add_rule('required')
            ->add_rule('min_length', 4)
            ->add_rule('max_length', 15)
            ->add_rule('valid_rule1')
        ;
        $validation->add('password')
            ->add_rule('required')
            ->add_rule('min_length', 8)
            ->add_rule('max_length', 20)
            ->add_rule('valid_rule1')
        ;
        $validation->add('nickname')
            ->add_rule('required')
            ->add_rule('min_length', 4)
            ->add_rule('max_length', 20)
            ->add_rule('valid_rule3')
        ;
        $validation->add('device_info')
        ->add_rule('required')
        ->add_rule('min_length', 1)
        ->add_rule('max_length', 1000)
        ;
        $validation->add('os')
        ->add_rule('required')
        ->add_rule('min_length', 1)
        ->add_rule('max_length', 1000)
        ;
        return $validation;
    }

    /**
     * 独自アカウント・メールアドレス確認API（リンク）
     */
    /*public static function get_confirm(){
        // TODO
        return null;
    }*/
}