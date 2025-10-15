<?php

	/*
	{
		"ID": 15752,
		"Name": "Chat  v2.6 (by Hyp_nos)"
	},
	*/

	require_once $_SERVER['DOCUMENT_ROOT'] . "/core/asset.php";

	$assets = Asset::GetAssetsOfTypePaged("", AssetType::DECAL, 1, 25);

	$output_assets = [];

	foreach ($assets as $asset) {
		if($asset instanceof Asset) {
			array_push($output_assets, [
				"AssetID" => $asset->id,
				"Name" => $asset->name,
				"IsVoteable" => true
			]);
		}
	}

	header("Content-Type: application/json");

	echo json_encode($output_assets);

?>