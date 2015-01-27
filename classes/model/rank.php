<?php
/**
 * ランクマスター
 *
 * @author a-kido
 */
class Model_Rank extends Model_Base{

	protected static $_primary_key = array('id');

	protected static $_properties = array(
		'id',
		'name',
		'minimum_points',
		'delete_flg',
		'delete_at',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'ranks';

	/**
	 * ランク名称を取得
	 *
	 * @param int $id
	 * @return String
	 */
	public static function get_name($id){

		$query = DB::select('r.name');
		$query->as_object('Model_Rank');
		$query->from(array(static::table(),'r'));
		$query->where(array('r.delete_flg'=>static::DELETE_FLG_FALSE));
		$query->where('r.id','=',$id);

		$cache_key = "db." .self::$_table_name .".id{$id}";
		$results = $query->cached(Config::get('query_expires_cache_time'), $cache_key, false)->execute(); //キャッシュ

		if(count($results) == 0){
			return "";
		}
		return $results[0]['name'];
	}

	/**
	 * 次ランクに昇格するために必要なポイント
	 *
	 * @param int $points 現保有ポイント数
	 * @param int $rank_id 現ランクID
	 *
	 * @return int
	 */
	public static function get_rankup_required_points($points = 0, $rank_id = null){

		if(empty($rank_id)){
			return 0;
		}

		// 次ランク検索
		$sql = "SELECT id,minimum_points FROM ranks WHERE minimum_points > (SELECT minimum_points FROM ranks WHERE id = :rank_id) ORDER BY minimum_points LIMIT 1";
		$result = DB::query($sql)
					->param('rank_id', $rank_id)
					->execute()
					->current();

		if(isset($result['minimum_points'])){
			if($result['minimum_points'] > $points){
				// 正常パターン
				return $result['minimum_points'] - $points ;
			}else{
				// 累積獲得ポイントと現ランクに不整合がある
			}
		}else {
			// 現ランクより上位のランクが存在しない
		}
		return 0;

	}

	 /**
	 * 保有ポイントでの該当ランクを取得
	 *
	 * @param int $points 保有ポイント
	 * @return array
	 */
	public static function get_point_rank($points = 0){

		$models = static::query()
							->where('minimum_points', '<=', $points)
							->order_by('minimum_points', 'desc')
							->get();

		$model = array_shift($models);
		if(!$model){
			return 0;
		}

		return $model;
	}


	/**
	 * ランクを比較して、昇格となるのか判断します。
	 *
	 * @param int $before_rank_id
	 * @param int $after_rank_id
	 *
	 * @return boolean
	 */
	public static function is_upgrade($before_rank_id = null, $after_rank_id = null){

		if(!$before_rank_id || !$after_rank_id){
			return false;
		}

		//異なる2つのランクIDのうち、必要評価ポイント数が大きいランクを取得
		$models = static::query()
							->where('id', 'in', array($before_rank_id,$after_rank_id))
							->order_by('minimum_points', 'desc')
							->limit(1)
							->offset(0)
							->get();

		if(isset($models[$after_rank_id])){
			return true;
		}

		return false;
	}

}