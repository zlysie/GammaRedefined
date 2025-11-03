<?php 
if(isset($_GET['assetId']) && isset($_GET['access'])): ?>
http://arl.lambda.cam/Asset/BodyColors.ashx?clothing;http://arl.lambda.cam/asset/?id=<?= $_GET['assetId'] ?>&access=<?= $_GET['access'] ?>
<?php else: ?>
http://arl.lambda.cam/Asset/BodyColors.ashx?userId=1
<?php endif ?>