<?php
	if(!isset($_SESSION)){
	    session_start();
	}
	$_REQUEST['lang'] = $_REQUEST['getLang'];
	$_SESSION['lang'] = $_REQUEST['lang'];	
?>