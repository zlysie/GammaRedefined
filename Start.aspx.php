<?php
	session_start();
	
	require_once $_SERVER["DOCUMENT_ROOT"]."/core/utilities/userutils.php";
	
	UserUtils::LockOutUserIfNotLoggedIn();

	die(header("Location: /Games.aspx"));
?>