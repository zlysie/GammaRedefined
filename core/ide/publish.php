<?php 
	require_once $_SERVER['DOCUMENT_ROOT']."/core/asset.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/userutils.php";

	$user = UserUtils::RetrieveUser();

	if($user == null) {
		die("Hey have you tried logging in before doing this? <br><a href='javascript:window.close()'>No...</a>");
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Publish to a Place - ANORRL</title>
		<link rel="icon" type="image/x-icon" href="/favicon.ico">
		<link rel="stylesheet" href="/css/AllCSS.css?t=<?= time() ?>">
		<script src="/js/jquery.js"></script>
		<script src="/js/main.js?t=<?= time() ?>"></script>
		<script src="/js/publish.js"></script>
		<style>

			h1, h2, h3, h4 {
				margin: 0;
			}

			h2 {
				margin-top: 10px;
			}

			#PublishContainer #ItemDetails {
				background: #000;
				border: 2px solid black;
				padding: 10px;
				width: 866px;
			}

			#ItemDetails input[type=text],
			#ItemDetails input[type=number],
			#ItemDetails select,
			#ItemDetails textarea {
				border: 2px solid black;
				background: #444;
				padding: 2px 4px;
				color: white;
				resize: vertical;
			}

			#ItemDetails input[type=text],
			#ItemDetails textarea {
				width: 320px;
			}

			#ItemDetails input[type=submit],
			#ItemDetails a[type=submit],
			#ItemDetails label[for=files] {
				border: 2px solid black;
				background: black;
				color: white;
				padding: 4px 8px;
				font-weight: bold;
				font-family: punk;
				margin: 10px auto;
				margin-bottom: 0px;
				display: block;
			}

			#ItemDetails input[type=submit]:hover,
			#ItemDetails input[type=submit]:hover,
			#ItemDetails label[for=files]:hover {
				text-decoration: underline;
				background: #161616;
				cursor: pointer;
			}

			#ItemDetails label#filename {
				margin-left: 5px;
			}

			#PublishContainer #ItemDetails table td {
				width: 140px;
				min-width: 140px;
				vertical-align: top;
			}

			#PublishContainer #ItemDetails #DetailStack {
				margin: 0 auto;
				width: 510px;
			}

			#Body {
				background: none;
				border: none;
			}

			#PublishPlaces {
				padding: 5px;
				border: 2px solid black;
				background: #222;
			}

			#PublishPlaces .Place {
				border: 2px solid black;
				background: #1d1d1d;
				width:261px;
				padding: 5px;
				text-align: center;
				margin: 3px;
				display: inline-block;
			}

			#PublishPlaces .Place * {
				display:block;
				width:257px;
			}

			#PublishPlaces .Place img {
				border: 2px solid black;
				background: #111;
				width:257px;
				height:145px;
			}

			#PublishPlaces .Place span {
				margin-top: 5px;
				font-weight: bold;
			}

			#PublishPlaces .Place:hover,
			#PublishPlaces .Place:hover span {
				text-decoration: underline;
				cursor: pointer;
			}

			#PublishPlaces .Place:hover {
				border-color: white;
			}
		</style>
	</head>
	<body>
		<div id="Container">
			<div id="Body">
				<div id="BodyContainer">
					<div id="PublishContainer">
						<h2>Publish your lovely little place...</h2>
						<div id="ItemDetails">
							<form method="POST">
								<input name="ANORRL$IDE$Publish$Place$Action" hidden>
								<div id="PublishPlaces">
									<div class="Place" data-placeid="createnew">
										<img src="/images/ide/createnewplace.png">
										<span>Create a New Place</span>
									</div>
									<?php 
										$places = $user->GetAllOwnedAssetsOfType(AssetType::PLACE);
										
										if(count($places) != 0) {
											foreach($places as $place) {
												$place_id = $place->id;
												$place_name = $place->name;
												echo <<<EOT
												<div class="Place" data-placeid="$place_id">
													<img src="/thumbs/?id=$place_id&sx=261&sy=149">
													<span>$place_name</span>
												</div>
												EOT;
											}
										}
									?>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<link href="/css/RobloxOld.css" rel="stylesheet" type="text/css" />
		<form style="display:none;padding:15px;" scroll="no" name="PublishContent" id="PublishContent">
			<input id="DialogResult" type="hidden" />
			<div id="Uploading" style="DISPLAY: block; FONT-WEIGHT: bold; COLOR: royalblue">Uploading. Please wait...</div>
			<div id="Confirmation" style="display: none;">
				<table height="100%" width="100%">
					<tr valign="top" height="100%">
						<td>The upload has completed!</td>
					</tr>
					<tr>
						<td align="right">
							<table cellspacing="5" cellpadding="0" border="0">
								<tr>
									<td><input class="OKCancelButton" onclick="window.close(); return false" type="button" value="Close" /></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div id="Failure" style="display: none;">
				<p>The upload has failed.</p>
			</div>
		</form>
	</body>
</html>