<?php
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>

<?php
$MAX_FILE_SIZE = 4000000;
?>
<html>
<head>
<style type="text/css">
table.events {
	float: left;
}
table.events td {
	border: solid #000 1px;
}
table.events th {
	border: solid #000 1px;
}
div.events {
	float: left;
}

</style>
<script language="JavaScript">
<!--
function toggleVisible(id) {
   var a = document.getElementById?document.getElementById(id):document.all[id];
   if (a) {
	 if (a.style.display == 'block') a.style.display = 'none';
	 else a.style.display = 'block';
   }
   return false;
}
//-->
</script>
</head>
<body>
<p><b>NOTE: this test page can only parse replays from SC2 beta phase 2</b><br />
Expect gazillion error messages if you try an older replay file.</p>
<p><b>NOTE 2: Computer opponents' events are not recorded in replays (Meaning no apm or build orders of computer opponents)</b></p>
<p>The source code and other documentation is available at <a href="https://code.google.com/p/phpsc2replay/" target=_blank>https://code.google.com/p/phpsc2replay/</a></p>
<form enctype="multipart/form-data" action="upload_file.php" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_FILE_SIZE;?>" />
    Choose file to upload: <input name="userfile" type="file" /><br />
	<label for="debug">Debug?</label><input type="checkbox" name="debug" value="1" /><br />
	<label for="test">Test stuff?</label><input type="checkbox" name="test" value="1" /><br />
	<br /><br />
    <input type="submit" value="Upload File" />
</form>
<?php
function createAPMImage($vals, $length, $fn) {
	$width = 300;
	$height = 200;
	$pixelsPerSecond = $width/ $length;
	$pic = imagecreatetruecolor($width,$height);
	$lineColor = imagecolorallocate($pic,0,0,0);
	$lineColorGrey = imagecolorallocate($pic,192,192,192);
	$bgColor = imagecolorallocate($pic,255,255,255);
	$bgColorT = imagecolorallocatealpha($pic,255,255,255,127);
	imagefill($pic,0,0,$bgColorT);
	// first create x/y pairs
	// do this by adding up the actions of the 60 seconds before the pixel
	// if there are less than 60 seconds, extrapolate by multiplying with 60/$secs
	// the time index corresponding to a pixel can be calculated using the $pixelsPerSecond variable,
	// it should always be 0 < $pixelsPerSecond < 1
	$xypair = array();
	$maxapm = 0;
	for ($x = 1;$x <= $width;$x++) {
		$secs = ceil($x / $pixelsPerSecond);
		$apm = 0;
		if ($secs < 60) {
			for ($tmp = 0;$tmp < $secs;$tmp++)
				if (isset($vals[$tmp]))
					$apm += $vals[$tmp];
			$apm = $apm / $secs * 60;
		} else {
			for ($tmp = $secs - 60;$tmp < $secs;$tmp++)
				if (isset($vals[$tmp]))
					$apm += $vals[$tmp];
			$apm = $apm;
		}
		if ($apm > $maxapm)
			$maxapm = $apm;
		$xypair[$x] = $apm;

	}

	// draw the pixels
	if ($maxapm <= 0)
		return;
	for ($i = 2;$i <= $width;$i++) {
		imageline($pic,$i - 1,$height - $xypair[$i - 1] / $maxapm * $height, $i,$height - $xypair[$i] / $maxapm * $height,$lineColor);
	}
	// build a seperate container image 
	$frame = imagecreatetruecolor($width +50,$height+50);
	imagefill($frame,0,0,$bgColor);
	
	imagerectangle($frame,40,0,$width + 40,$height,$lineColor);
	imageline($frame,40,$height / 2,$width + 40,$height / 2, $lineColorGrey);


	imagestringup($frame,4,5,$height - 15,"APM -->",$lineColor);
	imagestring($frame,4,55,$height + 20,"Time (minutes)",$lineColor);
	imagestring($frame,2,25,$height - 15,"0",$lineColor);
	imagestring($frame,2,20,($height / 2),floor($maxapm / 2),$lineColor);
	imagestring($frame,2,20,0,floor($maxapm),$lineColor);
	$lengthMins = ($length / 60);
	for ($i = 0;$i < $lengthMins;$i+=5) {
		imagestring($frame,2,40+($width / ($lengthMins / 5) * ($i / 5)),$height + 5,$i,$lineColor);
		if ($i > 0)
			imageline($frame,40+($width / ($lengthMins / 5) * ($i / 5)),0,40+($width / ($lengthMins / 5) * ($i / 5)),$height, $lineColorGrey);		
	}
	// copy the graph onto the container image and save it
	imagecopy($frame,$pic,40,0,0,0,$width,$height);
	imagepng($frame,$fn);
	imagedestroy($frame);
	imagedestroy($pic);
}



if (isset($_FILES['userfile'])) {
	$error = $_FILES['userfile']['error'];
	$type = $_FILES['userfile']['type'];
	$name = $_FILES['userfile']['name'];
	$tmpname = $_FILES['userfile']['tmp_name'];
	$size = $_FILES['userfile']['size'];
	$err = false;
	if ($size >= $MAX_FILE_SIZE) {
		echo "Error: The uploaded file was too large. The maximum size is ".$MAX_FILE_SIZE." bytes.<br />";
		$err = true;
	}
	if ($error == UPLOAD_ERR_PARTIAL) {
		echo "Error: The upload was not completed successfully. Please try again.<br />";
		$err = true;
	}
	if ($error == UPLOAD_ERR_NO_FILE) {
		echo "Error: No file was selected for uploading.<br />";
		$err = true;
	}
	if (!is_uploaded_file($tmpname)) {
		echo "Error: Uploaded filename doesn't point to an uploaded file.<br />";
		$err = true;
	}
	if ($err !== true) {
		if ($_POST['debug'] == 1) {
			error_reporting(-1);
		}
		if (class_exists("MPQFile") || (include 'mpqfile.php')) {
			$start = microtime_float();
			$parseDurationString = "";
			$debug = 0;
			if ($_POST['debug'] == 1 || $_POST['test'] == 1) {
				echo sprintf("<b>Debugging is on.</b><br />\n");
				$debug = 2;
			}
			$a = new MPQFile($tmpname,true,$debug);
			$init = $a->getState();
			
			if (isset($_POST['test']) && $_POST['test'] == 1) {
				if (class_exists("SC2Replay") || (include 'sc2replay.php')) {
					
					//$bool = $a->insertChatLogMessage("testing testing", "testguy", 1);
					//$bool = $a->insertChatLogMessage("testing 2", 1, 5);

					//$a->saveAs("testfile.SC2Replay", true);
					//$a = new MPQFile("testfile.SC2Replay", true, 2);
					$byte = 0;
					$b = new SC2Replay($a);		
					$b->setDebug(true);
					$tmp = $b->parseDetailsValue($a->readFile("replay.details"),$byte);
					echo "<pre>";
					var_dump($tmp);
					echo "</pre>";
					die();
				}
			}
			if ($init == MPQ_ERR_NOTMPQFILE)
				echo "Error parsing uploaded file, make sure it is a valid MPQ archive!<br />\n";
			else if ($a->getFileType() == MPQ_SC2REPLAYFILE) {
				echo sprintf("Version: %s<br />\n",$a->getVersionString());
				$b = $a->parseReplay();
				$parseDurationString .= sprintf("Parsed replay in %d ms.<br />\n",((microtime_float() - $start)*1000));
				$players = $b->getPlayers();
				$recorder = $b->getRecorder();
				echo sprintf("Map name: %s, Game length: %s<br />\n",$b->getMapName(),$b->getFormattedGameLength());
				echo sprintf("Team size: %s, Game speed: %s<br />\n",$b->getTeamSize(), $b->getGameSpeedText());
				echo sprintf("Realm: %s<br />\n",$b->getRealm());
				echo sprintf("Date and time played: %s<br />\n",date('jS \of F Y \a\t H:i' ,$b->getCtime()));
				if ($recorder != null)
					echo sprintf("Replay recorded by: %s (EXPERIMENTAL!)<br />\n",$recorder['name']);
				$apmString = "<b>APM graphs</b><br />\n";
				$obsString = "";
				$obsCount = 0;
				echo "<table border=\"1\"><tr><th>Player name</th><th>Race</th><th>Color</th><th>Team</th><th>Average APM<br />(experimental)</th><th>Winner?</th></tr>\n";
				foreach($players as $value) {
					if ($value['isObs']) {
						if ($obsString == "")
							$obsString = $value['name'];
						else
							$obsString .= ', '.$value['name'];
						$obsCount++;
						continue;
					}
					if ($b->isWinnerKnown())
						$wincolor = (isset($value['won']) && $value['won'] == 1)?0x00FF00:0xFF0000;
					else
						$wincolor = 0xFFFFFF;
					if ($value['isComp'])
						$difficultyString = sprintf(" (%s)",SC2Replay::$difficultyLevels[$value['difficulty']]);
					else
						$difficultyString = "";
					echo sprintf("<tr><td>%s</td><td>%s</td><td><font color=\"#%s\">%s</font></td><td>%s</td><td style=\"text-align: center\">%d</td><td style=\"background-color: #%06X; text-align: center\">%d</td></tr>\n",
									$value['name'].$difficultyString,
									$value['race'],
									$value['color'],
									$value['sColor'],
									($value['team'] > 0)?"Team ".$value['team']:"-",
									($value['team'] > 0)?(round($value['apmtotal'] / ($b->getGameLength() / 60))):0,
									((isset($value['won']))?$wincolor:0xFFFFFF),
									(isset($value['won']))?$value['won']:(($value['team'] > 0)?"Unknown":"-")
								);
					if (!$value['isObs'] && $value['ptype'] != 'Comp') {
						$apmFileName = $value['id']."_".md5($name).".png";
						createAPMImage($value['apm'],$b->getGameLength(),$apmFileName);
						$apmString .= sprintf("%s:<br /><img src=\"$apmFileName\" /><br />\n",$value['name']);
					}
				}
				echo "</table><br />";
				if ($obsCount > 0) {
					echo "Observers ($obsCount): $obsString<br />\n";
				}
				$messages = $b->getMessages();
				if (count($messages) > 0) {
					echo "<b>Messages:</b><br /><table border=\"1\"><tr><th>Time</th><th>Player</th><th>Target</th><th>Message</th></tr>\n";
					foreach ($messages as $val)
						echo sprintf("<tr><td>%d sec</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",$val['time'],
									  $val['name'], ($val['target'] == 2)?"Alliance":"All",$val['message']);
					echo "</table><br />\n";
				}
				echo $apmString;



				
				$t = $b->getEvents();
				if (class_exists('SC2ReplayUtils')) {
				?>
				<div>
				<span><b>Click on the following links to show/hide events</b></span><br />
				<span><a href="#" onClick="return toggleVisible('allevents');">All events</a></span>
				<span><a href="#" onClick="return toggleVisible('buildingevents');">Building events</a></span>
				<span><a href="#" onClick="return toggleVisible('unitevents');">Unit events</a></span>
				<span><a href="#" onClick="return toggleVisible('upgradeevents');">Upgrade events</a></span>
				</div>
				<div>		
				<?php
					//create table of all events
					echo "<div id=\"allevents\" style=\"display: block\" class=\"events\"><h2>All events:</h2><table class=\"events\"><tr><th>Timecode</th>\n";
					$pNum = count($players);
					foreach ($players as $value) {
					  if (!$value['isObs'] && $value['ptype'] != 'Comp')
						echo sprintf("<th>%s (%s)</th>",$value['name'],$value['race']);
					}
					echo "</tr>\n";
					if (count($t) > 0)
						foreach ($t as $value) {
							$eventarray = $b->getAbilityArray($value['a']);
							// setting rally points or issuing move/attack move or other commands does not tell anything
							if ($eventarray['type'] == SC2_TYPEGEN && !isset($_POST['debug'])) continue;
							echo sprintf("<tr><td>%d sec</td>",$value['t'] / 16);
							foreach ($players as $value2) {
								if ($value2['isObs'] || $value2['ptype'] == 'Comp') continue;
								if ($value['p'] == $value2['id'])
									echo sprintf("<td>%s%s</td>",$eventarray['desc'],(isset($_POST['debug']))?sprintf(" (%06X)",$value['a']):"");
								else
									echo "<td>&nbsp;</td>";
							}
							echo "</tr>\n";
						}
					echo "</table></div>";
					$buildingDiv = "<div id=\"buildingevents\" style=\"display: none\" class=\"events\"><h2>Buildings:</h2>";
					$unitDiv = "<div id=\"unitevents\" style=\"display: none\" class=\"events\"><h2>Units:</h2>";
					$upgradeDiv = "<div id=\"upgradeevents\" style=\"display: none\" class=\"events\"><h2>Upgrades:</h2>";
					// create ability breakdown tables
					foreach ($players as $value) {
						if ($value['isComp'] || $value['isObs']) continue;
						$buildingTable = sprintf("<table class=\"events\"><tr><th><font color=\"#%s\">%s</font></th><th>First seen</th><th>Total</th></tr>\n",
									  $value['color'],
									  $value['name']);
						$unitTable = sprintf("<table class=\"events\"><tr><th><font color=\"#%s\">%s</font></th><th>First seen</th><th>Total</th></tr>\n",
									  $value['color'],
									  $value['name']);
						$upgradeTable = sprintf("<table class=\"events\"><tr><th><font color=\"#%s\">%s</font></th><th>First seen</th><th>Total</th></tr>\n",
									  $value['color'],
									  $value['name']);
						foreach ($value['firstevents'] as $eventid => $time) {
							$eventarray = $b->getAbilityArray($eventid);
							$str = sprintf("<tr><td>%s</td><td>%s</td><td>%d</td></tr>\n",
										$eventarray['name'],
										$b->getFormattedSecs($time),
										$value['numevents'][$eventid]);
							switch ($eventarray['type']) {
								case SC2_TYPEBUILDING:
								case SC2_TYPEBUILDINGUPGRADE:
									$buildingTable .= $str;
									break;
								case SC2_TYPEUNIT:
								case SC2_TYPEWORKER:
									$unitTable .= $str;
									break;
								case SC2_TYPEUPGRADE:
									$upgradeTable .= $str;
									break;
								default:
							}
						}
						$buildingTable .= "</table>";
						$unitTable .= "</table>";
						$upgradeTable .= "</table>";
						$buildingDiv .= $buildingTable;
						$unitDiv .= $unitTable;
						$upgradeDiv .= $upgradeTable;
					}
					echo $buildingDiv . "</div>";
					echo $unitDiv . "</div>";
					echo $upgradeDiv . "</div>";
					echo "</div>";
				}
			}
			else if ($a->getFileSize("DocumentHeader") > 0 && $a->getFileSize("Minimap.tga") > 0) { // possibly SC2 map file
				if (class_exists("SC2Map") || (include 'sc2map.php')) {
					$sc2map = new SC2Map();
					$sc2map->parseMap($a);
					echo "<table>";
					echo sprintf("<tr><td>Map name:</td><td>%s</td></tr>\n",$sc2map->getMapName());
					echo sprintf("<tr><td>Author:</td><td>%s</td></tr>\n",$sc2map->getAuthor());
					echo sprintf("<tr><td>Short description:</td><td>%s</td></tr>\n",preg_replace('/<[^>]+>/','',$sc2map->getShortDescription()));
					echo sprintf("<tr><td>Long description:</td><td>%s</td></tr>\n",preg_replace('/<[^>]+>/','',$sc2map->getLongDescription()));
					$minimapfilename = md5($sc2map->getMapName()).".png";
					imagepng($sc2map->getMiniMapData(),$minimapfilename);
					echo sprintf("</table>Minimap:<br /><img src=\"$minimapfilename\" /><br />\n");
				}
			}
			echo sprintf("<p>Peak memory usage: %d bytes<br /></p>\n",memory_get_peak_usage(true));
			$parseDurationString .= sprintf("Page generated in %d ms.<br />\n",((microtime_float() - $start)*1000));
			echo "<p>$parseDurationString</p>";
		}
	}
}
?>

</body>
</html>
