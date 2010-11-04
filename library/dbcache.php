<?php

/**
 * CACHE provides for an in memory cache of DB queries.
 * 
 * @author	Alex Bentley
 * @history	1.2	minor code cleanup
 *			1.1	now uses array_scan
 *			1.0	initial release
 */
class CACHE extends DB {

/**
 * Performs or returns cached results for the query.
 *
 * @param	$db		the name of the database to perform the query on.
 * @param	$table	the name of the table to perform the query on.
 * @param	$where	the where clause of the query.
 * @return	result of the query (possibly cached).
 * @see		DB::query
 * @see		array_extract
 */
function get ($db, $table, $where, $reload=false) {
	static $cache = null;
	
	if (($cache == null) || !array_extract($cache, array($db, $table, $where), false) || $reload) {	
		$query = "SELECT * FROM $table WHERE $where";		
		$cache[$db][$table][$where] = DB::query($db, $query);
	//} else {
		//DEBUG::show('cache hit');
	}
	
	return $cache[$db][$table][$where];
}


}
?>