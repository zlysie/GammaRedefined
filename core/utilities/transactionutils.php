<?php

	require_once $_SERVER["DOCUMENT_ROOT"]."/core/utilities/userutils.php";

	enum TransactionType {
		case CONES;
		case LIGHTS;
		case FREE;
	}

	class TransactionUtils {
		private static function getRandomString($length = 15): string {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString = '';
			
			for ($i = 0; $i < $length; $i++) {
				$index = rand(0, strlen($characters) - 1);
				$randomString .= $characters[$index];
			}
	
			return $randomString;
		}

		
		public static function GenerateID() {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$id = self::getRandomString(); //id
			$stmt = $con->prepare('SELECT * FROM `transactions` WHERE `ta_id` LIKE ?');
			$stmt->bind_param('s', $id);
			$stmt->execute();
			$stmt->store_result();
			
			$instances = $stmt->num_rows;
			
			if($instances != 0) {
				return self::GenerateID();
			} else {
				return $id;
			}
		}

		public static function StipendLightsToUser(int $user_id, int $amount = 250) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$ta_id = self::GenerateID();
			$ta_userid = $user_id;
			$ta_cost = $amount;
			$stmt = $con->prepare('INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`) VALUES (?, ?, "lights", ?)');
			$stmt->bind_param("sii", $ta_id, $ta_userid, $ta_cost);
			$stmt->execute();
		}

		public static function StipendConesToUser(int $user_id, int $amount = 100) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$ta_id = self::GenerateID();
			$ta_userid = $user_id;
			$ta_cost = $amount;
			$stmt = $con->prepare('INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`) VALUES (?, ?, "cones", ?)');
			$stmt->bind_param("sii", $ta_id, $ta_userid, $ta_cost);
			$stmt->execute();
		}

		public static function StipendCheckToUser(int $user_id) {
			$user = User::FromID($user_id);
			if($user != null && !$user->IsBanned() && $user->PendingStipend()) {
				


				include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
				$stmt_getuser = $con->prepare("SELECT * FROM `subscriptions` WHERE `userid` = ?");
				$stmt_getuser->bind_param('i', $user->id);
				$stmt_getuser->execute();
				$result = $stmt_getuser->get_result();


				if($result->num_rows == 1) {
					$stmt_user_status_check = $con->prepare('UPDATE `subscriptions` SET `lastpaytime` = now() WHERE `userid` = ?');
					$stmt_user_status_check->bind_param('i', $user->id);
					$stmt_user_status_check->execute();
				} else {
					$stmt_user_status_check = $con->prepare('INSERT INTO `subscriptions`(`userid`) VALUES (?)');
					$stmt_user_status_check->bind_param('i', $user->id);
					$stmt_user_status_check->execute();


				}

				self::StipendLightsToUser($user_id);
				self::StipendConesToUser($user_id);
			}
		}

		public static function BuyItem(string $type, int $asset_id): string {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			
			$get_user = UserUtils::RetrieveUser();
			$asset = Asset::FromID($asset_id);
			if($get_user != null && !$get_user->IsBanned()) {
				if($asset != null) {
					if(!$get_user->Owns($asset) && $asset->onsale) {
						if($asset->cost_cones == 0 && $asset->cost_lights == 0) {
							
							$ta_id = self::GenerateID();
							$ta_userid = $get_user->id;
							$ta_asset = $asset->id;
							$ordinal = $asset->type->ordinal();
							$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'lights', 0, ?, ?, ?)");
							$stmt_processtransaction->bind_param('siiii', $ta_id, $ta_userid, $ta_asset, $ordinal, $asset->creator->id);
							if($stmt_processtransaction->execute()) {
								$stmt_get_sale_count = $con->prepare("SELECT * FROM `transactions` WHERE `ta_asset` = ? AND `ta_userid`!= ?");
								$stmt_get_sale_count->bind_param('ii', $asset_id, $asset->creator->id);
								$stmt_get_sale_count->execute();
								$sale_count = $stmt_get_sale_count->get_result()->num_rows;
			
								$stmt_update_sale_stat = $con->prepare("UPDATE `assets` SET `asset_sales_count` = ? WHERE `asset_id` = ?");
								$stmt_update_sale_stat->bind_param('ii', $sale_count, $asset_id);
								$stmt_update_sale_stat->execute();
								return "yay";
							} else {
								return "Something went wrong at our end!";
							}
						} else {
							//die(($type == "cones" ? "that is surely cones!" : "that's literally something else")." $type");
							if($type == "cones" && (($asset->cost_lights == 0 && $asset->cost_cones != 0) || ($asset->cost_lights != 0 && $asset->cost_cones != 0))) {
								$user_amount = $get_user->GetNetCones();
								$asset_amount = $asset->cost_cones;

								$result = $user_amount-$asset_amount;

								if($result >= 0) {
									$ta_id = self::GenerateID();
									$ta_userid = $get_user->id;
									$ta_cost = $asset_amount;
									$ta_asset = $asset->id;
									$ordinal = $asset->type->ordinal();
									$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'cones', ?, ?, ?, ?)");
									$stmt_processtransaction->bind_param('siiiii', $ta_id, $ta_userid, $ta_cost, $ta_asset, $ordinal, $asset->creator->id);
									if($stmt_processtransaction->execute()) {
										$stmt_get_sale_count = $con->prepare("SELECT * FROM `transactions` WHERE `ta_asset` = ? AND `ta_userid`!= ?");
										$stmt_get_sale_count->bind_param('ii', $asset_id, $asset->creator->id);
										$stmt_get_sale_count->execute();
										$sale_count = $stmt_get_sale_count->get_result()->num_rows;
					
										$stmt_update_sale_stat = $con->prepare("UPDATE `assets` SET `asset_sales_count` = ? WHERE `asset_id` = ?");
										$stmt_update_sale_stat->bind_param('ii', $sale_count, $asset_id);
										$stmt_update_sale_stat->execute();
										return "yay";
									} else {
										return "Something went wrong at our end!";
									}
								} else {
									return "User did not have sufficient funds to perform this action!";
								}
							} else if($type == "lights" && (($asset->cost_lights != 0 && $asset->cost_cones == 0) || ($asset->cost_lights != 0 && $asset->cost_cones != 0))) {
								$user_amount = $get_user->GetNetLights();
								$asset_amount = $asset->cost_lights;

								$result = $user_amount-$asset_amount;

								if($result >= 0) {
									$ta_id = self::GenerateID();
									$ta_userid = $get_user->id;
									$ta_cost = $asset_amount;
									$ta_asset = $asset->id;
									$ordinal = $asset->type->ordinal();
									$stmt_processtransaction = $con->prepare("INSERT INTO `transactions`(`ta_id`, `ta_userid`, `ta_currency`, `ta_cost`, `ta_asset`, `ta_assettype`, `ta_assetcreator`) VALUES (?, ?, 'lights', ?, ?, ?, ?)");
									$stmt_processtransaction->bind_param('siiiii', $ta_id, $ta_userid, $ta_cost, $ta_asset, $ordinal, $asset->creator->id);
									if($stmt_processtransaction->execute()) {
										$stmt_get_sale_count = $con->prepare("SELECT * FROM `transactions` WHERE `ta_asset` = ? AND `ta_userid`!= ?");
										$stmt_get_sale_count->bind_param('ii', $asset_id, $asset->creator->id);
										$stmt_get_sale_count->execute();
										$sale_count = $stmt_get_sale_count->get_result()->num_rows;
					
										$stmt_update_sale_stat = $con->prepare("UPDATE `assets` SET `asset_sales_count` = ? WHERE `asset_id` = ?");
										$stmt_update_sale_stat->bind_param('ii', $sale_count, $asset_id);
										$stmt_update_sale_stat->execute();
										return "yay";
									} else {
										return "Something went wrong at our end!";
									}
								} else {
									return "User did not have sufficient funds to perform this action!";
								}
							} else {
								return "Invalid purchase method.";
							}
						}
					} else {
						if($get_user->Owns($asset)) {
							return "You already own this asset.";
						} else if(!$asset->onsale) {
							return "Item is off-sale sorry not sorry...";
						} else {
							return "Item is off-sale and beside you already own this?";
						}
					}
					
				} else {
					return "That asset doesn't exist!";
				}
				
			} else {
				return "User is not authorised to perform this action!";
			}
		}
	}
?>