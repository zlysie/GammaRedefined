

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>ANORRL</title>
		<link rel="stylesheet" type="text/css" href="/css/AllCSS.css?t=<?= time() ?>"></link>
		<style>
			body {
				color: white;
			}

			.InGamePopup {
				height: 100vh;
				margin: 12px;
			}


			#Wrapper {
				margin: 12px;
				width: 550px;
				display:block;
				margin: auto;
				margin-top:200px;
			}

			.StandardBoxWhite {
				background: #232323;
				border: 2px solid black;
				padding: 15px;
			}

			.InGamePopup h2 {
				margin: 0;
				width: 220px;
			}
		</style>
	</head>
	<body class="InGamePopup">
		<div id="Wrapper">
			<h2>ANORRL Screenshot</h2>
			<div class="StandardBoxWhite">
				<div id="post-image-main">
					<p>Hey, you just took a screenshot in ANORRL! You could:</p>
					<ul id="post-image-ul">
						<li>Go to <a href="javascript:window.external.OpenPicFolder()">My Pictures</a> folder to check it out!</li>
						<li>Paste it to your favorite painting software</li>
					</ul>
				</div>
				<hr />
				<div id="post-image-footer"><a href="#" onclick="if ('True' == 'True') window.external.PostImage(false, 0, 0, 0); else window.external.PostImage(false, 0); window.close(); return false;">Never show this again</a></div>
			</div>
		</div>
	</body>
</html>