<?php
/**
 * const定数を集積したクラス
 */
class Constants_Code{

    const SUCCESS = 0; /* 成功 */
    const UNAUTHENTICATED = 1; /* 認証エラー */
    const PARAM_INVALID   = 2; /* パラメータエラー */


    /* 1000番台 フォト関連 */
    //const PHOTO_NOTFOUND  = 1001; /* 写真が見つからない */
    //const PHOTO_REMOVED   = 1002; /* 写真が削除済み */
    //const PHOTO_NOTPUBLIC = 1003; /* 写真が公開ではない（公開の写真しか参照できない状況で非公開の写真を参照した場合） */
    //const PHOTO_NOTOWN    = 1004; /* 写真の持ち主にしか許可されない操作を持ち主ではないユーザが試みた場合 */
    //const PHOTO_COMMENT_NOTFOUND   = 1005; /* 写真コメントが見つからない */
    //const PHOTO_COMMENT_REMOVED    = 1006; /* 写真コメントが削除済み */
    //const PHOTO_COMMENT_NOTOWN     = 1007; /* 写真コメントの発言者にしか許可されない操作を発言者ではないユーザが試みた場合 */
    //const PHOTO_FAVORITE_NOTPUBLIC = 1008; /* 写真が公開ではない（公開の写真しか参照できない状況で非公開の写真を参照した場合） */

    /* 2000番台 おもフォト */
    const OMOFHOTO_NOTFOUND       = 2001; /* おもフォト評価時：おもフォトが見つからない */
    const OMOFHOTO_PHOTO_NOTFOUND = 2002; /* おもフォト登録時：元フォトが見つからない */


    /* 4000番台 アカウント関連 */
    const LOGINTOKEN_INVAILD  = 4001; /* credentialの値が不正 */

    /* 4100番台 オリジナルアカウント関連 */
    const LOGINID_CONFLICT  = 4101; /* オリジナルアカウント登録時：ログインIDが使用済 */
    const LOGIN_INPUT_WRONG = 4102; /* オリジナルアカウントログイン時：パスワードまたはログインIDの不一致など */
    const PASSWORD_WRONG    = 4103; /* オリジナルアカウントパスワード変更時：変更後PWと変更後PW（確認用）が異なっている */
    const PASSWORD_UNMATCH  = 4104; /* オリジナルアカウントパスワード変更時：変更前PWが誤っている */
    const PASSWORD_CONFLICT = 4105; /* オリジナルアカウントパスワード変更時：変更前PWと変更後PWが同じ */

    /* 4200番台 Twitterアカウント関連 */
    const AUTH_TWITTER_FAILED          = 4201; /* Twitterアカウントでの登録・ログイン時：認証情報が不正 */
    const TWITTER_ALREADY_REGISTERED   = 4202; /* Twitterアカウントでの登録時：同Twitterアカウントで既に登録済み */
    const TWITTER_ACCOUNT_NONEREGISTER = 4203; /* Twitterアカウントでのログイン時：Twitter認証は問題ないが、このIDでのおもタスに登録はされていない */
    //const REQUEST_TWITTER_FAILED       = 4202; /* Twitterアカウントでの登録・ログイン時：TwitterAPIとの認証を開始できない */
    //const TWITTER_AUTH_EXPIRED         = 4204; /* Twitterアカウントでの登録・ログイン時：Twitter認証が時間切れ */
    //const TWITTER_AUTH_CANCELED        = 4206; /* Twitterアカウントでのログイン時：ユーザ自身がTwitter認証をキャンセルした */
    //const ACCOUNT_NOTSIGNIN            = 4211; /* Twitter認証：該当アカウントへサインインしていない */
    //const ACCOUNT_ALREADY_SIGNIN       = 4212; /* Twitter認証：該当アカウントへ既にサインインしている */
    //const ACCOUNT_NEUTRAL_SIGNIN       = 4213; /* Twitter認証：何れのアカウントもサインアウト状態 */
    //const ACCOUNT_UNMATCH              = 4214; /* Twitter認証：異なるTwitter認証アカウントあり */

    /* 4300番台 Facebookアカウント関連 */
    const AUTH_FACEBOOK_FAILED          = 4301; /* Facebookアカウントでの登録・ログイン時：認証情報が不正 */
    const FACEBOOK_ALREADY_REGISTERED   = 4302; /* Facebookアカウントでの登録時：同Facebookアカウントで既に登録済み */
    const FACEBOOK_ACCOUNT_NONEREGISTER = 4303; /* Facebookアカウントでのログイン時：Facebook認証は問題ないが、このIDでのおもタスに登録はされていない */


    /* 5000番台 ユーザ関連 */
    const TARGET_USER_NOTFOUND = 5001; /* 対象ユーザが見つからない */
	const TARGET_USER_PROFILEIMAGE_NOTFOUND = 5002; /* 対象ユーザーのプロフィール画像が見つからない	 */


    /* 6000番台 画像関連 */
    const UNSUPPORTED_IMAGE_EXTENSION = 6001; /* 各種画像アップロード時：サポート外の拡張子 */
    const IMAGE_UPLOAD_SIZE_EXCESS    = 6002; /* 各種画像アップロード時：画像容量が大きすぎる */
    const IMAGE_UPLOAD_ERROR          = 6003; /* 各種画像アップロード時：アップロードエラー */
    const IMAGE_MOVE_FAILED           = 6004; /* 各種画像アップロード時：アプリ内一時フォルダへの画像移動に失敗 */
    const UPLOAD_IMAGE_NOTFOUND       = 6005; /* 各種画像アップロード時：アップロードした画像のデータが存在しない */
    const IMAGE_S3_UPLOAD_FAILED      = 6006; /* 各種画像アップロード時：S3へのファイル転送に失敗 */


    /* 7000番台　お知らせ関連 */


    /* 8000番台 ランキング関連 */
    const RANKING_NOTFOUND = 8001; /* 指定した日付のランキングが見つからない */


    const NOTIMPLEMENTED = 99997; /* 機能が未実装 */
    const SERVER_MAINTAINANCE = 99998; /* メンテナンス中 */
    const UNKNOWN_ERROR = 99999; /* 不明なエラー */

}