<?php
/**
 * 
 * @author k-kawaguchi
 */
class Model_Special_Collection_Linked extends Model_Base
{
	
	protected static $_properties = array(
		'id',
		'special_collection_id',
		'linked_type',
		'linked_photo_id',
		'delete_at',
		'created_at',
		'updated_at'
	);
	protected static $_table_name = 'special_collection_linked';

	public static $response_root_element = 'special_collection_linked';
	
}	