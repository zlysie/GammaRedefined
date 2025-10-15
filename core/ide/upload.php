<?php

	require_once $_SERVER['DOCUMENT_ROOT']."/core/asset.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/userutils.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/assetuploader.php";

	$user = UserUtils::RetrieveUser();

	function FunnyStrToBool(string $value): bool {
		return $value == "True";
	}

	/* thank you weeg <3 */
	function ValidateRoblox_XML(string $XML_Data): bool {
		//FIND BETTER WAY TO DO THIS
		$xml = new DOMDocument();
		$xml->loadXML($XML_Data);

		if(!@$xml->schemaValidate($_SERVER['DOCUMENT_ROOT']."/roblox.xsd")){
			//throw new Exception("Invalid LEGACY ROBLOX XML Format file");
			return false;
		}else{
			//echo "Valid XML File<br>";
			return true;
		}
	}

	if($user == null) {
		if(isset($_GET['security'])) {
			$user = User::FromSecurityKey(urldecode($_GET['security']));
		}
	}

	if($user != null) {
		if(isset($_GET['assetid'])) {
			$assetid = intval($_GET['assetid']);

			if($assetid == 0) {
				// Publish new item

				$timer = 31;
				if($user->GetLatestAssetUploaded() != null) {
					$difference = (time()-($user->GetLatestAssetUploaded()->created_at->getTimestamp()-3600));
					$timer = $difference;
				}

				if($timer < 30) {
					http_response_code(501);
					die("You are uploading too many assets! Wait a bit!");
				}

				/*
					type
					name
					description
					ispublic
					commentsenabled
					serversize
					chattype
					iscopylocked
				*/

				if(
					isset($_GET['type']) &&
					isset($_GET['name']) &&
					isset($_GET['description']) &&
					isset($_GET['ispublic']) &&
					isset($_GET['commentsenabled'])
				) {
					$type = $_GET['type'];
					$name = urldecode($_GET['name']);
					$description = urldecode($_GET['description']);
					$public = FunnyStrToBool($_GET['ispublic']);
					$comments_enabled = FunnyStrToBool($_GET['commentsenabled']);

					$recieveddata = file_get_contents("php://input");
					//echo "parsed:".$recieveddata;
					if(strlen(gzdecode($recieveddata)) != 0) {
						$recieveddata = gzdecode($recieveddata);
						echo "decoding using gz\n";
					}					

					if(strtolower($type) == "place")  {
						if(isset($_GET['serversize']) &&
							isset($_GET['chattype']) &&
							isset($_GET['iscopylocked'])
						) {
							$server_size = intval($_GET['serversize']);
							$chattype = ChatType::index(intval($_GET['chattype']));
							$copylocked = FunnyStrToBool($_GET['iscopylocked']);

							AssetUploader::UploadPlace($name, $description, $recieveddata, $public, $copylocked, $comments_enabled, $chattype, $server_size, $user);
							
							http_response_code(200);
							die("Uploaded successfully!");
						}
					}

					
				} else {
					die(http_response_code(502));
				}

			} else {
				$asset = Asset::FromID($assetid);

				$recieveddata = file_get_contents("php://input");
				//echo "parsed:".$recieveddata;
				if(strlen(gzdecode($recieveddata)) != 0) {
					$recieveddata = gzdecode($recieveddata);
					echo "decoding using gz\n";
				}

				if($asset != null && $asset->creator->id == $user->id) {
					// If the user owns this asset, then allow publishing.
					
					print_r(AssetUploader::UpdatePlace($assetid, $recieveddata));
					http_response_code(200);
					die("Uploaded successfully!");
				}
			}
		}
	} else {
		http_response_code(503);
		die("Action failed.");
	}

	http_response_code(500);
	die("Action failed.");

?>