<style type="text/css" charset="utf-8">

table {
	font-size: 1em;
}

.demo-description {
	clear: both;
	padding: 12px;
	font-size: 1.3em;
	line-height: 1.4em;
}

.ui-draggable, .ui-droppable {
	background-position: top;
}

</style>

<script type="text/javascript">
    jQuery(function () {
        jQuery('#start_date').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });

    });
</script>
<script type="text/javascript">
    jQuery(function () {
        jQuery('#end_date').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });

    });
</script>
<script type="text/javascript">
    jQuery(function () {
        jQuery('#nowmark_start_date').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });

    });
</script>
<script type="text/javascript">
    jQuery(function () {
        jQuery('#nowmark_end_date').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });

    });
</script>


<script type="text/javascript">

function checkphotolist(list){
  //数字とカマン
  var checkrule=/^[\d|,]*$/;
  //数字の場合
  if(checkrule.test(list)){
    return true;
  }else{
    return false;
  }
}
function checkZipFile (input,type){
	try {
		$("#errorMessage").empty();
		if (!/\.(zip)$/.test(input.value)) { 
			if(type == 1){//特集バナー(SP)の場合
				//alert("特集紹介画面(SP)はzipファイルを選択してください。"); 
				$("#errorMessage").append("特集紹介画面(SP)はzipファイルを選択してください。</br>");
			}else{//特集バナー(PC)
				//alert("特集紹介画面(PC)はzipファイルを選択してください。"); 
				$("#errorMessage").append("特集紹介画面(PC)はzipファイルを選択してください。</br>");
			}            
            input.value = ""; 
            return false; 
        } 
	} catch (err) {
		console.log(err);
	}
}

function preview_image (input,type){
	try {
		$("#errorMessage").empty();
		//if (!/\.(gif|jpg|jpeg|png|GIF|JPG|PNG)$/.test(input.value)) { 
		if (!/\.(jpg)$/.test(input.value)) { 
			if(type == 1){//特集バナー(SP)の場合
				//alert("特集バナー(SP)はjpgファイルを選択してください。"); 
				$("#errorMessage").append("特集バナー(SP)はjpgファイルを選択してください。</br>");
			}else{//特集バナー(PC)
				//alert("特集バナー(PC)はjpgファイルを選択してください。"); 
				$("#errorMessage").append("特集バナー(PC)はjpgファイルを選択してください。</br>");
			}            
            input.value = ""; 
            return false; 
        } 
		var reader = new FileReader;
		if(type == 1){//特集バナー(SP)の場合
			reader.addEventListener('load', onLoad_Reader_Sp, false);
		}else{//特集バナー(PC)
			reader.addEventListener('load', onLoad_Reader_Pc, false);
		}
		reader.readAsDataURL(input.files[0]);
	} catch (err) {
		console.log(err);
	}
}

var onLoad_Reader_Sp = {
	size: NaN,

	handleEvent: function (e) {
		var t = e.target;

		if (t instanceof FileReader) {
		this.size = e.total;
		var img = new Image;
		img.addEventListener('load', this, false);
		img.src = t.result;
		return;
		}

		if (t instanceof Image) {
		var img = e.target;
		//alert([ 'size:' + this.size, 'width:' + img.naturalWidth, 'height:' + img.naturalHeight ]);
		if(img.naturalWidth != 640){
			//alert("特集バナー(SP)の幅は640の画像ファイルを選択してください。"); 
			$("#errorMessage").append("特集バナー(SP)の幅は640の画像ファイルを選択してください。</br>");
			document.getElementById("sp_banner_file").value = "";
		}
		if(img.naturalHeight != 126){
			//alert("特集バナー(SP)の高さは126の画像ファイルを選択してください。"); 
			$("#errorMessage").append("特集バナー(SP)の高さは126の画像ファイルを選択してください。</br>");
			document.getElementById("sp_banner_file").value = "";
		}
		
		return;
		}
	}
};
var onLoad_Reader_Pc = {
	size: NaN,

	handleEvent: function (e) {
		var t = e.target;

		if (t instanceof FileReader) {
		this.size = e.total;
		var img = new Image;
		img.addEventListener('load', this, false);
		img.src = t.result;
		return;
		}

		if (t instanceof Image) {
		var img = e.target;
		alert([ 'size:' + this.size, 'width:' + img.naturalWidth, 'height:' + img.naturalHeight ]);
		if(img.naturalWidth != 1024){
			//alert("特集バナー(PC)の幅は1024の画像ファイルを選択してください。"); 
			$("#errorMessage").append("特集バナー(PC)の幅は1024の画像ファイルを選択してください。</br>");
			document.getElementById("pc_banner_file").value = "";
		}
		if(img.naturalHeight != 162){
			//alert("特集バナー(PC)の高さは162の画像ファイルを選択してください。"); 
			$("#errorMessage").append("特集バナー(PC)の高さは162の画像ファイルを選択してください。</br>");
			document.getElementById("pc_banner_file").value = "";
		}
		return;
		}
	}
};


$(document).ready(function()
{
	
	$("#special_type option[value='3']").attr("selected", true); 
	
    $("#special_type").change(function(e){
    
    	var selectValue = $("#special_type").val();
    	
    	$("#photoArea").css("display","block");
		$("#omophotoArea").css("display","block");
		$("#special_page_title_sp").css("display","");
		$("#special_page_file_sp").css("display","");
		$("#special_page_title_pc").css("display","");
		$("#special_page_file_pc").css("display","");
		/*
		$("#special_page_title_sp").removeClass("display");
		$("#special_page_file_sp").removeClass("display");
		$("#special_page_title_pc").removeClass("display");
		$("#special_page_file_pc").removeClass("display");
		*/

    	switch(selectValue){
			case "1"://特集紹介画面→動的画面(おもフォトのみ)
				$("#photoArea").css("display","none");
				break;
			case "2"://特集紹介画面→動的画面(フォト・おもフォト)
				break;
			case "3"://静的画面
				$("#photoArea").css("display","none");
				$("#omophotoArea").css("display","none");
				break;
			case "4"://動的画面(おもフォトのみ)
				$("#photoArea").css("display","none");
				$("#special_page_title_sp").css("display","none");
				$("#special_page_title_pc").css("display","none");
				$("#special_page_file_sp").css("display","none");
				$("#special_page_file_pc").css("display","none");
				break;
			case "5"://動的画面(フォト・おもフォト)
				$("#special_page_title_sp").css("display","none");
				$("#special_page_title_pc").css("display","none");
				$("#special_page_file_sp").css("display","none");
				$("#special_page_file_pc").css("display","none");
				break;
			case "6"://お笑い芸人投稿まとめ
				$("#photoArea").css("display","none");
				$("#omophotoArea").css("display","none");
				$("#special_page_title_sp").css("display","none");
				$("#special_page_title_pc").css("display","none");
				$("#special_page_file_sp").css("display","none");
				$("#special_page_file_pc").css("display","none");
				break;
			default:
		}
    	
    	
    });
    
    $("#sc_save").live("click",function(e){
		
		var check_result = 0;//0:OK,1:NG
		$("#errorMessage").empty();
		//エラーチェック
        var name = $("#name").val();
        var introduction = $("#introduction").val();
        var start_date = $("#start_date").val();
        var end_date = $("#end_date").val();
		var special_type = $("#special_type").val();
        var nowmark_start_date = $("#nowmark_start_date").val();
        var nowmark_end_date = $("#nowmark_end_date").val();
        var photo_list = $("#photo_list").val();
        var omophoto_list = $("#omophoto_list").val();

        if(name.length<3 || name.length>50){
			$("#errorMessage").append("特集名は3～50文字で入力してください。</br>");
			check_result = 1;
		}
        if(introduction.length<3 || introduction.length>255){
			$("#errorMessage").append("コメントは3～255文字で入力してください。</br>");
			check_result = 1;
		}
        if(start_date == ""){
			$("#errorMessage").append("掲載開始日を入力してください。</br>");
			check_result = 1;
		}
        if(end_date == ""){
			$("#errorMessage").append("掲載終了日を入力してください。</br>");
			check_result = 1;
		}
		if(start_date != "" && end_date != "" && start_date >= end_date){
			$("#errorMessage").append("掲載終了日は掲載開始日より大きく設定してください。</br>");
			check_result = 1;
		}
        if(nowmark_start_date == ""){
			$("#errorMessage").append("開催開始日を入力してください。</br>");
			check_result = 1;
		}
        if(nowmark_end_date == ""){
			$("#errorMessage").append("開催終了日を入力してください。</br>");
			check_result = 1;
		}
		if(nowmark_start_date != "" && nowmark_end_date != "" && nowmark_start_date >= nowmark_end_date){
			$("#errorMessage").append("開催終了日は開催開始日より大きく設定してください。</br>");
			check_result = 1;
		}
		var special_type = $("#special_type").val();
		if(special_type == "2" || special_type == "5"){
			if(!checkphotolist(photo_list)){
				$("#errorMessage").append("フォトはカンマ区切りで数字を入力してください。</br>");
				check_result = 1;
			}
		}
		if(special_type == "1" || special_type == "2" || special_type == "4" || special_type == "5"){
			if(!checkphotolist(omophoto_list)){
				$("#errorMessage").append("おもフォトはカンマ区切りで数字を入力してください。</br>");
				check_result = 1;
			}
		}
		if($("#sp_banner_file").val() == ""){
			$("#errorMessage").append("特集バナー(SP)はjpgファイルを選択してください。</br>");
			check_result = 1;
		}
		if($("#pc_banner_file").val() == ""){
			$("#errorMessage").append("特集バナー(PC)はjpgファイルを選択してください。</br>");
			check_result = 1;
		}
		if(special_type == "1" || special_type == "2" || special_type == "3"){
			if($("#sp_special_page_file").val() == ""){
				$("#errorMessage").append("特集紹介画面(SP)を選択してください。</br>");
				check_result = 1;
			}
			if($("#pc_special_page_file").val() == ""){
				$("#errorMessage").append("特集紹介画面(PC)を選択してください。</br>");
				check_result = 1;
			}
		}
		
		//正常の場合、
		if(check_result == 0){
			//特集データを登録
			$("#sc_submit").click();
		}
    });



});


</script>



<div>

<form action="update" method="post" enctype="multipart/form-data">

<input type="submit" id="sc_submit" style="display:none"/>

<table border="0" cellspacing="0" cellpadding="0" >
	<tr>
		<td style="text-align: center;">
			<a href="index?check=XXXXXXX">
			<input type="button" id="sc_preview" name="sc_preview" value="戻る" size="100" class="btn btn-lg btn-primary btn-block" style="width:150px;"/>
			</a>
		</td>
		<td style="text-align: center;width:30px;">
		</td>
		<td style="text-align: center;">
			<input type="button" id="sc_save" name="sc_save" value="保存" size="100" class="btn btn-lg btn-primary btn-block" style="width:150px;"/>
		</td>
		<td style="text-align: center;width:30px;">
		</td>
		<td style="text-align: left;">
			<div id="errorMessage" style="color:red;"></div>
		</td>
	</tr>
</table>

<label for="fm-login-id">特集ID：XXXX</label>


<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="180px" style="text-align: center;">
			<label for="id">特集名*</label>
		</th>
		<th width="230px" style="text-align: center;">
			<label for="id">コメント*</label>
		</th>
		<th width="170px" style="text-align: center;">
			<label for="id">掲載開始日*</label>
		</th>
		<th width="170px" style="text-align: center;">
			<label for="id">掲載終了日*</label>
		</th>
	</tr>
	<tr>
		<td style="text-align: center;">
			<textarea rows="3" cols="15" id="name" name="name" value="" maxlength="50" class="form-control"></textarea>
		</td>
		<td style="text-align: center;">
			<textarea rows="3" cols="20" id="introduction"  name="introduction" value="" maxlength="255"  class="form-control"></textarea>
		</td>
		<td style="text-align: center;">
			<input type="text" id="start_date" name="start_date" value="" size="30" class="form-control"/>
		</td>
		<td style="text-align: center;">
			<input type="text" id="end_date" name="end_date" value="" size="30" class="form-control"/>
		</td>
	</tr>
</table>

</br>

<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="300px" style="text-align: center;">
			<label for="id">特集タイプ*</label>
		</th>
		<th width="170px" style="text-align: center;">
			<label for="id">開催開始日*</label>
		</th>
		<th width="170px" style="text-align: center;">
			<label for="id">開催終了日*</label>
		</th>
	</tr>
	<tr>
		<td style="text-align: center;">
			<select id="special_type" class="form-control">
			  <option value="1">1:特集紹介画面→動的画面(おもフォトのみ)</option>
			  <option value="2">2:特集紹介画面→動的画面(フォト・おもフォト)</option>
			  <option value="3">3:静的画面</option>
			  <option value="4">4:動的画面(おもフォトのみ)</option>
			  <option value="5">5:動的画面(フォト・おもフォト)</option>
			  <option value="6">6:お笑い芸人投稿まとめ</option>
			</select>
		</td>
		<td style="text-align: center;">
			<input type="text" id="nowmark_start_date" name="nowmark_start_date" value="" size="30" class="form-control"/>
		</td>
		<td style="text-align: center;">
			<input type="text" id="nowmark_end_date" name="nowmark_end_date" value="" size="30" class="form-control"/>
		</td>
	</tr>
</table>

</br>
<div id="photoArea">
<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="600px" style="text-align: center;">
			<label for="id">フォト</label>
		</th>
	</tr>
	<tr>
		<td style="text-align: center;">
			<input type="text" id="photo_list" name="photo_list" value="" size="" class="form-control"/>
		</td>
	</tr>
</table>
</br>
</div>

<div id="omophotoArea">
<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="600px" style="text-align: center;">
			<label for="id">おもフォト</label>
		</th>
	</tr>
	<tr>
		<td style="text-align: center;">
			<input type="text" id="omophoto_list" name="omophoto_list" value="" size="" class="form-control"/>
		</td>
	</tr>
</table>
</br>
</div>

<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="200px" style="text-align: center;">
			<label for="id">特集バナー(SP)*</label>
		</th>
		<th width="200px" style="text-align: center;">
			<label for="id">特集バナー(PC)*</label>
		</th>
		<th width="200px" style="text-align: center;" id="special_page_title_sp">
			<label for="id">特集紹介画面(SP)</label>
		</th>
		<th width="200px" style="text-align: center;" id="special_page_title_pc">
			<label for="id">特集紹介画面(PC)</label>
		</th>
	</tr>
	<tr>
		<td style="text-align: center;">
			<img src="http://devwww.omotas.com/special/3/banner_img/banner_pc.jpg" width="200px">
	    	<input type="file" name="sp_banner_file" id="sp_banner_file" value="" onchange="preview_image(this,1);" imgUrl=""/>
		</td>
		<td style="text-align: center;">
			<input type="file" name="pc_banner_file" id="pc_banner_file" value="" onchange="preview_image(this,2);" imgUrl=""/>
		</td>
		<td style="text-align: center;" id="special_page_file_sp">
			<a href="http://devwww.omotas.com/special/1/content.html">content.html</a>
			<input type="file" name="sp_special_page_file" id="sp_special_page_file" value="" onchange="checkZipFile(this,1);" pageUrl=""/>
		</td>
		<td style="text-align: center;" id="special_page_file_pc">
			<input type="file" name="pc_special_page_file" id="pc_special_page_file" value="" onchange="checkZipFile(this,2);" pageUrl=""/>
		</td>
	</tr>
</table>

</br>
</form>
</div>


