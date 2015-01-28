<?php
class CustomValidation
{

    /**
     * バリデーションルール1
     * 半角英数、-(ハイフン)、_(アンダースコア)、.(ピリオド)のみ
     */
    public static function _validation_valid_rule1($val){
    	if(empty($val)){
    		return true;
    	}

    	mb_regex_encoding("UTF-8");

    	$pattern_str = "a-zA-Z0-9"; //半角英数
    	$pattern_str .= "-_.";      //半角一部記号

    	return (preg_match("/^[" .$pattern_str ."]+$/u", $val) > 0);
    }


    /**
     * バリデーションルール2
     * 全角、半角英数、-(ハイフン)、_(アンダースコア)、.(ピリオド)のみ
     */
    public static function _validation_valid_rule2($val){
    	if(empty($val)){
    		return true;
    	}
    	mb_regex_encoding("UTF-8");

    	$pattern_str = "a-zA-Z0-9"; //半角英数
    	$pattern_str .= "-_.";      //半角一部記号
    	$pattern_str .= "ぁ-んァ-ヶー一-龠"; //ひらがな、カタカナ、漢字

    	return (preg_match("/^[" .$pattern_str ."]+$/u", $val) > 0);
    }


    /**
     * バリデーションルール3
     * 全角、半角英数記号、半角カナ
     */
    public static function _validation_valid_rule3($val){
        if(!$val){
            return true;
        }
        mb_regex_encoding("UTF-8");

        $pattern_str = "!-~"; //半角英数記号
        $pattern_str .= "\\"; //単位
        $pattern_str .= "ｦ-ﾟ"; //半角カナ
        $pattern_str .= "ぁ-んァ-ヶー一-龠"; //ひらがな、カタカナ、漢字
        $pattern_str .= "〇○◇□△▽☆●◆■▲▼★◎◯♂♀〒"; //マーク
        $pattern_str .= "（）〔〕［］｛｝〈〉《》「」『』【】‘’“”"; //括弧
        $pattern_str .= "→←↑↓⇒⇔"; //矢印
        $pattern_str .= "…‥、。，．・：；？！゛゜´｀¨＾ヽヾゝゞ〃°′″"; //点
        $pattern_str .= "￥＄￠￡％‰℃Å"; //単位
        $pattern_str .= "＋－±×÷＝≒≠≦≧＜＞≪≫∞∽∝∴∵∈∋⊆⊇⊂⊃∪∩∧∨￢∀∃∠⊥⌒∂∇≡√∫∬"; //学術記号
        $pattern_str .= "ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩαβγδεζηθικλμνξοπρστυφχψω"; //ギリシャ文字
        $pattern_str .= "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя"; //ロシア文字
        $pattern_str .= "─│┌┐┘└├┬┤┴┼━┃┏┓┛┗┣┳┫┻╋┠┯┨┷┿┝┰┥┸╂"; //罫線
        $pattern_str .= "＃＆＊＠§※〓♯♭♪†‡¶仝々〆ー～￣＿―‐∥｜／＼"; //その他記号

    	return (preg_match("/^[" .$pattern_str ."]+$/u", $val) > 0);
    }


     /**
     * 日付のフォーマットチェック
     */
    public static function _validation_valid_date($val){
        if(!$val){
            return true;
        }
    	return (preg_match("/^([0-9]{4})[\-](0?[0-9]|1[0-2])[\-]([0-2]?[0-9]|3[01])$/", $val) > 0);
    }

}