<h2> Metadata: </h2>
<?php if($metadata): ?>
<div id="sc2rep-wrapper" class="sc2rep">
	Game Length: <?php echo($metadata['meta']['length']); ?>
</div>
<?php else: ?>
Replay Information Not Available
<?php endif; ?>
