<?php
	include_once $_SERVER['DOCUMENT_ROOT']."/core/asset.php";
	include_once $_SERVER['DOCUMENT_ROOT']."/core/renderer.php";
	include_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/userutils.php";

	
	// Horse shit yandere dev code what am i doing bruh

	class AssetUploader {

		private static function GetMD5OfData(mixed $data) {
			return md5($data);
		}


		private static function GrabVersionOfAsset(int $id) {}

		private static function UploadAsset(User|null $user, AssetType $type, string $name, string $description, bool $public, bool $hidden_ahh, mixed $file, bool $override = false): array {
			
			if($user != null && !$user->IsBanned()) {
				include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
				$md5 = self::GetMD5OfData($file);
				$directory = $_SERVER['DOCUMENT_ROOT'];
				$assetsdir = "$directory/../assets/";
				$filepath = $assetsdir.$md5;
				if(!file_exists($filepath)) {
					file_put_contents($filepath, $file);
				}

				$parsed_userid = $user->id;
				$parsed_type   = $type->ordinal();
				$parsed_public = intval($public);
				$parsed_hidden = intval($hidden_ahh);
				
				$status = AssetStatus::PENDING->ordinal();
				if($user->IsAdmin() || $override) {
					$status = AssetStatus::ACCEPTED->ordinal();
				}

				$stmt = $con->prepare('INSERT INTO `assets`(`asset_creator`, `asset_type`, `asset_name`, `asset_description`, `asset_public`, `asset_nevershow`, `asset_status`) VALUES (?, ?, ?, ?, ?, ?, ?);');
				$stmt->bind_param('iissiii', $parsed_userid, $parsed_type, $name, $description, $parsed_public, $parsed_hidden, $status);
				$stmt->execute();

				$id = $con->insert_id;

				$stmt = $con->prepare('INSERT INTO `assetversions`(`version_assetid`, `version_md5sig`, `version_assettype`) VALUES (?, ?, ?)');
				$stmt->bind_param('isi', $id, $md5, $parsed_type);
				$stmt->execute();
			
				return ["error" => false, "id" => $id];
			} else {
				return ["error" => true, "reason" => "User not authorised."];
			}
		}

		private static function UpdateAsset(int $id, User $user, mixed $file): array {
			$asset = Asset::FromID($id);
			if($user != null && !$user->IsBanned() && $asset != null && $asset->creator->id == $user->id) {
				include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
				$md5 = self::GetMD5OfData($file);

				if($md5 == $asset->GetLatestVersionDetails()->md5sig) { 
					return ["error" => true, "reason" => "I'm pretty sure you've already uploaded this?"];
				}

				$directory = $_SERVER['DOCUMENT_ROOT'];
				$assetsdir = "$directory/../assets/";
				$filepath = $assetsdir.$md5;
				if(!file_exists($filepath)) {
					file_put_contents($filepath, $file);
				}

				$parsed_userid = $user->id;
				$parsed_type = $asset->type->ordinal();
				
				$status = AssetStatus::PENDING->ordinal();
				if($user->IsAdmin()) {
					$status = AssetStatus::ACCEPTED->ordinal();
				}

				$new_versionid = count($asset->GetAllVersions())+1;

				$stmt = $con->prepare('INSERT INTO `assetversions`(`version_assetid`, `version_md5sig`, `version_assettype`, `version_subid`) VALUES (?, ?, ?, ?)');
				$stmt->bind_param('isii', $id, $md5, $parsed_type, $new_versionid);
				$stmt->execute();

				$versionid = $con->insert_id;

				$stmt = $con->prepare('UPDATE `assets` SET `asset_currentversion` = ?, `asset_lastedited` = now() WHERE `asset_id` = ?');
				$stmt->bind_param('ii', $new_versionid, $id);
				$stmt->execute();
			
				return ["error" => false, "versionid" => $versionid];
			} else {
				return ["error" => true, "reason" => "User not authorised."];
			}
			
		}

		private static function CheckMimeType($contents) {
			$file_info = new finfo(FILEINFO_MIME_TYPE);
			return $file_info->buffer($contents);
		} 

		
		public static function UploadImage(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();
			if($user->IsAdmin()) {
				if($file['error'] == 0) {
					// process singular asset
					$image_result = self::UploadAsset($user, AssetType::IMAGE, $name, "", false, true, file_get_contents($file['tmp_name']));
					if($image_result['error']) {
						return $image_result;
					} else {

						include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
						require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";

						$ta_id = TransactionUtils::GenerateID();
						$ta_assettype = AssetType::IMAGE->ordinal();
						$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
						$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_result['id'], $ta_assettype, $user->id);
						$stmt_processtransaction->execute();


						$md5hashfile = md5(file_get_contents($file['tmp_name']));
						$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
						$stmt->bind_param('si', $md5hashfile, $image_result['id']);
						$stmt->execute();

						return ["error" => false, "id" => $image_result['id']];
					}
				} else {
					return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
				}
			} else {
				return ["error" => true, "reason" => "You are not authorised to perform this action!"];
			}
			
		}

		public static function UploadLua(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();
			if($user->IsAdmin()) {
				if($file['error'] == 0) {
					$lua_data = file_get_contents($file['tmp_name']);

					// process singular asset
					$image_result = self::UploadAsset($user, AssetType::LUA, $name, "", false, true, $lua_data);
					if($image_result['error']) {
						return $image_result;
					} else {

						include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
						require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";

						$ta_id = TransactionUtils::GenerateID();
						$ta_assettype = AssetType::LUA->ordinal();
						$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
						$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_result['id'], $ta_assettype, $user->id);
						$stmt_processtransaction->execute();


						$md5hashfile = md5($image_data);
						$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = 'script' WHERE `version_assetid` = ?");
						$stmt->bind_param('i', $image_result['id']);
						$stmt->execute();

						return ["error" => false, "id" => $image_result['id']];
					}
				} else {
					return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
				}
			} else {
				return ["error" => true, "reason" => "You are not authorised to perform this action!"];
			}
			
		}

		public static function UpdateLua(int $id, array $file) {
			$user = UserUtils::RetrieveUser();
			if($user->IsAdmin()) {
				if($file['error'] == 0) {
					$lua_data = file_get_contents($file['tmp_name']);

					// process singular asset
					$image_result = self::UpdateAsset($id, $user, $lua_data);
					if($image_result['error']) {
						return $image_result;
					} else {
						include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
						require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";


						$md5hashfile = md5($lua_data);
						$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = 'script' WHERE `version_id` = ?");
						$stmt->bind_param('i', $image_result['versionid']);
						$stmt->execute();

						return ["error" => false, "id" => $image_result['versionid']];
					}
				} else {
					return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
				}
			} else {
				return ["error" => true, "reason" => "You are not authorised to perform this action!"];
			}
			
		}

		public static function UploadMesh(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {
				$mesh_data = file_get_contents($file['tmp_name']);

				if(!str_starts_with(trim($mesh_data), "version 1.0")) {
					return ['error' => true, 'reason' => 'Version 1.0x meshes only!'];
				}

				// process singular asset
				$image_result = self::UploadAsset($user, AssetType::MESH, $name, "", false, true, $mesh_data);
				if($image_result['error']) {
					return $image_result;
				} else {

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
					require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";

					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::MESH->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_result['id'], $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = md5($mesh_data);
					$assetsdir = "$directory/../assets/thumbs/$md5hashfile";

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $image_result['id']);
					$stmt->execute();

					$render = TheFuckingRenderer::RenderMesh($image_result['id']);
					$data = "data:image/png;base64,$render";
					list($type, $data) = explode(';', $data);
					list(, $data)      = explode(',', $data);
					$data = base64_decode($data);

					$render_image = imagecreatefromstring($data);
					imagesavealpha($render_image, true);
					imagepng($render_image, $assetsdir);

					return ["error" => false, "id" => $image_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UpdateMesh(int $id, array $file) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {
				$mesh_data = file_get_contents($file['tmp_name']);

				if(!str_starts_with(trim($mesh_data), "version 1.0")) {
					return ['error' => true, 'reason' => 'Version 1.0x meshes only!'];
				}

				// process singular asset
				$image_result = self::UpdateAsset($id, $user, $mesh_data);
				if($image_result['error']) {
					return $image_result;
				} else {

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = md5($mesh_data);
					$assetsdir = "$directory/../assets/thumbs/$md5hashfile";

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_id` = ?");
					$stmt->bind_param('si', $md5hashfile, $image_result['versionid']);
					$stmt->execute();

					if(!file_exists($assetsdir)) {
						$render = TheFuckingRenderer::RenderPlayer($shirt_result['id']);
						$data = "data:image/png;base64,$render";
						list($type, $data) = explode(';', $data);
						list(, $data)      = explode(',', $data);
						$data = base64_decode($data);

						$render_image = imagecreatefromstring($data);
						imagesavealpha($render_image, true);
						imagepng($render_image, $assetsdir);
					}

					return ["error" => false, "id" => $image_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UpdatePlace(int $id, array|string $file) {
			$user = UserUtils::RetrieveUser();

			if(is_array($file)) {
				if($file['error'] != 0) {
					return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
				}
				$place_data = file_get_contents($file['tmp_name']);
			} else {
				$place_data = $file;
			}

			// process singular asset
			$place_result = self::UpdateAsset($id, $user, $file);
			if($place_result['error']) {
				return $place_result;
			} else {

				include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";

				$directory = $_SERVER['DOCUMENT_ROOT'];
				$md5hashfile = md5($place_data);
				$assetsdir = "$directory/../assets/thumbs/$md5hashfile";

				$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_id` = ?");
				$stmt->bind_param('si', $md5hashfile, $place_result['versionid']);
				$stmt->execute();

				if(!file_exists($assetsdir)) {
					$render = TheFuckingRenderer::RenderPlace($id);
					$data = "data:image/png;base64,$render";
					list($type, $data) = explode(';', $data);
					list(, $data)      = explode(',', $data);
					$data = base64_decode($data);

					$render_image = imagecreatefromstring($data);
					imagesavealpha($render_image, true);
					imagepng($render_image, $assetsdir);
				}

				return ["error" => false, "vid" => $place_result['versionid']];
			}
		}

		public static function UploadDecal(string $name, string $description, array $file, bool $face = false) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {
				$original_image = imagecreatefromstring(file_get_contents($file['tmp_name']));
				list($width, $height) = getimagesize($file['tmp_name']);

				$image = imagecreatetruecolor($width, $height);
				$bga = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $bga);
				imagecopy($image, $original_image, 0, 0, 0, 0, $width, $height);
				imagesavealpha($image, true);

				if($width > $height) {
					$new_width = 420;
					$new_height = -1;
				} else if($width < $height) {
					$new_width = -1;
					$new_height = 420;
				} else {
					$new_width = 420;
					$new_height = 420;
				}

				$resultimage = imagescale($image, $new_width, $new_height);
				imagesavealpha($resultimage, true);

				ob_start();
				imagepng($resultimage);
				$image_data = ob_get_contents();
				ob_end_clean();

				// process singular asset
				$image_result = self::UploadAsset($user, AssetType::IMAGE, $name, "", false, true, $image_data);
				if($image_result['error']) {
					return $image_result;
				} else {
					$image_id = $image_result['id'];
					$decal_data = <<<EOT
					<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://arl.lambda.cam/roblox.xsd" version="4">
						<External>null</External>
						<External>nil</External>
						<Item class="Decal" referent="RBX0">
							<Properties>
								<token name="Face">0</token>
								<string name="Name">Decal</string>
								<float name="Shiny">20</float>
								<float name="Specular">0</float>
								<Content name="Texture">
								<url>http://arl.lambda.cam/asset/?id=$image_id</url>
								</Content>
								<bool name="archivable">true</bool>
							</Properties>
						</Item>
					</roblox>
					EOT;
					$decal_result = self::UploadAsset($user, $face ? AssetType::FACE : AssetType::DECAL, $name, $description, false, false, $decal_data);
					if($decal_result['error']) {
						return $decal_result;
					}

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
					require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";

					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::IMAGE->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_id, $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = ($face ? AssetType::FACE : AssetType::DECAL)->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'none', 0, ?, ?, ?)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $decal_result['id'], $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = md5($image_data);
					$assetsdir = "$directory/../assets/thumbs/$md5hashfile";
					imagepng($resultimage, $assetsdir);
					
					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $image_id);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $decal_result['id']);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assets` SET `asset_relatedid` = ? WHERE `asset_id` = ?;");
					$stmt->bind_param('ii', $decal_result['id'], $image_id);
					$stmt->execute();

					return ["error" => false, "id" => $decal_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UploadAudio(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {

				$data = file_get_contents($file['tmp_name']);

				if(self::CheckMimeType($data) != "audio/mpeg") {
					return ["error" => true, "reason" => "Audio file was not mp3!"];
				}

				// process singular asset
				$audio_result = self::UploadAsset($user, AssetType::AUDIO, $name, "", false, true, $data);
				if($audio_result['error']) {
					return $audio_result;
				} else {
					$audio_id = $audio_result['id'];
					$audio_data = <<<EOT
					<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://arl.lambda.cam/roblox.xsd" version="4">
						<External>null</External>
						<External>nil</External>
						<Item class="Sound" referent="RBX0">
							<Properties>
								<bool name="Looped">false</bool>
								<string name="Name">Sound</string>
								<float name="Pitch">1</float>
								<bool name="PlayOnRemove">false</bool>
								<Content name="SoundId"><url>http://arl.lambda.cam/asset/?id=$audio_id</url></Content>
								<float name="Volume">0.5</float>
							</Properties>
						</Item>
					</roblox>
					EOT;
					$audiomodel_result = self::UploadAsset($user, AssetType::AUDIO, $name, $description, false, false, $audio_data);
					if($audiomodel_result['error']) {
						return $audiomodel_result;
					}

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
					require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";
					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::AUDIO->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'none', 0, ?, ?, ?)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $audiomodel_result['id'], $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = "sound";
					
					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $audio_id);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $audiomodel_result['id']);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assets` SET `asset_relatedid` = ? WHERE `asset_id` = ?;");
					$stmt->bind_param('ii', $audiomodel_result['id'], $audio_id);
					$stmt->execute();

					return ["error" => false, "id" => $audiomodel_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UploadTShirt(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {
				$original_image = imagecreatefromstring(file_get_contents($file['tmp_name']));
				list($width, $height) = getimagesize($file['tmp_name']);

				$image = imagecreatetruecolor($width, $height);
				$bga = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $bga);
				imagecopy($image, $original_image, 0, 0, 0, 0, $width, $height);
				imagesavealpha($image, true);

				if($width > $height) {
					$new_width = 420;
					$new_height = -1;
				} else if($width < $height) {
					$new_width = -1;
					$new_height = 420;
				} else {
					$new_width = 420;
					$new_height = 420;
				}
				
				// calculate resized image
				$r_image = imagescale($image, $new_width, $new_height);
				// get size parameters of scaled image as for easier copying
				$r_width  = imagesx($r_image);
				$r_height = imagesy($r_image);
				
				// if the height is taller than the width then attempt to center it
				if($r_width < $r_height) {
					$dst_x = (420 - $r_width)/2;
				} else {
					$dst_x = 0;
				}
				
				$resizedimage = imagecreatetruecolor(420, 420);
				$trans_colour = imagecolorallocatealpha($resizedimage, 0, 0, 0, 127);
				imagefill($resizedimage, 0, 0, $trans_colour);
				imagecopyresampled($resizedimage, $image, $dst_x, 0, 0, 0, $r_width, $r_height, $width, $height);
				
				
				// create tshirt THUMBNAIL image
				// create base image of size 420x420 with transparent background
				$tshirt = imagecreatetruecolor(420, 420);
				$trans_colour = imagecolorallocatealpha($tshirt, 0, 0, 0, 127);
				imagefill($tshirt, 0, 0, $trans_colour);
				
				// paste tshirt (the icon thing) into image
				$bg_tshirt = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/images/tshirt.png");
				imagecopy($tshirt, $bg_tshirt, 0, 0, 0, 0, 420, 420);
				// and paste the processed resizedimage on top of it
				imagecopyresampled($tshirt, $resizedimage, 84, 84, 0, 0, 252, 252, 420, 420);
				
				imagesavealpha($resizedimage, true);

				ob_start();
				imagepng($resizedimage);
				$image_data = ob_get_contents();
				ob_end_clean();

				// process singular asset
				$image_result = self::UploadAsset($user, AssetType::IMAGE, $name, "", false, true, $image_data);
				if($image_result['error']) {
					return $image_result;
				} else {
					$image_id = $image_result['id'];
					$tshirt_data = <<<EOT
					<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.roblox.com/roblox.xsd" version="4">
						<External>null</External>
						<External>nil</External>
						<Item class="ShirtGraphic" referent="RBX0">
							<Properties>
								<Content name="Graphic">
								<url>http://arl.lambda.cam/asset/?id=$image_id</url>
								</Content>
								<string name="Name">Shirt Graphic</string>
								<bool name="archivable">true</bool>
							</Properties>
						</Item>
					</roblox>
		
					EOT;
					$decal_result = self::UploadAsset($user, AssetType::TSHIRT, $name, $description, false, false, $tshirt_data);
					if($decal_result['error']) {
						return $decal_result;
					}

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
					require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";
					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::IMAGE->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_id, $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::TSHIRT->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'none', 0, ?, ?, ?)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $decal_result['id'], $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = md5($image_data);
					$assetsdir = "$directory/../assets/thumbs/$md5hashfile";
					imagesavealpha($tshirt, true);
					imagepng($tshirt, $assetsdir);
					
					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $image_id);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $decal_result['id']);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assets` SET `asset_relatedid` = ? WHERE `asset_id` = ?;");
					$stmt->bind_param('ii', $decal_result['id'], $image_id);
					$stmt->execute();

					return ["error" => false, "id" => $decal_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UploadShirt(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {
				$original_image = imagecreatefromstring(file_get_contents($file['tmp_name']));
				list($width, $height) = getimagesize($file['tmp_name']);

				ob_start();
				imagepng($original_image);
				$image_data = ob_get_contents();
				ob_end_clean();

				if($width != 585 || $height != 559) {
					return ["error" => true, "reason" => "Image size was not correct! Expected: 585 x 559 but instead got: $width x $height"];
				}

				// process singular asset
				$image_result = self::UploadAsset($user, AssetType::IMAGE, $name, "", false, true, $image_data);
				if($image_result['error']) {
					return $image_result;
				} else {
					$image_id = $image_result['id'];
					$tshirt_data = <<<EOT
					<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://arl.lambda.cam/roblox.xsd" version="4">
						<External>null</External>
						<External>nil</External>
						<Item class="Shirt" referent="RBX9F2F0ED79CBE4747857ED528B4B05979">
							<Properties>
								<string name="Name">Clothing</string>
								<Content name="ShirtTemplate">
								<url>http://arl.lambda.cam/asset/?id=$image_id</url>
								</Content>
							</Properties>
						</Item>
					</roblox>
					EOT;
					$shirt_result = self::UploadAsset($user, AssetType::SHIRT, $name, $description, false, false, $tshirt_data);
					if($shirt_result['error']) {
						return $shirt_result;
					}

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
					require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";
					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::IMAGE->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_id, $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::SHIRT->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'none', 0, ?, ?, ?)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $shirt_result['id'], $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = md5($image_data);
					$assetsdir = "$directory/../assets/thumbs/$md5hashfile";
					
					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $image_id);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $shirt_result['id']);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assets` SET `asset_relatedid` = ? WHERE `asset_id` = ?;");
					$stmt->bind_param('ii', $shirt_result['id'], $image_id);
					$stmt->execute();

					if(!file_exists($assetsdir)) {
						$render = TheFuckingRenderer::RenderPlayer($shirt_result['id']);
						$data = "data:image/png;base64,$render";
						list($type, $data) = explode(';', $data);
						list(, $data)      = explode(',', $data);
						$data = base64_decode($data);

						$render_image = imagecreatefromstring($data);
						imagesavealpha($render_image, true);
						imagepng($render_image, $assetsdir);
					}

					

					return ["error" => false, "id" => $shirt_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UploadPants(string $name, string $description, array $file) {
			$user = UserUtils::RetrieveUser();

			if($file['error'] == 0) {
				$original_image = imagecreatefromstring(file_get_contents($file['tmp_name']));
				list($width, $height) = getimagesize($file['tmp_name']);

				ob_start();
				imagepng($original_image);
				$image_data = ob_get_contents();
				ob_end_clean();

				if($width != 585 || $height != 559) {
					return ["error" => true, "reason" => "Image size was not correct! Expected: 585 x 559 but instead got: $width x $height"];
				}

				// process singular asset
				$image_result = self::UploadAsset($user, AssetType::IMAGE, $name, "", false, true, $image_data);
				if($image_result['error']) {
					return $image_result;
				} else {
					$image_id = $image_result['id'];
					$tshirt_data = <<<EOT
					<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://arl.lambda.cam/roblox.xsd" version="4">
						<External>null</External>
						<External>nil</External>
						<Item class="Pants" referent="RBX9F2F0ED79CBE4747857ED528B4B05979">
							<Properties>
								<string name="Name">Clothing</string>
								<Content name="PantsTemplate">
								<url>http://arl.lambda.cam/asset/?id=$image_id</url>
								</Content>
							</Properties>
						</Item>
					</roblox>
					EOT;
					$pants_result = self::UploadAsset($user, AssetType::PANTS, $name, $description, false, false, $tshirt_data);
					if($pants_result['error']) {
						return $pants_result;
					}

					include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
					require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";
					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::IMAGE->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`, `ta_showsupatall`) VALUES (?, ?, 'none', 0, ?, ?, ?, 0)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $image_id, $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$ta_id = TransactionUtils::GenerateID();
					$ta_assettype = AssetType::PANTS->ordinal();
					$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'none', 0, ?, ?, ?)");
					$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $pants_result['id'], $ta_assettype, $user->id);
					$stmt_processtransaction->execute();

					$directory = $_SERVER['DOCUMENT_ROOT'];
					$md5hashfile = md5($image_data);
					$assetsdir = "$directory/../assets/thumbs/$md5hashfile";
					
					
					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $image_id);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
					$stmt->bind_param('si', $md5hashfile, $pants_result['id']);
					$stmt->execute();

					$stmt = $con->prepare("UPDATE `assets` SET `asset_relatedid` = ? WHERE `asset_id` = ?;");
					$stmt->bind_param('ii', $pants_result['id'], $image_id);
					$stmt->execute();

					if(!file_exists($assetsdir)) {
						$render = TheFuckingRenderer::RenderPlayer($pants_result['id']);
						$data = "data:image/png;base64,$render";
						list($type, $data) = explode(';', $data);
						list(, $data)      = explode(',', $data);
						$data = base64_decode($data);

						$render_image = imagecreatefromstring($data);
						imagesavealpha($render_image, true);
						imagepng($render_image, $assetsdir);
					}

					return ["error" => false, "id" => $pants_result['id']];
				}
			} else {
				return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
			}
		}

		public static function UploadPlace(string $name, string $description, array|string $file,
			bool $public = true,
			bool $copylocked = true,
			bool $comments_enabled = true,
			int $server_size = 12,
			User|null $user = null,
			bool $firstplace = false
		) {
			if($user == null) {
				$user = UserUtils::RetrieveUser();
			}

			if(is_array($file)) {
				if($file['error'] != 0) {
					return ["error" => true, "reason" => "Something wrong occurred when uploading!"];
				}
				$place_data = file_get_contents($file['tmp_name']);
			} else {
				$place_data = $file;
			}
			

			// process singular asset
			$place_result = self::UploadAsset($user, AssetType::PLACE, $name, $description, $public, false, $place_data, $firstplace);
			if($place_result['error']) {
				return $place_result;
			} else {
				$place_id = $place_result['id'];

				include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
				require_once $_SERVER['DOCUMENT_ROOT']."/core/utilities/transactionutils.php";

				$ta_id = TransactionUtils::GenerateID();
				$ta_assettype = AssetType::PLACE->ordinal();
				$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'none', 0, ?, ?, ?)");
				$stmt_processtransaction->bind_param('siiii', $ta_id, $user->id, $place_id, $ta_assettype, $user->id);
				$stmt_processtransaction->execute();

				$directory = $_SERVER['DOCUMENT_ROOT'];
				$md5hashfile = md5($place_data);
				$assetsdir = "$directory/../assets/thumbs/$md5hashfile";
				
				$stmt = $con->prepare("UPDATE `assetversions` SET `version_md5thumb` = ? WHERE `version_assetid` = ?");
				$stmt->bind_param('si', $md5hashfile, $place_id);
				$stmt->execute();

				$stmt_addplace = $con->prepare("INSERT INTO `asset_places`(`place_id`, `place_copylocked`, `place_serversize`) VALUES (?, ?, ?)");
				
				$place_copylocked = $copylocked ? 1 : 0;
				
				$stmt_addplace->bind_param('iii', $place_id, $place_copylocked, $server_size);
				$stmt_addplace->execute();

				if(!file_exists($assetsdir) && ($user->IsAdmin() || $firstplace)) {
					
					$render = TheFuckingRenderer::RenderPlace($place_id);
					$data = "data:image/png;base64,$render";
					list($type, $data) = explode(';', $data);
					list(, $data)      = explode(',', $data);
					$data = base64_decode($data);

					$render_image = imagecreatefromstring($data);
					imagesavealpha($render_image, true);
					imagepng($render_image, $assetsdir);
				}

				return ["error" => false, "id" => $place_result['id']];
			}
		}

	}
?>