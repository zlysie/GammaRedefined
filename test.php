<?php
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/assetuploader.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/userutils.php";

	$user = UserUtils::RetrieveUser();

	AssetUploader::UploadShirt("Shirt", "", [ "error" => 0, "tmp_name" => $_SERVER['DOCUMENT_ROOT']."/7175610311.png"])
?>