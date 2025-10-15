<?php 
	require_once $_SERVER['DOCUMENT_ROOT']."/core/asset.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/userutils.php";

	$user = UserUtils::RetrieveUser();
	if($user == null) {
		die("Hey have you tried logging in before doing this? <br><a href='javascript:window.close()'>No...</a>");
	}

	function ReturnNotUnicodedString(string $contents) {
		$blockedchars = array('𒐫', '‮', '﷽', '𒈙', '⸻ ', '꧅');
		return str_replace($blockedchars, '', trim($contents));
	}

	function FunnyBoolToStr(bool $value) {
		return $value ? "True" : "False";
	}

	$verifiedcrap = false;

	if(
		isset($_POST['ANORRL$IDE$Publish$Place$Name']) && 
		isset($_POST['ANORRL$IDE$Publish$Place$Description']) && 
		isset($_POST['ANORRL$IDE$Publish$Place$ServerSize']) && 
		isset($_POST['ANORRL$IDE$Publish$Place$ChatType']) && 
		isset($_POST['ANORRL$IDE$Publish$Place$Submit'])
	) {

		$name = ReturnNotUnicodedString($_POST['ANORRL$IDE$Publish$Place$Name']);
		$description = ReturnNotUnicodedString($_POST['ANORRL$IDE$Publish$Place$Description']);

		$server_size = intval($_POST['ANORRL$IDE$Publish$Place$ServerSize']) <= 0 ? 12 : intval($_POST['ANORRL$IDE$Publish$Place$ServerSize']);

		$preprocessed_chattype = intval($_POST['ANORRL$IDE$Publish$Place$ChatType']);
		$chattype = ($preprocessed_chattype > 2 || $preprocessed_chattype < 0) ? ChatType::BOTH->ordinal() : $preprocessed_chattype;

		$isPublic = isset($_POST['ANORRL$IDE$Publish$Place$ServerSize']);
		$commentsEnabled = isset($_POST['ANORRL$IDE$Publish$Place$ServerSize']);
		$isCopylocked = isset($_POST['ANORRL$IDE$Publish$Place$Copylocked']);

		if(strlen($name) < 4) {
			die("Name must not be less than 4 characters!");
		}

		$timer = 31;
		if($user->GetLatestAssetUploaded() != null) {
			$difference = (time()-($user->GetLatestAssetUploaded()->created_at->getTimestamp()-3600));
			$timer = $difference;
		}

		if($timer < 30) {
			die("You are uploading too many assets! Wait a bit!");
		}
	
		$verifiedcrap = true;
	}

	
?>
<?php if(!$verifiedcrap): ?>
<!DOCTYPE html>
<html>
	<head>
		<title>Publish Place - ANORRL</title>
		<link rel="icon" type="image/x-icon" href="/favicon.ico">
		<link rel="stylesheet" href="/css/AllCSS.css?t=<?= time() ?>">
		<script src="/js/jquery.js"></script>
		<script src="/js/main.js?t=<?= time() ?>"></script>
		<style>

			h1, h2, h3, h4 {
				margin: 0;
			}

			h2 {
				margin-top: 10px;
			}

			#PublishContainer #ItemDetails {
				background: #2c2c2c;
				border: 2px solid black;
				padding: 10px;
				width: 866px;
			}

			#PublishContainer #ItemDetails table {
				border: 2px solid black;
				padding: 10px;
				background: #222;
				width:512px;
				
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
								<div id="DetailStack">
									<h4>Information</h4>
									<table>
										<tr>
											<td>Name</td>
											<td><input type="text" name="ANORRL$IDE$Publish$Place$Name" value="My Place" minlength="3" maxlength="128"></td>
										</tr>
										<tr>
											<td>Description</td>
											<td><textarea style="height: 50px;" name="ANORRL$IDE$Publish$Place$Description"></textarea></td>
										</tr>
										<tr>
											<td>Public</td>
											<td><input type="checkbox" name="ANORRL$IDE$Publish$Place$PublicBox" checked></td>
										</tr>
										<tr>
											<td>Enable Comments</td>
											<td><input type="checkbox" name="ANORRL$IDE$Publish$Place$CommentsBox" checked></td>
										</tr>
									</table>
								</div>
								<div id="DetailStack">
									<h4 style="margin-top: 10px">Place Settings</h4>
									<table>
										<tr>
											<td>Server Size</td>
											<td><input type="number" name="ANORRL$IDE$Publish$Place$ServerSize" value="12"></td>
										</tr>
										<tr>
											<td>Chat Type</td>
											<td>
												<select name="ANORRL$IDE$Publish$Place$ChatType" id="cars">
													<option value="1">Classic</option>
													<option value="2">Bubble</option>
													<option value="0" selected>Both</option>
												</select>
											</td>
										</tr>
										<tr>
											<td>Copylocked</td>
											<td><input type="checkbox" name="ANORRL$IDE$Publish$Place$Copylocked" checked></td>
										</tr>
									</table>
									<input type="submit" value="Publish" name="ANORRL$IDE$Publish$Place$Submit" style="text-align: center">
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
<?php else: ?>
<?php
	if(session_status() != PHP_SESSION_ACTIVE) {
		session_start();
	}

	// Prevent user from uploading the same place again by refreshing.
	if(isset($_SESSION['HasUploaded']) && $_SESSION['HasUploaded']) {
		$_SESSION['HasUploaded'] = false;
		die("<script>window.close()</script>");
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" >
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Upload</title>
		<link href="/css/RobloxOld.css" rel="stylesheet" type="text/css" />
	</head>
	<body bgcolor="buttonface" scroll="no">
		<form name="PublishContent" method="post" action="Upload.aspx" id="PublishContent">
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
			<script type="text/javascript">
				function uploadData()
				{
					try
					{
						window.external.SaveUrl('http://<?= $_SERVER['SERVER_NAME'] ?>/Data/Upload.ashx?assetid=0&type=Place&name=<?= urlencode($name) ?>&description=<?= urlencode($description) ?>&ispublic=<?= FunnyBoolToStr($isPublic) ?>&commentsenabled=<?= FunnyBoolToStr($commentsEnabled) ?>&serversize=<?= $server_size ?>&chattype=<?= $chattype ?>&iscopylocked=<?= FunnyBoolToStr($isCopylocked) ?>');
						document.getElementById("Uploading").style.display='none';
						document.getElementById("Confirmation").style.display='block';
					}
					catch (ex)
					{
						try
						{
							window.external.SaveUrl('http://<?= $_SERVER['SERVER_NAME'] ?>/Data/Upload.ashx?assetid=0&type=Place&name=<?= urlencode($name) ?>&description=<?= urlencode($description) ?>&ispublic=<?= FunnyBoolToStr($isPublic) ?>&commentsenabled=<?= FunnyBoolToStr($commentsEnabled) ?>&serversize=<?= $server_size ?>&chattype=<?= $chattype ?>&iscopylocked=<?= FunnyBoolToStr($isCopylocked) ?>');
							document.getElementById("Uploading").style.display='none';
							document.getElementById("Confirmation").style.display='block';
						}
						catch (ex2)
						{
							document.getElementById("Uploading").style.display='none';
							document.getElementById("Failure").style.display='block';
						}
					}
				}
				window.setTimeout("uploadData()", 1000);
			</script>
		</form>
	</body>
</html>
<?php
	$_SESSION['HasUploaded'] = true;
?>
<?php 
die();
endif ?>