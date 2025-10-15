<?php

	require_once $_SERVER['DOCUMENT_ROOT']."/core/asset.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/userutils.php";

	$user = UserUtils::RetrieveUser();

	function cropAlign($image, $cropWidth, $cropHeight, $horizontalAlign = 'center', $verticalAlign = 'middle') {
		$width = imagesx($image);
		$height = imagesy($image);
		$horizontalAlignPixels = calculatePixelsForAlign($width, $cropWidth, $horizontalAlign);
		$verticalAlignPixels = calculatePixelsForAlign($height, $cropHeight, $verticalAlign);
		return imageCrop($image, [
			'x' => $horizontalAlignPixels[0],
			'y' => $verticalAlignPixels[0],
			'width' => $horizontalAlignPixels[1],
			'height' => $verticalAlignPixels[1]
		]);
	}
	//https://stackoverflow.com/questions/6891352/crop-image-from-center-php
	function calculatePixelsForAlign($imageSize, $cropSize, $align) {
		switch ($align) {
			case 'left':
			case 'top':
				return [0, min($cropSize, $imageSize)];
			case 'right':
			case 'bottom':
				return [max(0, $imageSize - $cropSize), min($cropSize, $imageSize)];
			case 'center':
			case 'middle':
				return [
					max(0, floor(($imageSize / 2) - ($cropSize / 2))),
					min($cropSize, $imageSize),
				];
			default: return [0, $imageSize];
		}
	}

	if(isset($_GET['id'])) {
		$id = intval($_GET['id']);

		$specialcase = false;

		$asset = Asset::FromID($id);
		if($asset != null) {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
			
			if($asset->status == AssetStatus::ACCEPTED || $user != null && $user->IsAdmin()) {
				
				$stmt = $con->prepare('SELECT * FROM `assetversions` WHERE `version_assetid` = ? ORDER BY `version_id` DESC');
				$stmt->bind_param('i', $id);
				$stmt->execute();

				$stmt_result = $stmt->get_result();

				$md5hash = $stmt_result->fetch_assoc()['version_md5thumb'];

				if($md5hash == "sound" && $asset->type == AssetType::AUDIO) {
					$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/audio.png");
				} else if($md5hash == "script" && $asset->type == AssetType::LUA) {
					$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/script.png");
				} else {
					if($asset->relatedasset != null || $asset->type == AssetType::IMAGE) {
						if(file_exists($_SERVER['DOCUMENT_ROOT']."/../assets/$md5hash")) {
							$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/../assets/$md5hash");
							$specialcase = true;
						} else {
							$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavailable.jpg");
						}
					} else {
						if(file_exists($_SERVER['DOCUMENT_ROOT']."/../assets/thumbs/$md5hash")) {
							$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/../assets/thumbs/$md5hash");
						} else {
							$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavailable.jpg");
						}
					}
					
				}
			} else if($asset->status == AssetStatus::PENDING) {
				$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/review-pending.png");
			} else {
				$contents = file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/rejected.png");
			}
			

			ob_clean();

			if(isset($_GET['sxy'])) {
				$size = intval($_GET['sxy']);
				if($size < 16 || $size > 420) {
					$size = 420;
				}

				$image = imagecreatefromstring($contents);
				$width = imagesx($image);
				$height = imagesy($image);
				
				// Mostly just used for places in stuff/create pages
				if($width != $height) {
					if($width > $height) {
						$cropSize = $height;
					}

					if($width < $height) {
						$cropSize = $width;
					}

					$image = cropAlign($image,$cropSize, $cropSize);
				}

				$image = imagescale($image, $size, $size);
				imagesavealpha($image, true);
				header("Content-Type: image/png");
				ob_clean();
				imagepng($image);
				
			} else if(isset($_GET['sx']) && isset($_GET['sy'])) {
				$sizex = intval($_GET['sx']);
				if($sizex < 16 || $sizex > 1080) {
					$sizex = 420;
				}

				$sizey = intval($_GET['sy']);
				if($sizey < 16 || $sizey > 1080) {
					$sizey = 420;
				}

				$image = imagecreatefromstring($contents);
				$image = imagescale($image, $sizex, $sizey);
				imagesavealpha($image, true);
				
				header("Content-Type: image/png");
				ob_clean();
				imagepng($image);
			} else {
				$file_info = new finfo(FILEINFO_MIME_TYPE);
				$mime = $file_info->buffer($contents);

				header("Content-Type: $mime");
				ob_clean();
				echo $contents;
			}

			
		}
	}

?>