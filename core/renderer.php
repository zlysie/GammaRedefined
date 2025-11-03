<?php 

	ini_set("default_socket_timeout", 15);
	ini_set("soap.wsdl_cache_enabled", 0);

	require_once $_SERVER['DOCUMENT_ROOT']."/core/rcclib.php";

	class TheFuckingRenderer {

		public static int $port = 0;
		public static string $address = "";

		public static string $domain = "";
		public static bool $cantuserenderer = false;

		private static function UpdateAndSetConfig(array $renderer_settings) {
			if(self::$domain != $renderer_settings['DOMAIN']) {
				self::$domain = $renderer_settings['DOMAIN'];
			}

			if(self::$port != intval($renderer_settings['RCCPORT'])) {
				self::$port = intval($renderer_settings['RCCPORT']);
			}

			if(self::$address != $renderer_settings['RCCIP']) {
				self::$address = $renderer_settings['RCCIP'];
			}

			if(self::$cantuserenderer != boolval($renderer_settings['DISABLED'])) {
				self::$cantuserenderer = boolval($renderer_settings['DISABLED']);
			}
		}

		public static function RenderPlayer(int $id = 0) {
			$settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../settings.env", true);
			self::UpdateAndSetConfig($settings['renderer']);

			if(self::$cantuserenderer) {
				echo "renderer was disabled?";
				return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavailable.jpg"));
			}
			$access = $settings['asset']['ACCESSKEY'];
			try {
				$rcc = new Roblox\Grid\Rcc\RCCServiceSoap(self::$address, self::$port);

				$domain = self::$domain;

				$JobId = md5(rand());

				$job = new Roblox\Grid\Rcc\Job($JobId);
				$scriptText = <<<EOT
				local player = game.Players:CreateLocalPlayer(0)

				player.CharacterAppearance = "http://$domain/asset/?id=$id&access=$access"
				player:LoadCharacter()

				return (game:GetService("ThumbnailGenerator"):Click("PNG", 420, 420, true))
				EOT;

				$script = new Roblox\Grid\Rcc\ScriptExecution($JobId."-Script", $scriptText);
				$base64data = $rcc->OpenJob($job, $script);
				$rcc->RenewLease($JobId, 1);
			} catch(SoapFault $e) {
				echo "some fault happened ig";
				$base64data = base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavailable.jpg"));
			}

			return $base64data;
		}

		public static function RenderMesh(int $id = 0) {
			$settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../settings.env", true);
			self::UpdateAndSetConfig($settings['renderer']);
			
			if(self::$cantuserenderer) {
				return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavailable.jpg"));
			}

			try {
				$rcc = new Roblox\Grid\Rcc\RCCServiceSoap(self::$address, self::$port);

				$domain = self::$domain;

				$JobId = md5(rand());
				
				$access = $settings['asset']['ACCESSKEY'];

				$job = new Roblox\Grid\Rcc\Job($JobId);
				$scriptText = <<<EOT
				game:GetService("ContentProvider"):SetBaseUrl("http://$domain/")
				game:GetService("ScriptContext").ScriptsDisabled = true
				game:GetService("Lighting").Outlines = false

				local part = Instance.new("Part", workspace)
				part.Size = Vector3.new(4,4,4)

				Instance.new("SpecialMesh", part).MeshId = "http://$domain/asset/?id=$id&access=$access"
				
				return (game:GetService("ThumbnailGenerator"):Click("PNG", 420, 420, true))
				EOT;

				$script = new Roblox\Grid\Rcc\ScriptExecution($JobId."-Script", $scriptText);
				$base64data = $rcc->OpenJob($job, $script);
				$rcc->RenewLease($JobId, 1);
			} catch(SoapFault $e) {
				$base64data = base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavailable.jpg"));
			}

			return $base64data;
		}

		public static function RenderPlace(int $id = 0) {
			$settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../settings.env", true);
			self::UpdateAndSetConfig($settings['renderer']);

			if(self::$cantuserenderer) {
				echo "renderer is disabled?";
				return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/unavail-250x250.png"));
			}

			$domain = self::$domain;

			$JobId = md5(rand());

			$access = $settings['asset']['ACCESSKEY'];

			$time = time();

			$rcc = new RCCServiceSoap(self::$address, self::$port, 'roblox.com', true);
			
			return $rcc->execScript(
				<<<EOT
				game:Load("http://$domain/asset/?id=$id&access=$access&time=$time")
				local render = game:GetService("ThumbnailGenerator"):Click("PNG", 420, 230, false)
				return (render)
				EOT,
				$JobId,
				5
			);
		}

	}

	
	/*$value = TheFuckingRenderer::RenderPlayer();
	echo "<img src='data:image/png;base64,$value'>";*/
?>