<script type="text/javascript">
var ajax_create_special_collection_url = "<?php echo Config::get('base_url')."ajax/createSpecialCollection";?>";
var ajax_read_delete_url = "<?php echo Config::get('base_url')."ajax/delete";?>";
var ajax_release_special_collection_url = "<?php echo Config::get('base_url')."ajax/releaseSpecialCollection";?>";
</script>
<script type="text/javascript">

//特集新規作成
function read_ajax_create_special_collection(name,introduction,start_date,end_date) {
	$.ajax({
		url : ajax_create_special_collection_url,
		cache : false,
		type : 'POST', // HTTP method
		dataType : 'json', // response data type
		data : {
			name : name,
			introduction : introduction,
			start_date : start_date,
			end_date : end_date
		},
			    
		beforeSend : function(xhr)
		{
		    xhr.setRequestHeader("If-Modified-Since", "Thu, 01 Jun 1970 00:00:00 GMT");
		},

		success : function(key)
		{
			var jsonData = eval("("+key+")");
			if (jsonData.data && jsonData.result_code == "2000") {
		        $("#name").val("");
		        $("#introduction").val("");
		        $("#start_date").val("");
		        $("#end_date").val("");
		        //特集一覧取得
		        	
			}
			else{

			}
		},

		error : function(xhr, status, err) {
			alert(err);
			console.log('-----ajax response error');
		}
	});	
}

//本番反映
function read_ajax_release_special_collection() {
	$.ajax({
		url : ajax_release_special_collection_url,
		cache : false,
		type : 'POST', // HTTP method
		//dataType : 'json', // response data type
		//data : {
			//name : name,
			//introduction : introduction,
			//start_date : start_date,
			//end_date : end_date
		//},
			    
		beforeSend : function(xhr)
		{
		    xhr.setRequestHeader("If-Modified-Since", "Thu, 01 Jun 1970 00:00:00 GMT");
		},

		//success : function(key)
		//{
			//var jsonData = eval("("+key+")");
			/*
			if (jsonData.data && jsonData.result_code == "2000") {
		        $("#name").val("");
		        $("#introduction").val("");
		        $("#start_date").val("");
		        $("#end_date").val("");
		        //特集一覧取得
		        	
			}
			else{

			}*/
		//},

		error : function(xhr, status, err) {
			alert(err);
			console.log('-----ajax response error');
		}
	});	
}

function IsNumRange(num,min,max){
  var reNum=/^\d*$/;
  //数字の場合

  if(num==""){
	if(parseInt(num)!=0){
		return false;
	}    
  }
  if(reNum.test(num)){
    num = parseInt(num);
    if(num>=min&&num<=max){
        return true;
    }else{
        return false;
    }
  }else{
    return false;
  }
}


$(document).ready(function()
{
    jQuery(function () {
        jQuery('#start_date').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });

    });
    jQuery(function () {
        jQuery('#end_date').datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });

    });
    
    $("#sc_create").live("click",function(e){
		var check_result = 0;//0:OK,1:NG
		$("#errorMessage").empty();
		//エラーチェック
        var name = $("#name").val();
        var introduction = $("#introduction").val();
        var start_date = $("#start_date").val();
        var end_date = $("#end_date").val();
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
		//正常の場合、
		if(check_result == 0){
			//特集データを登録
			read_ajax_create_special_collection(name,introduction,start_date,end_date);
		}
    });
    
    $("#sc_release").live("click",function(e){
		var check_result = 0;//0:OK,1:NG
		$("#errorMessage").empty();
		//エラーチェック
        //var name = $("#name").val();
        //var introduction = $("#introduction").val();
        //var start_date = $("#start_date").val();
        //var end_date = $("#end_date").val();
        /*
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
		}*/
		//正常の場合、
		if(check_result == 0){
			//特集データを登録
			read_ajax_release_special_collection();
		}
    });

    $("#sc_preview").live("click",function(e){
		
		//設定した表示順の特集ＩＤリスト（カンマ区切りで複数指定）
		var displayorder_specialIdList="";
		//設定した表示順の数字（カンマ区切りで複数指定）
		var displayorder_List="";
		//設定した削除の特集ＩＤリスト（カンマ区切りで複数指定）
		var delete_specialIdList="";
		
		var check_result = 0;//0:OK,1:NG
		$("#errorMessage").empty();
		
		//該当ページで表示された件数 TODO
		var num = 2;
		//エラーチェック
		for(var i=1;i<=num;i++){
			var displayorderStr = "#display_order_" + i;
			var displayorder = $(displayorderStr).val();
			if(displayorder != ""){
				if( IsNumRange(displayorder,1,100000) ){//エラーチェックがＯＫの場合、
					if(displayorder_specialIdList == ""){
						displayorder_specialIdList = $(displayorderStr).attr('special_id');
						displayorder_List = displayorder;
					}else{
						displayorder_specialIdList = displayorder_specialIdList + "," + $(displayorderStr).attr('special_id');
						displayorder_List = displayorder_List + "," + displayorder;
					}
				}else{//エラーチェックがNGの場合、
					$("#errorMessage").append("特集一覧順序は空欄または1～100000数字で入力してください。</br>");
					return false;
				}
			}

			var deleteStr = "#delete_" + i;
			if($(deleteStr).is(":checked")){
				if(delete_specialIdList == ""){
					delete_specialIdList = $(deleteStr).attr('special_id');
				}else{
					delete_specialIdList = delete_specialIdList + "," + $(deleteStr).attr('special_id');
				}
			}
		}
		//delete_specialIdList = "31,32";
		delete_specialIdList = "";
		//特集削除操作
		if (delete_specialIdList != ""){
			read_ajax_delete(delete_specialIdList);
		}		
		//alert("displayorder_specialIdList:"+displayorder_specialIdList + " displayorder_List:" +displayorder_List+" delete_specialIdList:"+delete_specialIdList);

    });
});
</script>



<div>

<table border="0" cellspacing="0" cellpadding="0" >
	<tr>
		<td style="text-align: center;">
			<input type="button" id="sc_preview" name="sc_preview" value="プレビュー" size="100" class="btn btn-lg btn-primary btn-block" style="width:150px;"/>
		</td>
		<td style="text-align: center;width:30px;">
		</td>
		<td style="text-align: center;">
			<input type="button" id="sc_release" name="sc_release" value="本番環境反映" size="100" class="btn btn-lg btn-primary btn-block" style="width:150px;"/>
		</td>
		<td style="text-align: center;width:30px;">
		</td>
		<td style="text-align: left;">
			<div id="errorMessage" style="color:red;"></div>
		</td>
	</tr>
</table>

</br>

<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="85px" style="text-align: center;">
			<label for="id">特集ID</label>
		</th>
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
		<th width="90px" style="text-align: center;">
			<label for="id">新規作成</label>
		</th>
	</tr>
	<tr>
		<td>
		</td>
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
		<td style="text-align: center;">
			<input type="button" id="sc_create" name="sc_create" value="新規" size="50" class="btn btn-lg btn-primary btn-block" style="width:80px;display: table-cell;"/>
		</td>
	</tr>
</table>

</br>

<table bordercolor="#C0C0CC" border="3" cellspacing="0" cellpadding="3" >
	<tr bgcolor="#DCDCDC">
		<th width="85px" style="text-align: center;">
			<label for="id">特集ID</label>
		</th>
		<th width="180px" style="text-align: center;">
			<label for="id">特集名</label>
		</th>
		<th width="230px" style="text-align: center;">
			<label for="id">コメント</label>
		</th>
		<th width="170px" style="text-align: center;">
			<label for="id">掲載開始日</label>
		</th>
		<th width="170px" style="text-align: center;">
			<label for="id">掲載終了日</label>
		</th>
		<th width="90px" style="text-align: center;">
			<label for="id">詳細</label>
		</th>
		<th width="120px" style="text-align: center;">
			<label for="id">特集一覧順序</label>
		</th>
		<th width="90px" style="text-align: center;">
			<label for="id">削除</label>
		</th>
	</tr>
	<tr>
		<td>1234</td>
		<td style="text-align: left;">特集名特集名特集名特集名特集名特集名特集名特集名</td>
		<td style="text-align: left;">コメントコメントコメントコメントコメントコメントコメントコメント</td>
		<td style="text-align: left;">2015-03-05 14:25:13</td>
		<td style="text-align: left;">2015-03-05 14:25:13</td>
		<td style="text-align: center;">
			<a href="detail?id=100">
			<input type="button" id="sc_detail" name="sc_detail" value="詳細編集" size="50" class="btn btn-lg btn-primary btn-block" style="width:80px;"/>
			</a>
		</td>
		<td style="text-align: center;"><input type="text" id="display_order_1" special_id="100" value="" size="50" style="width:80px;display: table-cell;" class="form-control"/></td>
		<td style="text-align: center;"><input type="checkbox" id="delete_1" special_id="100"  class="form-control"></td>
	</tr>
	<tr bgcolor="#808080">
		<td>1234</td>
		<td style="text-align: left;">特集名特集名特集名特集名特集名特集名特集名特集名</td>
		<td style="text-align: left;">コメントコメントコメントコメントコメントコメントコメントコメント</td>
		<td style="text-align: left;">2015-03-05 14:25:13</td>
		<td style="text-align: left;">2015-03-05 14:25:13</td>
		<td style="text-align: center;">
			<a href="detail?id=101&check=XXXXXXX">
			<input type="button" id="sc_detail" name="sc_detail" value="詳細編集" size="50" class="btn btn-lg btn-primary btn-block" style="width:80px;"/>
			</a>
		</td>
		<td style="text-align: center;"><input type="text" id="display_order_2" special_id="101"  value="" size="50" style="width:80px;display: table-cell;" class="form-control"/></td>
		<td style="text-align: center;"><input type="checkbox" id="delete_2" special_id="101" class="form-control"></td>
	</tr>
</table>

<input type="hidden" id="pagecount" name="pagecount" value="" size="50" />
<input type="hidden" id="rowcount" name="rowcount" value="" size="50" />




</div>

<span id="datacount"></span>
<br />
<br />

<div id="specialcollectionlist">
</div>
<br/>
<br/>







