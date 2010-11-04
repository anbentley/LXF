<?php
/**
 * DB provides a common simplified interface to multiple databases.
 * 
 * @author	Alex Bentley
 * @history	5.1     update to update funtion to correct a long standing bug that could stop updates from occuring
 *          5.0     significant protective coding added
 *          4.7		fixes for internal functions to imporve overall stability
 *			4.6		fix to repair a condition parameter name to fit accepted names
 *			4.5		fix to update statement to separate selector placeholders from data placeholder
 *			4.4		removed dependence on ABOUT class
 *			4.3		fix call to rowCount
 *			4.2		improved parameter handling
 *			3.0		PDO is the default processing flow
 *			2.5		add PDO support
 *			1.0		initial release
 */
class DB {

/**
 * Returns an array of values for a connection string after applying defaults. 
 *
 * @param  $db		the name of the database specification to evaluate.
 * @return			an array of values as a keyed array including dbhost, dbtype, dbuser, and dbcode.
 * @see				connect
 */
function get_dbaccess(&$db) {
	@list($host, $db) = explode(':', $db);
	if ($db == '') { // no host specified, update $db to reflect the defaults
		$db = $host;
		$host = 'default';
	}
	
	$access = get('db-access');
	
	if (!is_array($access) || !array_key_exists($host, $access)) return false;
	
	$defaults = array('dbhost' => 'localhost', 'dbtype' => 'mysql');
	$dba = smart_merge($defaults, $access[$host]);
	
	return $dba;
}

/**
 * Executes a query. The PDO code is done in an eval to allow for PHP4 compatibility
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $query	the SQL text of the query.
 * @param  $params	a keyed array to use for parameter replacement. PDO style query parameters are supported.
 *					they are of the form :name
 * @return			dependent on type of query as determined by first word of the query
 *					for INSERT the last key inserted is returned
 *					for DELETE or UPDATE the number of records affected is returned
 *					for all other queries the data retrieved is returned
 * @see				connect
 */
function query ($db, $query, $params=array()) {
	if (!is_array($params)) $params = array(); // old query parameter, ignore it
	
	list($type) = explode(' ', $query, 2);
	$type = strtolower($type);
	
	if (!get('db-use-PDO')) return DB::SQLquery($db, $query, $params, $type);

	$dba = self::get_dbaccess($db);
	
	$pdoconnect = $dba['dbtype'].':host='.$dba['dbhost'].';dbname='.$db;
	$pdoaccount = $dba['dbaccount'];
	$pdocode = $dba['dbcode'];
    
	try {
        $rows = false;
        
		$dbh = new PDO($pdoconnect, $pdoaccount, $pdocode, 
			array(PDO::ERRMODE_EXCEPTION => true, PDO::ATTR_PERSISTENT => false, PDO::ATTR_EMULATE_PREPARES=>true));
		if ($dbh === false) {
			return false;
		}
		
		$stmt = $dbh->prepare($query);
		foreach ($params as $key => $value) {
			if (is_numeric($key)) $key++;
			if (!$stmt->bindValue ($key, $value)) { /* bind all passed params */
				DEBUG::display('BIND of '.$key.' as '.$value.' FAILED');
			}
		}
		
		if ($stmt->execute()) { /* sucessful execution */
			switch ($type) {
				case 'insert':
					$rows = $dbh->lastInsertId();
					break;
					
				case 'delete':
				case 'update':
					$rows = $stmt->rowCount();
					break;
					
				default:
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			
			$stmt->closeCursor();
			$dbh = null;
			
			if ($rows == array()) $rows = false;
			
			return $rows;
		} else {
			$err = $stmt->errorInfo();
			$dbh = null;
			
			$rows = false;
		}
	} catch (Exception $e) {
		DEBUG::show($e->getMessage());
		$dbh = null;
		$rows = false;
	}

	return $rows;
}


/**
 * Executes an INSERT query. This method builds a valid INSERT query which it then passed to query for execution.
 * It uses the template: INSERT INTO db.table ( field, ... ) VALUES ( value, ... )
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $table	the name of the SQL table to insert the data into.
 * @param  $fields	a keyed array of field names and values for insertion. alternatively it is a simple array of field names.
 * @param  $values	a simple array of values for insertion if the alternate form is used.
 * @return			the last key inserted is returned or false if the query fails.
 * @see				query
 */
function insert($db, $table, $fields, $values=null, $expected=1) {
	if (is_array($values)) { // combine arrays and reissue the request for old style requests
        if (is_array($fields) && is_array($values) && (count($fields) != count($values))) return false; // unbalanced pairs

		$fieldlist = array();
        if (is_array($values)) {
            while ($fields != array()) {
                $field = array_pop($fields);
                $fieldlist[$field] = array_pop($values);
            }
        }
		return self::insert($db, $table, $fieldlist);
	}

	$dba = self::get_dbaccess($db);

	$fieldlist = '';
	$valuelist = '';
	$corrected = array();
	if (is_array($fields)) {
        foreach ($fields as $field => $value) {
            $name = str_replace(':', '', $field);
            append($fieldlist, $name, ' , ');
            append($valuelist, ':'.$name, ' , ');
            $corrected[':'.$name] = $value; // deal with old code
        }
	}
    	
	$query = 'INSERT INTO '.$db.'.'.$table.' ( '.$fieldlist.' ) VALUES ( '.$valuelist.' )';	
	return self::query($db, $query, $corrected);
}

/**
 * Executes an INSERT query. This is included for compatibility.
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $table	the name of the SQL table to insert the data into.
 * @param  $fields	a keyed array of field names and values for insertion. alternatively it is a simple array of field names.
 * @param  $values	a simple array of values for insertion if the alternate form is used.
 * @return			the last key inserted is returned or false if the query fails.
 * @see				insert
 * @deprecated		insert
 */
function ins($db, $table, $fields, $values=null) {
	DEBUG::deprecated('insert');
	return self::insert($db, $table, $fields, $values);
}

/**
 * Executes an UPDATE query. This method builds a valid UPDATE query which it then passed to query for execution.
 * It uses the template: UPDATE db.table SET field = value , ... WHERE keyfield = value ...
 * The recommended calling sequence is not the alternate form.
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $table	the name of the SQL table to update.
 * @param  $fields	a keyed array of field names and values for update. 
 *					alternatively it is a simple array of field names.
 * @param  $keys	a keyed array of field names and values to be used to select the records to update. 
 *					alternatively it is a simple array of values for update if the alternate form is used.
 * @param  $v1		alternative it is a simple array of values for key fields if the alternate form is used.
 * @param  $v2		alternative it is a simple array of values for key values if the alternate form is used.
 * @param  $expected	this specifies the number of records that are expected to be affected.
 *					if this number does not match the number of actual records, the query will fail.
 *					if $expected is set to -1 then this check is NOT performed.
 * @return			the number of rows affected is returned or false if the query fails.
 * @see				query
 */
function update($db, $table, $fields, $keys, $v1=null, $v2=null, $expected=1) {
    // old style update call, combine arrays and reissue the request, was (fields, fieldvalues, keys, keyvalues)
	if (is_array($v1)) { 
        if (is_array($fields) && is_array($keys) && (count($fields) != count($keys))) return false; // unbalanced pairs
		if (is_array($v1)     && is_array($v2)   && (count($v1)     != count($v2)))   return false; // unbalanced pairs
		
        $f = array();
        $k = array();
		if (is_array($fields) && is_array($keys)) $f = array_combine($keys, $fields);
		
		if (is_array($v2)) $k = array_combine($v1, $v2);

		return self::update($db, $table, $f, $k, null, null, $expected);
	}

	$dba = self::get_dbaccess($db);
	$fieldlist = '';
	if (is_array($fields)) {
        foreach ($fields as $field => $value) {
            $name = str_replace(':', '', $field);
            append($fieldlist, $name.' = :'.$name, ' , ');
            $corrected[':'.$name] = $value; // deal with old code
        }
	}
	$conditions = '';
    
    // when checking the count we need to only pass the keys
    $countkeys = array();
	if (is_array($keys)) {
        foreach ($keys as $keyfield => $value) {
            $name = str_replace(':', '', $keyfield);
            $paramname = ':condition_'.str_replace('.', '', $name);
            append($conditions, $name.' = '.$paramname, ' AND ');
            $corrected[$paramname] = $value; // deal with old code
            $countkeys[$paramname] = $value;
        }
	}

	$params = $corrected;
		
	// insure we're only updating the number of rows we expect
	if ($expected > 0) {
		$query = 'SELECT count(*) as count FROM '.$db.'.'.$table.' WHERE '.$conditions;
		$count = self::query($db, $query, $countkeys);
		$count = $count[0]['count'];
	
		if ($count != $expected) return false;
	}
	
	$query = 'UPDATE '.$db.'.'.$table.' SET '.$fieldlist.' WHERE '.$conditions;
    
	if ($dba['dbhost'] == 'default') {
		if (get('log-updates')) {
			$dont_log = get('dont-log-tables');
			if (!array_key_exists($db, $dont_log) || !in_array($table, $dont_log[$db])) {
				$query2 = 'SELECT * FROM '.$db.'.'.$table.' WHERE '.$conditions;
				$res = self::query($db, $query2, $keyvalues, 'update');
				$entry_id = $res[0]['id'];
				
				$data_field = '';
				foreach ($res[0] as $key => $value) {
					$data_field .= $key.'||'.$value.'||';
				}
				
				self::insert(get('log-db'), get('log-table'), 
					array(':db_table' => $table, ':entry_id' => $entry_id, ':type' => 'update', ':data_field' => $data_field));
					
			}
		}
	}
	
	$result = self::query($db, $query, $params);
	if ($result == false) {
		$errors = mysql_error();
		if (empty($errors)) {
			return true;
		}
	}
	
	return $result;
}

/**
 * Executes an UPDATE query. The method is included for compatibility.
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $table	the name of the SQL table to update.
 * @param  $fields	a keyed array of field names and values for update. 
 *					alternatively it is a simple array of field names.
 * @param  $keys	a keyed array of field names and values to be used to select the records to update. 
 *					alternatively it is a simple array of values for update if the alternate form is used.
 * @param  $v1		alternative it is a simple array of values for key fields if the alternate form is used.
 * @param  $v2		alternative it is a simple array of values for key values if the alternate form is used.
 * @return			the number of rows affected is returned or false if the query fails.
 * @see				update
 * @deprecated		update
 */
function upd($db, $table, $fields, $keys, $v1=null, $v2=null, $expected=1) {
	DEBUG::deprecated('update');
	return self::update($db, $table, $fields, $keys, $v1, $v2, $expected);
}
/**
 * Executes a DELETE query. This method builds a valid DELETE query which it then passed to query for execution.
 * It uses the template: DELETE FROM db.table WHERE field = value ...
 * The recommended calling sequence is not the alternate form.
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $table	the name of the SQL table to update.
 * @param  $fields	a keyed array of field names and values for update. 
 *					alternatively it is a simple array of field names.
 * @param  $values	alternatively it is a simple array of values for delete if the alternate form is used.
 * @param  $expected	this specifies the number of records that are expected to be affected.
 *					if this number does not match the number of actual records, the query will fail.
 *					if $expected is set to -1 then this check is NOT performed.
 * @return			the number of rows affected is returned or false if the query fails.
 * @see				query
 */
function delete($db, $table, $fields, $values=null, $expected=1) {
	// old style call: combine arrays and reissue the request
    if (is_array($values) && is_array($fields)) {
		if (count($values) != count($fields)) return false; // unbalanced pairs

		while ($fields != array()) {
			$field = array_pop($fields);
			$f[':'.$field] = array_pop($values);
		}
		return self::delete($db, $table, $f, null, $expected);
	}

	$dba = self::get_dbaccess($db);
	
	$conditions = '';
	if (is_array($fields)) {
        foreach ($fields as $field => $value) {
            $name = str_replace(':', '', $field);
            append($conditions, $name.' = :'.$name, ' AND ');
            $corrected[':'.$name] = $value; // deal with old code
        }
	}
    
	// insure we're only deleting the number of rows we expect
	if ($expected > 0) {
		$query = 'SELECT count(*) as count FROM '.$db.'.'.$table.' WHERE '.$conditions;
		$count = self::query($db, $query, $corrected, null, $expected);
		$count = $count[0]['count'];
	
		if ($count != $expected) return false;
	}
	
	$query = 'DELETE FROM '.$db.'.'.$table.' WHERE '.$conditions;
	
	$result = self::query($db, $query, $corrected);
}

/**
 * Executes a DELETE query. This method is included for compatibility.
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $table	the name of the SQL table to update.
 * @param  $fields	a keyed array of field names and values for update. 
 *					alternatively it is a simple array of field names.
 * @param  $values	alternatively it is a simple array of values for delete if the alternate form is used.
 * @return			the number of rows affected is returned or false if the query fails.
 * @see				delete
 * @deprecated		delete
 */
function del($db, $table, $fields, $values=null, $expected=1) {
	DEBUG::deprecated('delete');
	return self::delete($db, $table, $fields, $values, $expected);
}

function getFields ($db, $table) {
	$dba = self::get_dbaccess($db);
	return self::query($db, 'SHOW COLUMNS FROM '.$db.'.'.$table);
}

function showFields ($db, $table) {	
	$results = self::getFields ($db, $table);
	
	$fieldnames = array('Field', 'Type', 'Null', 'Key', 'Default'/*, 'Extra'*/);
	
	echo '<table class="basictable">';
	
	echo tr('');
	foreach ($fieldnames as $name) {
		echo td('', $name);
	}
	echo tr('/');
	
	if (is_array($results)) {
        foreach ($results as $row) {
			echo tr('');
            foreach ($fieldnames as $name) {
                echo '<td>'.$row[$name].'</td>';
            }
			echo tr('/');
        }
    }
	
	echo '</table>';
}
		
/**
 * Executes a query.
 *
 * @param  $db		the name of the database to perform the query on.
 * @param  $query	the query to execute.
 * @param  $params	a keyed array of field names and values for update. 
 * @param  $type	the type of query being executed { insert | delete | select }.
 * @return			the results of the query.
 * @deprecated		query
 */
function SQLquery($db, $query, $params=array(), $type) {	
	$dba = self::get_dbaccess($db);
	$link = @mysql_connect ($dba['host'], $dba['dbaccount'], $dba['dbcode']);

	if (!$link) {
		if (page() != get('dberror')) {
			header ('Location: http://'.page('hostpage').'?'.get('dberror').'&uri='.urlencode(page('full')));
			exit();
		}
	}
	
	if (!@mysql_select_db($db)) return false;

	$rows = array();
	
	if (is_array($params) && count($params)) {
		foreach ($params as $field => $value) {
			if (str_contains($value, '\'"\\'."\n\r")) $value = '\''.mysql_real_escape_string($value).'\''; // put strings in quotes after escaping the string
			$source = array($field.' ', $field."\n", $field.','); // include all possible trailing characters to avoid partial matches
			$replace = array($value.' ', $value."\n", $value.',');
			$query = str_replace($source, $replace, $query.' ');
		}
	}
		
	$results = mysql_query($query);
	
	if (($results !== false) && ($results !== true)) {
		for ($i = 0; $i < mysql_num_rows($results); $i++) {
			while ($row = mysql_fetch_assoc ($results)) $rows[] = $row;
		}
	}
	
	switch ($type) {
		case 'insert':
			$rows = mysql_insert_id();
			break;
		
		case 'delete':
		case 'update':
			$rows = mysql_affected_rows();
			break;
			
		default:
	}
	
	@mysql_free_result($results);
	
	if ($rows == array()) return false;
	
	return $rows;
}

/**
 * Gets the timestamp from the database.
 *
 * @return	the timestamp.
 */
function getDateTime() {
	$query = 'SELECT CURRENT_TIMESTAMP() AS ts FROM DUAL WHERE 1 LIMIT 1';
	
	$results = self::query('', $query);
	return $results[0]['ts'];
}

/**
 * Formats a database query result as a table
 *
 * @param	$array	the results array.
 * @return	the HTML formatted table.
 * @see		smart_merge
 */
function queryToTable($array, $options=array()) {
	if (!is_array($array)) return '';
    
    $defaults = array('class' => 'basictable');
	$options = UTIL::merge($defaults, $options);
	
	$result = tr('class:titles');
	$titles = array_keys($array[0]);
	foreach ($titles as $item) $result .= td('', $item);
	$result .=  tr('/');
	
	foreach ($array as $row) {
		if (is_array($row)) {
            $result .= tr('');
			foreach ($titles as $item) $result .= td('', $item);
			$result .=  tr('/');
        }
	}
	
	return table('class:'.$options['class'], $result);
}

}


?>
