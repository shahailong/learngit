<?php

class Constants_Common{
    //const LOGIN_CREDENTIAL_HEADER_KEY = 'X-HTTP-OMOTAS-LOGIN-CREDENTIAL';
    const LOGIN_CREDENTIAL_HEADER_KEY = 'X-Http-Omotas-Login-Credential';

    const RESULT_CODE_RESPONSE_KEY = 'result_code';
    const TIME_RESPONSE_KEY = 'time';
    //const REGISTER_MAIL_SENDER_ADDRESS = 'a-kido@ibrains.co.jp';
    //const REGISTER_MAIL_SENDER_NAME = '3rdapp';

    const SELF_SERVER_IMAGE_URL_PATH = '/image/index/';
    const SELF_SERVER_IMAGE_RESIZE_URL_PATH = '/image/resize/';
    const SELF_SERVER_PHOTO_URL_PATH = '/photo/image/';

    const PUBLIC_CONTENTS_ROOT_PATH = 'contents/';
    const SELF_SERVER_PHOTO_RESIZE_URL_PATH = '/photo/resize/';

    const URL_SCHEME_PROTOCOL = 'thirdapp';
    const URL_SCHEME_TWITTER_FINISH = 'twitter_finish';
    const URL_SCHEME_TWITTER_REGISTER = 'twitter_register';

    const IMAGEFILE_DELETE_FLG_FALSE = 0;
    const IMAGEFILE_DELETE_FLG_TRUE  = 1;

    const PHOTO_DEFAULT_RESIZE_WIDTH = 200;
    const PHOTO_DEFAULT_RESIZE_HEIGHT = 200;

    const PROFILE_RESIZE_WIDTH = 400;
    const PROFILE_RESIZE_HEIGHT = 400;
}
