<?php

	require_once $_SERVER['DOCUMENT_ROOT'].'/core/user.php';

	enum AssetStatus {
		case REJECTED;
		case PENDING;
		case ACCEPTED;

		public static function index(?int $ordinal): AssetStatus {
			return match($ordinal) {
				-1 => AssetStatus::REJECTED, 
				 0 => AssetStatus::PENDING, 
				 1 => AssetStatus::ACCEPTED, 
			};
		}

		public function ordinal(): int {
			return match($this) {
				AssetStatus::REJECTED => -1, 
				AssetStatus::PENDING  =>  0, 
				AssetStatus::ACCEPTED =>  1, 
			};
		}
	}

	enum AssetType {
		case IMAGE;
		case TSHIRT;
		case AUDIO;
		case MESH;
		case LUA;
		case HAT;
		case PLACE;
		case MODEL;
		case SHIRT;
		case PANTS;
		case DECAL;
		public static function index(?int $ordinal): AssetType {
			return match($ordinal) {
				1 => AssetType::IMAGE,
				2 => AssetType::TSHIRT,
				3 => AssetType::AUDIO,
				4 => AssetType::MESH,
				5 => AssetType::LUA,
				8 => AssetType::HAT,
				9 => AssetType::PLACE,
				10 => AssetType::MODEL,
				11 => AssetType::SHIRT,
				12 => AssetType::PANTS,
				13 => AssetType::DECAL,
			};
		}

		public function ordinal(): int {
			return match($this) {
				AssetType::IMAGE 	=> 1,
				AssetType::TSHIRT 	=> 2,
				AssetType::AUDIO	=> 3,
				AssetType::MESH 	=> 4,
				AssetType::LUA 		=> 5,
				AssetType::HAT 		=> 8,
				AssetType::PLACE	=> 9,
				AssetType::MODEL 	=> 10,
				AssetType::SHIRT 	=> 11,
				AssetType::PANTS 	=> 12,
				AssetType::DECAL 	=> 13,
			};
		}

		public function label(): string {
			return match($this) {
				AssetType::IMAGE 	=> "Image",
				AssetType::TSHIRT 	=> "T-Shirt",
				AssetType::AUDIO	=> "Audio",
				AssetType::MESH 	=> "Mesh",
				AssetType::LUA 		=> "Script",
				AssetType::HAT 		=> "Hat",
				AssetType::PLACE	=> "Place",
				AssetType::MODEL 	=> "Model",
				AssetType::SHIRT 	=> "Shirt",
				AssetType::PANTS 	=> "Pants",
				AssetType::DECAL 	=> "Decal",
			};
		}
	}

	enum Currency {
		case CONES;
		case LIGHTS;
	}

	/**
	 * Abstract class for assets
	*/
	class Asset {
		public int         $id;
		public User        $creator;
		public AssetType   $type;
		public string      $name;
		public string      $description;
		/** friends-only in places */
		public bool        $public;
		public AssetStatus $status;

		public int         $favourites_count;
		public bool        $comments_enabled;

		public bool        $onsale;
		/** Robux */
		public int         $cost;
		public int         $sales_count;

		public Asset|null $relatedasset;
		public bool         $notcatalogueable;
		public int $current_version;
		

		public DateTime    $last_updatetime;
		public DateTime    $created_at;

		public static function FromID(int $id): Asset|null {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `assets` WHERE `asset_id` = ?");
			$stmt_getuser->bind_param('i', $id);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			if($result->num_rows == 1) {
				return new self($result->fetch_assoc());
			} else {
				return null;
			}
		}

		public static function GetAllUncheckedAssets(): array|null {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getallusers = $con->prepare("SELECT * FROM `assets` WHERE `asset_status` = ? AND `asset_nevershow` = 0");
			$ordinal = AssetStatus::PENDING->ordinal();
			$stmt_getallusers->bind_param("i", $ordinal);
			$stmt_getallusers->execute();
			$result = $stmt_getallusers->get_result();
			$result_array = array();

			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					if(User::FromID($row['asset_creator']) != null) {
						if($row['asset_type'] == AssetType::PLACE->ordinal()) {
							$asset = Place::FromID($row['asset_id']);
						} else {
							$asset = new Asset($row);
						}

						array_push($result_array, $asset);

						if(!$asset->notcatalogueable && $asset->status != AssetStatus::REJECTED && $asset->public) {
							
						}
					}
				}
				return $result_array;
			}
			return [];
		}


		public static function GetAssetsOfTypePaged(string $query, AssetType $type, int $pagenum, int $count, bool $allowunchecked = true) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `assets` WHERE `asset_name` LIKE ? AND `asset_type` = ? ORDER BY `asset_lastedited` LIMIT ?, ?");
			
			$page = (($pagenum-1)*$count);
			$q = "%$query%";
			$ordinal = $type->ordinal();
			$stmt_getuser->bind_param('siii', $q, $ordinal, $page, $count);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];

			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					$asset = new Asset($row);
					if($row['asset_type'] == AssetType::PLACE->ordinal()) {
						$asset = Place::FromID($row['asset_id']);
					} else {
						$asset = new Asset($row);
					}

					if(!$asset->notcatalogueable && $asset->status != AssetStatus::REJECTED && $asset->public) {
						if(!$allowunchecked && $asset->status == AssetStatus::ACCEPTED) {
							array_push($result_array, $asset);
						} else {
							if(!$allowunchecked && $asset->status == AssetStatus::PENDING) {} 
							else {
								array_push($result_array, $asset);
							}
						}
					}
					
				}
				return $result_array;
			}

			return [];
		}

		public static function GetAssetsOfType(string $query, AssetType $type, bool $allowunchecked = true) {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `assets` WHERE `asset_name` LIKE ? AND `asset_type` = ?");
			
			$q = "%$query%";
			$ordinal = $type->ordinal();
			$stmt_getuser->bind_param('si', $q, $ordinal);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];

			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					if($row['asset_type'] == AssetType::PLACE->ordinal()) {
						$asset = Place::FromID($row['asset_id']);
					} else {
						$asset = new Asset($row);
					}

					if(!$asset->notcatalogueable && $asset->status != AssetStatus::REJECTED && $asset->public) {
						if(!$allowunchecked && $asset->status == AssetStatus::ACCEPTED) {
							array_push($result_array, $asset);
						} else {
							if(!$allowunchecked && $asset->status == AssetStatus::PENDING) {} 
							else {
								array_push($result_array, $asset);
							}
						}
						
					}
				}
				return $result_array;
			}

			return [];
		}

		function __construct(array|int $rowdata) {
			if(is_array($rowdata)) {
				$this->id = intval($rowdata['asset_id']);
				$this->creator = User::FromID($rowdata['asset_creator']);
				$this->type = AssetType::index(intval($rowdata['asset_type'])); // temp
				$this->name = str_replace("<", "&lt;", str_replace(">", "&gt;", $rowdata['asset_name']));
				$this->description = str_replace("<", "&lt;", str_replace(">", "&gt;", $rowdata['asset_description']));
				$this->public = boolval($rowdata['asset_public']);
				$this->status = AssetStatus::index(intval($rowdata['asset_status']));
	
				$this->favourites_count = intval( $rowdata['asset_favourites_count']);
				$this->comments_enabled = boolval($rowdata['asset_comments_enabled']);
	
				$this->onsale = boolval($rowdata['asset_onsale']);
				$this->cost =  intval($rowdata['asset_cost']);
				$this->sales_count = intval($rowdata['asset_sales_count']);

				$this->notcatalogueable = boolval($rowdata['asset_nevershow']);
				$this->relatedasset = Asset::FromID(intval($rowdata['asset_relatedid']));
				$this->current_version = intval($rowdata['asset_currentversion']);
	
				$this->last_updatetime = DateTime::createFromFormat("Y-m-d H:i:s", $rowdata['asset_lastedited']);
				$this->created_at      = DateTime::createFromFormat("Y-m-d H:i:s", $rowdata['asset_created']);	
			} else {
				// for extended classes
				$asset_data = Asset::FromID($rowdata);
				
				$this->id = $asset_data->id;
				$this->creator = $asset_data->creator;
				$this->type = $asset_data->type;
				$this->name = $asset_data->name;
				$this->description = $asset_data->description;
				$this->public = $asset_data->public;
				$this->status = $asset_data->status;
	
				$this->favourites_count = $asset_data->favourites_count;
				$this->comments_enabled = $asset_data->comments_enabled;
	
				$this->onsale = $asset_data->onsale;
				$this->cost = $asset_data->cost;
				$this->sales_count = $asset_data->sales_count;
				
				$this->notcatalogueable = $asset_data->notcatalogueable;
				$this->relatedasset = $asset_data->relatedasset;
				$this->current_version = $asset_data->current_version;

				$this->last_updatetime = $asset_data->last_updatetime;
				$this->created_at      = $asset_data->created_at;	
			}
		}

		function GetAllVersions(): array {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `assetversions` WHERE `version_assetid` = ? ORDER BY `version_id` DESC");
			$stmt_getuser->bind_param('i', $this->id);
			$stmt_getuser->execute();

			$result = $stmt_getuser->get_result();

			$result_array = [];

			if($result->num_rows != 0) {
				while($row = $result->fetch_assoc()) {
					array_push($result_array, new AssetVersion($row));
				}
			}

			return $result_array;
		}

		function GetLatestVersionDetails(): AssetVersion|null {
			return AssetVersion::GetLatestVersionOf($this);
		}

		function GetVersionID(): int {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `assetversions` WHERE `version_assetid` = ? ORDER BY `version_id`");
			$stmt->bind_param("i", $this->id);
			$stmt->execute();

			$result = $stmt->get_result();
			$row = $result->fetch_assoc();
			return $row["version_id"];
		}

		function GetMD5HashCurrent(): string {
			return $this->GetMD5Hash($this->GetVersionID());
		}

		function GetMD5Hash(int $version): string {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `assetversions` WHERE `version_id` = ?");
			$stmt->bind_param("i", $version);
			$stmt->execute();

			$result = $stmt->get_result();
			$row = $result->fetch_assoc();
			return $row["version_md5sig"];
		}

		function Comment(User|int $user, string $content) {}
		function GetAllComments() {}
		function GetComments(int $page, int $rows) {}

		function Favourite(User|int $user) {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";

			$userid = $user;
			if($user instanceof User) {
				$userid = $user->id;
			}

			if(!$this->HasUserFavourited($user)) {
				$stmt = $con->prepare("INSERT INTO `favourites`(`fav_assetid`, `fav_userid`, `fav_assettype`) VALUES (?, ?, ?);");
				$type = $this->type->ordinal();
				$stmt->bind_param("iii", $this->id, $userid, $type);
				$stmt->execute();

				$this->UpdateFavouritesCount();
			}
		}

		private function UpdateFavouritesCount() {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `favourites` WHERE `fav_assetid` = ?;");
			$stmt->bind_param("i", $this->id);
			$stmt->execute();

			$favcount = $stmt->get_result()->num_rows;

			$stmt = $con->prepare("UPDATE `assets` SET `asset_favourites_count` = ? WHERE `asset_id` = ?");
			$stmt->bind_param("ii", $favcount, $this->id);
			$stmt->execute();
		}

		function Unfavourite(User|int $user) {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";

			$userid = $user;
			if($user instanceof User) {
				$userid = $user->id;
			}

			if($this->HasUserFavourited($user)) {
				$stmt = $con->prepare("DELETE FROM `favourites` WHERE `fav_assetid` = ? AND `fav_userid` = ?;");
				$stmt->bind_param("ii", $this->id, $userid);
				$stmt->execute();

				$this->UpdateFavouritesCount();
			}
		}

		function HasUserFavourited(User|int $user) {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";

			$userid = $user;
			if($user instanceof User) {
				$userid = $user->id;
			}

			$stmt = $con->prepare("SELECT * FROM `favourites` WHERE `fav_assetid` = ? AND `fav_userid` = ?;");
			$stmt->bind_param("ii", $this->id, $userid);
			$stmt->execute();

			return $stmt->get_result()->num_rows != 0;
		}

		function Buy(User|int $user, Currency $mode) {

		}

		function UpdateSalesCount() {
			include $_SERVER['DOCUMENT_ROOT']."/core/connection.php";
			$stmt = $con->prepare("SELECT * FROM `transactions` WHERE `ta_userid` != `ta_assetcreator` AND `ta_asset` = ?;");
			$stmt->bind_param("i", $this->id);
			$stmt->execute();

			$salescount = $stmt->get_result()->num_rows;

			$stmt = $con->prepare("UPDATE `assets` SET `asset_sales_count` = ? WHERE `asset_id` = ?");
			$stmt->bind_param("ii", $salescount, $this->id);
			$stmt->execute();
		}
	}


	enum Genre {
		/*
			Town and City
			Fantasy
			Sci-Fi
			Ninja
			Scary
			Pirate
			Adventure
			Sports
			Funny
			Wild West
			War
			Skate Park
			Tutorial 
		*/

		case ALL;
		case TOWNANDCITY;
		case FANTASY;
		case SCIFI;
		case NINJA;
		case SCARY;
		case PIRATE;
		case ADVENTURE;
		case SPORTS;
		case FUNNY;
		case WILDWEST;
		case WAR;
		case SKATEPARK;
		case TUTORIAL;

		public static function index(?int $ordinal): Genre {
			return match($ordinal) {
				1 => Genre::ALL,
				7 => Genre::TOWNANDCITY,
				8 => Genre::FANTASY,
				9 => Genre::SCIFI,
				10 => Genre::NINJA,
				11 => Genre::SCARY,
				12 => Genre::PIRATE,
				13 => Genre::ADVENTURE,
				14 => Genre::SPORTS,
				15 => Genre::FUNNY,
				16 => Genre::WILDWEST,
				17 => Genre::WAR,
				18 => Genre::SKATEPARK,
				19 => Genre::TUTORIAL,
			};
		}

		public function ordinal(): int {
			return match($this) {
				Genre::ALL => 1,
				Genre::TOWNANDCITY => 7,
				Genre::FANTASY => 8,
				Genre::SCIFI => 9,
				Genre::NINJA => 10,
				Genre::SCARY => 11,
				Genre::PIRATE => 12,
				Genre::ADVENTURE => 13,
				Genre::SPORTS => 14,
				Genre::FUNNY => 15,
				Genre::WILDWEST => 16,
				Genre::WAR => 17,
				Genre::SKATEPARK => 18,
				Genre::TUTORIAL => 19,
			};
		}

		public function label(): string {
			return match($this) {
				Genre::ALL => "All",
				Genre::TOWNANDCITY => "Town and City",
				Genre::FANTASY => "Fantasy",
				Genre::SCIFI => "Sci-Fi",
				Genre::NINJA => "Ninja",
				Genre::SCARY => "Scary",
				Genre::PIRATE => "Pirate",
				Genre::ADVENTURE => "Adventure",
				Genre::SPORTS => "Sports",
				Genre::FUNNY => "Funny",
				Genre::WILDWEST => "Wild West",
				Genre::WAR => "War",
				Genre::SKATEPARK => "Skate Park",
				Genre::TUTORIAL => "Tutorial",
			};
		}
	}

	class Place extends Asset {
		/** is the same as Asset::public */
		public bool $friends_only;
		public bool $copylocked;
		public Genre  $genre;
		public int $server_size;
		public int  $visit_count;
		public int  $current_playing_count;

		public static function FromID(int $id): Place|null {
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `asset_places` WHERE `place_id` = ?");
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
			parent::__construct(intval($rowdata['place_id']));

			$this->friends_only = $this->public;
			$this->copylocked = boolval($rowdata['place_copylocked']);
			$this->genre = Genre::index(intval($rowdata['place_genre']));
			$this->allowed_geartypes = null;
			$this->server_size = intval($rowdata['place_serversize']);
			$this->visit_count = intval($rowdata['place_visit_count']);
			$this->current_playing_count = intval($rowdata['place_currently_playing']);
		}

		function Visit(User|int $user) {}
		function GetBadges() {}
		function GetGamepasses() {}
	}
	class AssetVersion {

		public int $id;
		public Asset $asset;
		public string $md5sig;
		public string $md5thumb;
		public AssetType $asset_type;
		public DateTime $publish_date;

		public static function GetLatestVersionOf(Asset|int $asset) {
			if($asset instanceof Asset) {
				return self::GetVersionOf($asset, $asset->current_version);
			} else {
				$asset = Asset::FromID($asset);
				return self::GetVersionOf($asset, $asset->current_version);
			}
		}

		public static function GetVersionOf(Asset|int $asset, int $version) {
			$id = $asset;
			if($asset instanceof Asset) {
				$id = $asset->id;
			}
			include $_SERVER["DOCUMENT_ROOT"]."/core/connection.php";
			$stmt_getuser = $con->prepare("SELECT * FROM `assetversions` WHERE `version_assetid` = ? AND `version_subid` = ?");
			$stmt_getuser->bind_param('ii', $id, $version);
			$stmt_getuser->execute();
			$result = $stmt_getuser->get_result();

			if($result->num_rows == 1) {
				return new self($result->fetch_assoc());
			} else {
				return null;
			}
		}


		function __construct($rowdata) {
			$this->id = intval($rowdata['version_id']);
			$this->asset = Asset::FromID(intval($rowdata['version_assetid']));
			$this->asset_type = AssetType::index(intval($rowdata['version_assettype']));
			$this->md5sig = strval($rowdata['version_md5sig']);
			$this->md5thumb = strval($rowdata['version_md5thumb']);

			$this->publish_date      = DateTime::createFromFormat("Y-m-d H:i:s", $rowdata['version_publishdate']);	
		}

	}
?>