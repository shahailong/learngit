<?php
/**
 * 特集
 *
 * @author a-kido
 */
class Model_Specialworkexclusion extends Model_Base {

	protected static $_properties = array(
			'working_flg',
			'work_start_date'
	);
	protected static $_table_name = 'special_work_exclusion';



	/**
	 * 特集テーブル操作状態を取得
	 *
	 */
	public static function get_working_flg(){
		
		Log::error('get_working_flg start');
		$working_flg = 0;//0:未操作　1:操作中
		
		$query = DB::select('sw.working_flg');
		$query->from(array(static::table(),'sw'));
		$query->limit(1);
		$result = $query->execute();
		if(count($result) > 0){
			$working_flg = $result[0]['working_flg'];
		}
		Log::error('get_working_flg working_flg:'.$working_flg);

		return  $working_flg;
	}

	/**
	 * 特集操作排他テーブル更新
	 *
	 */
	public static function update_special_work_exclusion($working_flg){

		Log::error('update_special_work_exclusion start');
		
		return DB::update(static::table())
    	->set(array(
    			'working_flg'=>$working_flg,
    			'work_start_date'=>DB::expr('NOW()')
    	))
    	->execute();
	}

}