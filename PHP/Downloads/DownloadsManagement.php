<?php
include 'classes/downloadsClass.php';
$action = $_GET['action'];

if ($action)
{
	$download = new Download($action);
	$download = $download->downloadInfo;
	$download = json_encode($download);
	echo $download;
}
else
{
	$host = $_GET['host'];
	$downloadClass = new Download();
	$host = $downloadClass->getHostsForCompatibility($host);
	$host = json_encode($host);
	echo $host;
}
