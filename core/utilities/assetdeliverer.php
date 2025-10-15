<?php

	function IsRewrite() {
		if(!empty($_SERVER['IIS_WasUrlRewritten']))
			return true;
		else if(array_key_exists('HTTP_MOD_REWRITE',$_SERVER))
			return true;
		else if( array_key_exists('REDIRECT_URL', $_SERVER))
			return true;
		else
			return false;
	}

	if(!isset($_GET['id']) && !isset($_GET['ID'])) {
		die(http_response_code(500));
	}

	if(isset($_GET['id'])) {
		$id = intval($_GET["id"]);
	} else if(isset($_GET['ID'])) {
		$id = intval($_GET["ID"]);
	}

	if(!IsRewrite()) {
		die(header("Location: /asset/?id=".$id));
	}

	function checkMimeType($contents) {
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		return $file_info->buffer($contents);
	}

	$settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../settings.env", true);

	$access = $settings['asset']['ACCESSKEY'];

	include $_SERVER["DOCUMENT_ROOT"] . "/core/asset.php";

	$asset = Asset::FromID($id);
	if($asset != null) {
		if(isset($_GET['version']) && intval($_GET['version']) != 0) {
			$version = intval($_GET['version']);
			$asset_version = AssetVersion::GetVersionOf($asset, $version);

			if($asset_version != null) {
				$filename = $_SERVER['DOCUMENT_ROOT']."/../assets/".$asset_version->md5sig;
			} else {
				die(http_response_code(404));
			}

		} else {
			$filename = $_SERVER['DOCUMENT_ROOT']."/../assets/".$asset->GetLatestVersionDetails()->md5sig;
		}
		
	} else {
		// TESTING REASONS ONLY, DO NOT USE ON PROD AT ALL.
		$filename = $_SERVER['DOCUMENT_ROOT']."/../assets/$id";
	}

	if($asset != null && $asset->status == AssetStatus::REJECTED && (!isset($_GET['access']) && $_GET['access'] == $access)) {
		die(http_response_code(403));
	} else {
		if(file_exists($filename)) {
			$handle = fopen($filename, "r"); 
			$contents = fread($handle, filesize($filename)); 
			fclose($handle);
			header("Content-Type: application/octet-stream");
			$contents = str_replace("www.roblox.com", "arl.lambda.cam",$contents);
			$contents = str_replace("api.roblox.com", "arl.lambda.cam",$contents);

			$contents = str_replace("arl.lambda.cam", $_SERVER['SERVER_NAME'], $contents);
			
			echo $contents;
		} else {
			die(http_response_code(404));
		}
	}
?>