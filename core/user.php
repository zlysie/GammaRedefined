<?php

	require_once $_SERVER['DOCUMENT_ROOT']."/core/status.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/core/asset.php";

	/**
	 *  Core Profile Badges.
	 */
	enum ANORRLBadges {
		case ADMINISTRATOR;

		public function ordinal(): int {
			return match($this) {
				ANORRLBadges::ADMINISTRATOR => 1
			};
		}

		public static function index(int $badge): ANORRLBadges {
			return match($badge) {
				1 =>ANORRLBadges::ADMINISTRATOR
			};
		}
	}


	/**
	 * Data of the user.
	 */
	class User {
		public int $id;
		public string $name;
		public string $blurb;
		public string $password;
		public string $security_key;
		public DateTime $last_update;
		public DateTime $join_date;
		
		/**
		 * Attempts to grab userdata from given id.<br>
		 * Returns null if user of id was not found.
		 * @param int $id
		 * @return User|null
		 */
		public static function FromID(int $id) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `users` WHERE `user_id` = ?");
			$stmt_getuser->bind_param('i', $id);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			if($result->num_rows == 1) {
				return new self($result->fetch_assoc());
			} else {
				return null;
			}
		}

		/**
		 * Attempts to grab userdata from given security key.<br>
		 * Returns null if user of security key was not found.
		 * @param int $security
		 * @return User|null
		 */
		public static function FromSecurityKey(string $security) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `users` WHERE `user_security` = ?");
			$stmt_getuser->bind_param('s', $security);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			if($result->num_rows == 1) {
				return new self($result->fetch_assoc());
			} else {
				return null;
			}
		}

		/**
		 * Check if that user id even exists (For presence checking)
		 * @param int $id
		 * @return bool
	 	 */
		public static function Exists(int $id) {
			return self::FromID($id) != null;
		}

		function __construct($rowdata) {
			$this->id = intval($rowdata['user_id']);
			$this->name = strval($rowdata['user_name']);
			$this->blurb = str_replace("<", "&lt;", str_replace(">", "&gt;", $rowdata['user_blurb']));
			$this->last_update = DateTime::createFromFormat("Y-m-d H:i:s", $rowdata['user_lastprofileupdate']);
			$this->join_date = DateTime::createFromFormat("Y-m-d H:i:s", $rowdata['user_joindate']);
			
			$this->password = strval($rowdata['user_password']);
			$this->security_key = strval($rowdata['user_security']);
		}
		
		function GetFriends(): array {
			return [];
		}
		
		function GetFollowers(): array {
			return [];
		}
		
		function GetFollowing(): array {
			return [];
		}

		function GetFriendsCount(): int {
			return count($this->GetFriends());
		}
		
		function GetFollowersCount(): int {
			return count($this->GetFollowers());
		}

		function GetFollowingCount(): int {
			return count($this->GetFollowing());
		}

		/**
		 * Returns paged list of the user's created games
		 * @return void
		 */
		function GetOwnedGames(): array {
			return [];
		}

		function HasProfileBadgeOf(ANORRLBadges $badge): bool {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `profilebadges` WHERE `badge_id` = ? AND `badge_userid` = ?");
			$ordinal = $badge->ordinal();
			$stmt->bind_param('ii', $ordinal, $this->id);
			$stmt->execute();

			return $stmt->get_result()->num_rows != 0;
		}

		/**
		 * Returns the system badges (Homestead and the alike)
		 * @return void
		 */
		function GetProfileBadges(): array {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `profilebadges` WHERE `badge_userid` = ? ORDER BY `badge_recieved` DESC, `badge_admincorecore` DESC");
			$stmt->bind_param('i',$this->id);
			$stmt->execute();

			$result = $stmt->get_result();

			$badges = [];

			while($row = $result->fetch_assoc()) {
				array_push($badges, ANORRLBadge::FromID($row['badge_id']));
			}

			return $badges;
		}

		/**
		 * Returns badges created by the users (from games)
		 * @return void
		 */
		function GetUserBadges(): array {
			return [];
		}

		function GetLatestStatus(): Status|null {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `statuses` WHERE `status_poster` = ? ORDER BY `status_posted` DESC");
			$stmt->bind_param('i', $this->id);
			$stmt->execute();
			$result = $stmt->get_result();

			if($result->num_rows == 0) {
				return null;
			} else {
				return new Status($result->fetch_assoc());
			}
		}

		function GetAllOwnedAssetsOfTypePaged(AssetType $type, int $pagenum, int $count): array {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `transactions` WHERE `ta_assettype` = ? AND `ta_userid` = ? ORDER BY `ta_date` DESC LIMIT ?, ?");
			$page = (($pagenum-1)*$count);
			$ordinal = $type->ordinal();
			
			$stmt_getuser->bind_param('iiii', $ordinal, $this->id, $page, $count);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];


			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					$asset = Asset::FromID($row['ta_asset']);
					if($asset->status != AssetStatus::REJECTED && $asset->type == $type) {
						array_push($result_array, $asset);
					}
				}
				return $result_array;
			}

			return $result_array;
		}

		function GetAllOwnedAssetsOfType(AssetType $type): array {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `transactions` WHERE `ta_assettype` = ? AND `ta_userid` = ? ORDER BY `ta_date` DESC");
			$ordinal = $type->ordinal();
			$stmt_getuser->bind_param('ii', $ordinal, $this->id);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];
			
			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					$asset = Asset::FromID($row['ta_asset']);
					if($asset->status != AssetStatus::REJECTED) {
						array_push($result_array, $asset);
					}
				}
			}

			return $result_array;
		}

		function GetAllOwnedAssets(): array {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `transactions` WHERE `ta_userid` = ? ORDER BY `ta_date` DESC");
			$stmt_getuser->bind_param('i', $this->id);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];

			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					array_push($result_array, $row);
				}
				return $result_array;
			}

			return [];
		}

		function GetLatestAssetUploaded() {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `assets` WHERE `asset_creator` = ? ORDER BY `asset_id` DESC");
			$stmt_getuser->bind_param('i', $this->id);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];

			if($result->num_rows != 0) {
				$row = $result->fetch_assoc();
				return new Asset($row);
			} else {
				return null;
			}
		}
		
		function Follow(User|string $user) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}
			if(!$this->IsFollowing($user)) {
				$stmt_getuser = $con->prepare("INSERT INTO `follows`(`follower`, `followed`) VALUES (?, ?);");
				$stmt_getuser->bind_param('ss', $this->name, $username);
				$stmt_getuser->execute();
			} else {
				$stmt_getuser = $con->prepare("DELETE FROM `follows` WHERE `follower` = ? AND `followed` = ?;");
				$stmt_getuser->bind_param('ss', $this->name, $username);
				$stmt_getuser->execute();
			}
		}

		function Unfollow(User|string $user) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}
			if($this->IsFollowing($user)) {
				$stmt_getuser = $con->prepare("DELETE FROM `follows` WHERE `follower` = ? AND `followed` = ?;");
				$stmt_getuser->bind_param('ss', $this->name, $username);
				$stmt_getuser->execute();
			}
		}

		function IsFollowing(User|string $user): bool {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}

			$stmt_getuser = $con->prepare("SELECT * FROM `follows` WHERE `follower` LIKE ? AND `followed` LIKE ?;");
			$stmt_getuser->bind_param('ss', $this->name, $username);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			return $result->num_rows != 0;
		}

		function Friend(User|string $user) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}

			if(!$this->IsFriendsWith($user) && !$this->IsPendingFriendsReq($user) && !$this->IsIncomingFriendsReq($user)) {
				$stmt_addfriend = $con->prepare("INSERT INTO `friends`(`sender`, `reciever`) VALUES (?,?)");
				$stmt_addfriend->bind_param('ss', $this->name, $username);
				$stmt_addfriend->execute();
			} else if($this->IsIncomingFriendsReq($user)) {
				$stmt_addfriend = $con->prepare("UPDATE `friends` SET `status`= 1 WHERE `reciever` = ? AND `sender` = ?;");
				$stmt_addfriend->bind_param('ss', $this->name, $username);
				$stmt_addfriend->execute();
			} else {
				$this->Unfriend($user);
			}
		}

		function Unfriend(User|string $user) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}

			if($this->IsPendingFriendsReq($user) || $this->IsIncomingFriendsReq($user) || $this->IsFriendsWith($user)) {
				$stmt_getuser = $con->prepare("DELETE FROM `friends` WHERE (`reciever` LIKE ? AND `sender` LIKE ?)");
				$stmt_getuser->bind_param('ss', $this->name, $username);
				$stmt_getuser->execute();

				$stmt_getuser = $con->prepare("DELETE FROM `friends` WHERE (`sender` LIKE ? AND `reciever` LIKE ?)");
				$stmt_getuser->bind_param('ss', $this->name, $username);
				$stmt_getuser->execute();
			}
		}

		function IsPendingFriendsReq(User|string $user) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}

			$stmt_getuser = $con->prepare("SELECT * FROM `friends` WHERE `sender` LIKE ? AND `reciever` LIKE ? AND `status` = 0;");
			$stmt_getuser->bind_param('ss', $this->name, $username);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			return $result->num_rows != 0;
		}

		function IsIncomingFriendsReq(User|string $user) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}

			$stmt_getuser = $con->prepare("SELECT * FROM `friends` WHERE `reciever` LIKE ? AND `sender` LIKE ? AND `status` = 0;");
			$stmt_getuser->bind_param('ss', $this->name, $username);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			return $result->num_rows != 0;
		}

		function IsFriendsWith(User|string $user): bool {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$username = $user;
			if($user instanceof User) {
				$username = $user->name;
			}

			$stmt_getuser = $con->prepare("SELECT * FROM `friends` WHERE ((`reciever` LIKE ? AND `sender` LIKE ?) OR (`sender` LIKE ? AND `reciever` LIKE ?)) AND `status` = 1;");
			$stmt_getuser->bind_param('ssss', $this->name, $username, $this->name, $username);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			return $result->num_rows != 0;
		}

		function UpdateBio(string $bio): array {
			if(!$this->IsBanned()) {
				// check if user hasn't posted one in 30s

				//$offset = 3600; // windows blehh
				$offset = -3600; //prod


				$difference = (time()-($this->last_update->getTimestamp()+$this->last_update->getOffset()+$offset));

				//die(strval($difference));

				$calculated_time = 30 - $difference; 

				if($difference < 30) {
					return ["error"=> true, "reason" => "You need to wait $calculated_time seconds before updating again."];
				}

				$blockedchars = array('𒐫', '‮', '﷽', '𒈙', '⸻ ', '꧅');
				$bio_content = str_replace($blockedchars, '', trim($bio));

				if(strlen($bio_content) > 1000) {
					return ["error"=> true, "reason" => "Status was too long! (1000 characters maximum)"];
				}

				include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
				$stmt = $con->prepare('UPDATE `users` SET `user_blurb` = ?, `user_lastprofileupdate` = now() WHERE `user_id` = ?;');
				$stmt -> bind_param('si',  $bio_content, $this->id);
				$stmt -> execute();

				return ["error" => false];
			} else {
				return ["error"=> true, "reason" => "Unauthorized."];
			}
		}

		function Owns(Asset|int $asset): bool {
			$assetid = $asset;
			if($asset instanceof Asset) {
				$assetid = $asset->id;
			}
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt = $con->prepare('SELECT * FROM `transactions` WHERE `ta_userid` = ? AND `ta_asset` = ?;');
			$stmt -> bind_param('ii', $this->id, $assetid);
			$stmt -> execute();

			return $stmt->get_result()->num_rows != 0;
		}

		/**
		 * Checks if the user is admin (duh)
		 * @return void
		 */
		function IsAdmin(): bool {
			return $this->HasProfileBadgeOf(ANORRLBadges::ADMINISTRATOR);
		}

		/**
		 * Returns the ban details if the user has been suspended/terminated<br>
		 * Null if no bans have been issued.
		 * @return void
		 */
		function GetBanDetails() {}

		/**
		 * Checks if user is banned via {@see GetBanDetails}
		 * @return bool
		 */
		function IsBanned(): bool {
			return false;
		}

		/**
		 * Gives user a suspension until notice.
		 * @return void
		 */
		function Suspend(): void {}
		/**
		 * Permanent version of Suspend()
		 * @return void
		 */
		function Terminate(): void {}

		function IsOnline(): bool {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			
			$stmt_user_status_check = $con->prepare('SELECT * FROM `activity` WHERE `userid` = ? AND `action_time` > DATE_SUB(NOW(),INTERVAL 5 MINUTE)');
			$stmt_user_status_check->bind_param('i', $this->id);
			$stmt_user_status_check->execute();
			$activity_result = $stmt_user_status_check->get_result();
			
			return $activity_result->num_rows != 0;
		}

		function GetOnlineActivity(): string {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			
			$stmt_user_status_check = $con->prepare('SELECT * FROM `activity` WHERE `userid` = ? AND `action_time` > DATE_SUB(NOW(),INTERVAL 5 MINUTE)');
			$stmt_user_status_check->bind_param('i', $this->id);
			$stmt_user_status_check->execute();
			$activity_result = $stmt_user_status_check->get_result();
			
			if($activity_result->num_rows != 0) {
				return $activity_result->fetch_assoc()['action'];
			}

			return "Offline";
		}

		function GetNetLights(): int {
			return $this->GetNetAmount("lights");
		}

		function GetNetCones(): int {
			return $this->GetNetAmount("cones");
		}

		function GetNetAmount(?string $currency): int {
			$userid = $this->id;
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `transactions` WHERE (`ta_userid` = ? OR `ta_assetcreator` = ?) AND `ta_currency` LIKE ?");
			$stmt_getuser->bind_param('iis', $userid, $userid, $currency);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();
			$result_sum = 0;
			
			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					if(!$row['ta_asset']) {
						$result_sum += $row['ta_cost'];
					} else {
						if($row['ta_userid'] == $userid) {
							$result_sum -= $row['ta_cost'];
						} else {
							$result_sum += $row['ta_cost'];
						}
					}
					
				}
			}

			return $result_sum;
		}

		function PendingStipend() {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			
			$stmt_user_status_check = $con->prepare('SELECT * FROM `subscriptions` WHERE `userid` = ? AND `lastpaytime` > DATE_SUB(NOW(),INTERVAL 1 DAY)');
			$stmt_user_status_check->bind_param('i', $this->id);
			$stmt_user_status_check->execute();
			$activity_result = $stmt_user_status_check->get_result();
			return $activity_result->num_rows == 0;
		}
	}

	class ANORRLBadge {
		public ANORRLBadges $id;
		public string $name;
		public string $description;

		public static function FromID(int $id): ANORRLBadge|null {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `profilebadges_info` WHERE `pbadge_id` = ?");
			$stmt_getuser->bind_param('i', $id);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			if($result->num_rows == 1) {
				return new self($result->fetch_assoc());
			} else {
				return null;
			}
		}

		function __construct($rowdata) {
			$this->id = ANORRLBadges::index(intval($rowdata['pbadge_id']));
			$this->name = strval($rowdata['pbadge_name']);
			$this->description = strval($rowdata['pbadge_description']);
		}
	}
?>