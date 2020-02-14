<?php
require_once(__DIR__ . "/../inc/config.php");
header('Content-Type: application/json');

$fullbuilds = $pdo->query("SELECT build, version FROM wow_builds")->fetchAll(PDO::FETCH_KEY_PAIR);
$buildsToID = $pdo->query("SELECT build, id FROM wow_builds")->fetchAll(PDO::FETCH_KEY_PAIR);
$tablesToID = $pdo->query("SELECT name, id FROM wow_dbc_tables")->fetchAll(PDO::FETCH_KEY_PAIR);

$versionTableCache = [];
foreach($pdo->query("SELECT versionid, tableid FROM wow_dbc_table_versions") as $tv){
	$versionTableCache[$tv['versionid']][] = $tv['tableid'];
}

function isTableAvailableForBuild($table, $build){
	global $buildsToID;
	global $tablesToID;
	global $versionTableCache;

	return in_array($tablesToID[strtolower($table)], $versionTableCache[$buildsToID[$build]]);

}
$start = (int)filter_input( INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT );
$length = (int)filter_input( INPUT_GET, 'length', FILTER_SANITIZE_NUMBER_INT );

if(empty($_GET['draw']))
	$_GET['draw'] = 0;

$returndata['draw'] = (int)$_GET['draw'];
$returndata['recordsTotal'] = $pdo->query("SELECT count(*) FROM wow_hotfixes")->fetchColumn();
$returndata['recordsFiltered'] = $returndata['recordsTotal'];

if(empty($_GET['search']['value'])){
	$dataq = $pdo->prepare("SELECT * FROM wow_hotfixes ORDER BY firstdetected DESC, pushID DESC LIMIT " . $start .", " . $length);
}else{
	$dataq = $pdo->prepare("SELECT * FROM wow_hotfixes WHERE pushID LIKE :pushID OR recordID LIKE :recordID OR tableName LIKE :tableName or build LIKE :build or firstdetected LIKE :firstDetected ORDER BY firstdetected DESC, pushID DESC LIMIT " . $start .", " . $length);
	$dataq->bindValue(":pushID", "%".$_GET['search']['value']."%");
	$dataq->bindValue(":recordID", "%".$_GET['search']['value']."%");
	$dataq->bindValue(":tableName", "%".$_GET['search']['value']."%");
	$dataq->bindValue(":build", "%".$_GET['search']['value']."%");
	$dataq->bindValue(":firstDetected", "%".$_GET['search']['value']."%");
}
$dataq->execute();

$returndata['data'] = array();
while($row = $dataq->fetch()){
	$returndata['data'][] = array($row['pushID'], $row['tableName'], $row['recordID'], $fullbuilds[$row['build']], $row['isValid'], $row['firstdetected'], isTableAvailableForBuild($row['tableName'], $row['build']));
}

echo json_encode($returndata);
?>