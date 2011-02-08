<?php
$settings = array(
	'db-access' => array(
		'default'	=> array('dbhost' => 'localhost', 'dbaccount' => 'mets', 'dbcode' => 'Cfqp2JnV86QKEmNa'),
		'moodle'		=> array('dbhost' => 'localhost', 'dbaccount' => 'mdlr', 'dbcode' => '0pkcw4yHsQxtPwDn'),
		//'cip'		=> array('dbhost' => 'localhost', 'dbaccount' => 'cip', 'dbcode' => 'TF3CFVcThcZN6Vq2'),
		//'hsdl'		=> array('dbhost' => 'hsdl01.hsdl.org', 'dbaccount' => 'hsdl', 'dbcode' => 'dRZvzfe5fqWRB7nn'),
		//'spt'		=> array('dbhost' => 'loki.ern.nps.edu', 'dbaccount' => 'i3pImport', 'dbcode' => 'n8w8z37SZ8K6eqpS'),
		//'i3p'		=> array('dbhost' => 'vili.ern.nps.edu', 'dbaccount' => 'i3plist', 'dbcode' => 'rRxsjpT3fFzhRqK2'),
		'spt'		=> array('dbhost' => 'loki.ern.nps.edu:3306', 'dbaccount' => 'alex', 'dbcode' => 'stVjApzDqj5GctS8'),
		'sptx'		=> array('dbhost' => 'loki.ern.nps.edu:3306', 'dbaccount' => 'sptRunner', 'dbcode' => 'V5rrQ884wAhSJjFj'),
		'gen'	=> array('dbhost' => 'localhost', 'dbaccount' => 'mets', 'dbcode' => 'Cfqp2JnV86QKEmNa'),
	),
	
	'db-addslashes' => true,
	'log-updates' => true,
	'dont-log-tables' => array('people' => array('history', 'log', 'permission')),
	'log-db' => 'people',
	'log-table' => 'history',
	'dberror'	=> 'dberror',

// SOLR settings
	'search-engine' => 'solr',
	'search-host' => 'fulla.ern.nps.edu:8080',
	'db-use-PDO' => true,
	'show-query' => true,
	'show-result' => false,
	
	'solr-collections' => array(
		'documents'		=> array('id' => '0', 'sort' => 'score', 'datesort' => 'PublishDate'),
		'i3p'			=> array('id' => '2', 'sort' => 'score', 'datesort' => 'PublishDate'),
		'earlybird'		=> array('id' => '8', 'sort' => 'score', 'datesort' => 'FileDate'), 
		'newsletters'	=> array('id' => '7', 'sort' => 'score', 'datesort' => 'PublishDate'),
		'restricted'	=> array('id' => '1', 'sort' => 'score', 'datesort' => 'PublishDate'),
		'all_news'		=> array('id' => '(7 OR 8)', 'sort' => 'score', 'datesort' => 'PublishDate'), 
		'any'			=> array('id' => '(0 OR 2 OR 7 OR 8)', 'sort' => 'score', 'datesort' => 'PublishDate'), 
	),
	'document_directory' => '/homesec/docs/',
	'earlybird_directory' => '/homesec/ebird/',
	'newsletter_directory' => '/homesec/newsletters/',
	'restricted_directory' => '/homesec/infoshare/',
	
	'doc-images' => array(
		'homesec/docs/crs/' => 'images/docpics/gen_crs.jpg',
		'homesec/docs/gao/' => 'images/docpics/gen_gao.jpg',
		'homesec/docs/theses/' => 'images/docpics/gen_nps.jpg',
		'collection/notavailable' => 'images/docpics/notavailable.jpg',
		'www.worldcat.org/search' => 'images/docpics/worldcat.jpg', // test code for worldcat URLs
	),
// end SOLR settings

);
?>
