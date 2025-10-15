<?php if(isset($_GET['clothing'])): ?>
<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.novarin.co/roblox.xsd" version="4">
	<External>null</External>
	<External>nil</External>
	<Item class="BodyColors" referent="RBX96B37B6C58984541BA7545B230B6E10D">
		<Properties>
			<int name="HeadColor">194</int>
			<int name="LeftArmColor">194</int>
			<int name="LeftLegColor">194</int>
			<string name="Name">Body Colors</string>
			<int name="RightArmColor">194</int>
			<int name="RightLegColor">194</int>
			<int name="TorsoColor">194</int>
		</Properties>
	</Item>
</roblox>
<?php else:
	
	// grab body colours of character
?>
<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://rbx.lambda.cam/roblox.xsd" version="4">
	<External>null</External>
	<External>nil</External>
	<Item class="BodyColors" referent="RBXCCC36C132C584B37B29DB69EAE48292A">
		<Properties>
			<int name="HeadColor">24</int>
			<int name="LeftArmColor">24</int>
			<int name="LeftLegColor">119</int>
			<string name="Name">Body Colors</string>
			<int name="RightArmColor">24</int>
			<int name="RightLegColor">119</int>
			<int name="TorsoColor">23</int>
		</Properties>
	</Item>
</roblox>
<?php endif ?>