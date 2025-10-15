<?php 
	class Splasher {

		public static function GetRandomSplash(): string {
			$splashes = file($_SERVER["DOCUMENT_ROOT"]."/core/splashes.txt");
			return $splashes[array_rand($splashes)];
		}

		public static function GenerateSplashHeader(): void {
			$splash = self::GetRandomSplash();

			echo <<< EOT
				<div style="color: white;padding: 5px;border: 1px solid black;border-top: 0;background: orange;">
					<span style="font-size: 12px;width:100%;display:block;">$splash</span>
				</div>
			EOT;
		}

	}