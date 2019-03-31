<?php

// Set params to database connection
$servername = "localhost";
$username = 'your_username';
$password = 'your_password';
$dbname = 'your_database_name';

// categories url
$categories_file = 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt?fbclid=IwAR0_jQEUep0R2ElMf1lEYCDAW56b5vbl8DKTWE8N3xAhoVRpSoATg3yemFM';


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

function cllenCat($param){
	if(empty($param)){
		return '';
	}
	if (strpos($param, ' - ') === false) {
		return $param;
	}
	$exp = explode(" - ", $param);
	return $exp[1];
}

$file = file_get_contents($categories_file);
$file = explode("\n", $file);

// clear tables
$conn->query('TRUNCATE TABLE oc_category');
$conn->query('TRUNCATE TABLE oc_category_description');
$conn->query('TRUNCATE TABLE oc_category_path');
$conn->query('TRUNCATE TABLE oc_category_to_layout');
$conn->query('TRUNCATE TABLE oc_category_to_store');

foreach ($file as $index => $line) {
	if($index == 0){
		continue;
	}
	
	$fields = explode(" > ", $line);
	$keys = array_keys($fields);
	
	$lastKey = $keys[count($keys)-1];
	$prevKey = $keys[count($keys)-2];
	$lastCategory = empty($fields[$lastKey]) ? '' : cllenCat($fields[$lastKey]);
	$prevCategory = empty($fields[$prevKey]) ? '' : cllenCat($fields[$prevKey]);
	
	
	$getParent = $conn->query('SELECT `category_id` FROM `oc_category_description` WHERE `name` = "'.$prevCategory.'"  LIMIT 1');
	$getParentId = $getParent->fetch_assoc();
	$getParentId = empty($getParentId["category_id"]) ? 0 : $getParentId["category_id"];
	
	$conn->query("INSERT INTO `oc_category` (`image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `date_added`, `date_modified`) VALUES ('', '".$getParentId."', '0', '1', '".$index."', '1', NOW(), NOW() )");
	$last_id = $conn->insert_id;
	
	$conn->query("INSERT INTO `oc_category_description` (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`) VALUES ('".$last_id."', '1', '".$lastCategory."', '".$lastCategory."', '".$lastCategory."', '".$lastCategory."', '".$lastCategory."')");
	$conn->query("INSERT INTO `oc_category_description` (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`) VALUES ('".$last_id."', '2', '".$lastCategory."', '".$lastCategory."', '".$lastCategory."', '".$lastCategory."', '".$lastCategory."')");
	
	$conn->query('INSERT INTO `oc_category_to_layout` (`category_id`, `store_id`, `layout_id` ) VALUES ( "'.$last_id.'", "0", "0" )');
	$conn->query('INSERT INTO `oc_category_to_store` (`category_id`, `store_id` ) VALUES ( "'.$last_id.'", "0" )');

	if(!empty($fields)){
		foreach($fields as $level => $oneField){
			
			$oneField = cllenCat($oneField);
			$getCurrent = $conn->query('SELECT `category_id` FROM `oc_category_description` WHERE `name` = "'.$oneField.'"  LIMIT 1');
			$getCurrentId = $getCurrent->fetch_assoc();
			$getCurrentId = empty($getCurrentId["category_id"]) ? 0 : $getCurrentId["category_id"];
			
			$conn->query('INSERT INTO `oc_category_path` (`category_id`, `path_id`, `level` ) VALUES ( "'.$last_id.'", "'.$getCurrentId.'", "'.$level.'" )');
			
		}
	}
	
}

$conn->close();
die('finish');

?>
