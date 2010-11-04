<?php if($metadata): ?>
<div id="sc2rep-wrapper" class="sc2rep">
<div id="sc2rep-game-info">
<h2>Game Info: </h2>
<table style="width: 45%; min-width: 500px; float: left; margin-right: 20px;">
	<tr>
		<th>Game Type</th>
		<th>Map Name</th>
		<th>Map Image</th>
		<th>Length</th>
	</tr>
	<tr>
		<td><?php echo($metadata['meta']['team_size']); ?></td>
		<td><?php echo($metadata['map']['name']); ?></td>
		<td><img src="<?php echo($minimap_url); ?>" alt="<?php echo($metadata['map']['name']); ?>" style="width: 100px;" /></td>
		<td><?php echo($metadata['meta']['formatted_length']); ?></td>
	</tr>
</table>
</div>
<div id="sc2rep-players">
<table style="width: 45%; min-width: 500px;">
	<tr>
		<th>Player</th>
		<th>Profile</th>
		<th>Race</th>
		<th>APM</th>
	</tr>
<?php foreach($metadata['players'] as $player): ?>
	<tr>
		<td>
			<span style="color: #<?php echo($player['color']); ?>; font-weight: bold; margin-left: 10px;"><?php echo($player['player_name']); ?></span>
		</td>
		<td>
			<a href="<?php echo($player['sc2ranks_profile_url']); ?>"><img src="<?php echo($sc2ranks_icon_url); ?>" alt="sc2ranks profile" /></a>
			<a href="<?php echo($player['bnet_profile_url']); ?>"><img src="<?php echo($bnet_icon_url); ?>" alt="bnet profile" /></a>
		</td>
		<td><img src="<?php echo($player['race_image_url']); ?>" alt="<?php echo($player['race']); ?>" /></td>
		<td><?php echo($player['apm']); ?></td>
	</tr>
<?php endforeach; ?>
</table>
</div>
</div>
<?php else: ?>
Replay Information Not Available
<?php endif; ?>
