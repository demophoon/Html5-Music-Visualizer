<?php

$banned = Array();
$banned[0] = '127.0.1.1';
$banned[1] = '192.168.1.8';
$streamMusic = true;
$downloadMusic = false;

if (in_array($_SERVER['REMOTE_ADDR'],$banned)) {
  $info['javascript'] = "document.write('You have been banned from the music player.');stop=true;play=false;";
  die(json_encode($info));
}

// Update for random authentication code in seconds
$timeout = 15;

// Update Interval in Milliseconds
$update = ($timeout - (time() - (floor(time()/$timeout) * $timeout))) * 1000;

function secondsToMinutes($seconds) {
	//takes ### seconds and converts to ##:##
	$mins = 0;
	$secs = 0;
	if ($seconds > 0) {
		$mins = floor ($seconds / 60);
		$secs = $seconds % 60;
	}
	$secs = str_pad($secs, 2, "0", STR_PAD_LEFT);
	return $mins.":".$secs;
}

require_once('/getid3/getid3.php');

$iTunes = new COM('iTunes.Application');

$library = $iTunes->LibraryPlayList;
$currentTrack = $iTunes->CurrentTrack;
// die($filename);

// $tracks = $library->tracks;
// $path = 'C:/xampp/htdocs/itrc/images/albumart/';



// listing tracks code (To be finished at later date)
// for ($i=1;$i<$tracks->Count;$i++) {
// echo $tracks->Item($i)->Name;
// echo $tracks->Item($i)->Album;
// echo $tracks->Item($i)->Artist;
// echo $tracks->Item($i)->Duration;
// echo $tracks->Item($i)->Rating;
// echo $tracks->Item($i)->Size;
// echo $tracks->Item($i)->Genre;
// echo "<br>";
// }

$currentTrack = $iTunes->CurrentTrack;
$info = Array();


srand(floor(time()/$timeout)*1207);
$seed = substr(rand(), -1) . substr(rand(), -1) . substr(rand(), -1) . substr(rand(), -1);
srand(floor(time()/2)*1207);
$seed2 = "0." . substr(rand(), -1) . substr(rand(), -1) . substr(rand(), -1) . substr(rand(), -1);
$musicId = substr(rand() * rand(), -1) . substr(rand() * rand(), -1) . substr(rand() * 17, -1) . substr(rand() * 8 - rand(), -1);

if($currentTrack) {
  $position = $iTunes->PlayerPosition;
  $info["status"] = $iTunes->PlayerState;
  // $info["name"] = $currentTrack->Name;
  
  $info['name'] = 'Hello World! You are awesome! ...and stuff.';
  $info["num"] = $currentTrack->TrackNumber;
  $info["album"] = $currentTrack->Album;
  $info["artist"] = $currentTrack->Artist;
  $info["duration"] = $currentTrack->duration;
  $info["position"] = $position;
  $info["elapsed"] = secondsToMinutes($position);
  $info["remaining"] = "-".secondsToMinutes($info["duration"] - $position);
  $info["volume"] = $iTunes->SoundVolume;
  $info["update"] = $update;
  $info["seed"] = $seed;
  $info["rating"] = $currentTrack->Rating;
  $info["genre"] = $currentTrack->Genre;
  $info["bitrate"] = $currentTrack->bitrate;
  $info["filesize"] = $currentTrack->size;
  $info["type"] = $currentTrack->kind;
  $info["download"] = $downloadMusic;
  $info["seed2"] = $seed2;
  $myFile = "C:/xampp/htdocs/itrc/sync.txt";
  $fh = fopen($myFile, 'r');
  $data = fread($fh, filesize($myFile));
  fclose($fh);
  $syncFile = json_decode($data,true);
  $info["syncState"] = $syncFile['syncState'];
  $info["javascript"] = $syncFile['javascript'];
  $info["register"] = $syncFile['register'];
  
  // $info["javascript"] = "if (syncState != -1) { desync(); } else { play=true; }";
  // $info["javascript"] = "if (syncState != -1) { desync();playerId=$musicId;reRegister(); } else { play=true; }";
  // $info["javascript"] = "if (playerId == 142 && syncState != -1) { 
  // alert('Hello!') } else { 
  // play=true;
  // }";
} else {
  $position = 0;
  $info["status"] = $iTunes->PlayerState;
  $info["name"] = "No song playing";
  $info["num"] = "";
  $info["album"] = "";
  $info["artist"] = "";
  $info["duration"] = -1;
  $info["position"] = $position;
  $info["elapsed"] = -1;
  $info["remaining"] = -1;
  $info["volume"] = $iTunes->SoundVolume;
  $info["update"] = $update;
  $info["seed"] = $seed;
  $info["syncState"] = -1;
  $info["javascript"] = "";
}
$info["sync"] = true;
if (isset($_GET['action'])) {
  if (@$_GET['remote'] == 247 || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    switch ($_GET['action']) {
      case "play":
        if ($iTunes->PlayerState == 1) {
          $iTunes->Pause();
        }
        else {
          $iTunes->Play();
        }
      break;
      case "next":
        $iTunes->NextTrack();
      break;
      case "prev":
        $iTunes->BackTrack();
      break;
    }
  }
}

unset($iTunes);
date_default_timezone_set("US/Central");

if (isset($_GET['rc'])) {
  
  $file = "if (playerId == " . $_GET['remote'] . " && syncState != -1) { 
  alert('Hello!') } else { 
  play=true;
  }";
  
}

if (isset($_GET['update'])) {
  session_start();
  $mobile = (isset($_GET['mobile']) || isset($_SESSION['musicPlayerMobile'])) ? " [M]" : "";
  if ($_SESSION['musicPlayerTime'] < time()) {
    $_SESSION['musicPlayerTime'] = ceil(time()/(15*60))*(15*60);
    $h = fopen("C:/xampp/htdocs/itrc/users.txt","a");
    if (!isset($_SESSION['musicPlayerUID'])) {
      die();
    }
    fwrite($h,"User Active(" . date('D M j, g:i:sa') . ")$mobile: " . $_SESSION['musicPlayerUID'] . " - " . $_SERVER['REMOTE_ADDR'] . "\n");
    fclose($h);
  }
  if (isset($_SESSION['musicPlayerUID'])) {
    die(json_encode($info));
  }
  die('{"status":0,"name":"ERROR","num":0,"album":"You have been desyncronized. Please Reconnect",
  "artist":"Error Code:0x20","duration":0,"position":0,
  "elapsed":"Err","remaining":"Err","volume":0,"update":990000,
  "seed":"0","rating":0,"genre":null,"bitrate":0,"filesize":0,
  "type":1,"download":false,"seed2":"0","syncState":-1,"javascript":"",
  "register":"setTimeout(\'stop=true\',10000);","sync":false}');
}
if (isset($_GET['info'])) {
  echo "<u>" . $info['name'] . "</u> - " . $info['artist'] . " (on the album '<i>" . $info['album'] . "</i>')"; 
}
if (isset($_GET['music']) && $streamMusic) {
  $filename = $currentTrack->location;
  //print_r(get_headers($filename));
  if (!$_GET['syncid'] == $seed + 1) {
  header('Content-type: audio/mpeg');
	header('Content-length: ' . filesize($filename));
  
  print file_get_contents($filename);
  }
  die();
}
if (isset($_GET['download']) && $downloadMusic) {
  $filename = $currentTrack->location;
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Disposition: attachment; filename=" . $info['name'] . "-" . $info['artist']);
  header('Content-type: audio/mpeg');
  header("Content-Transfer-Encoding: binary");
  readfile($filename);
  session_start();
  $mobile = (isset($_GET['mobile']) || isset($_SESSION['musicPlayerMobile'])) ? " [M]" : "";
  $h = fopen("C:/xampp/htdocs/itrc/users.txt","a");
  fwrite($h,"User Download(" . date('D M j, g:i:sa') . ")$mobile: " . $_SESSION['musicPlayerUID'] . " - " . $_SERVER['REMOTE_ADDR'] . "   '" . $info['name'] . "-" . $info['artist'] . ".mp3'\n");
  fclose($h);
  die();
}
if (isset($_GET['unload'])) {
  session_start();
  $mobile = (isset($_GET['mobile']) || isset($_SESSION['musicPlayerMobile'])) ? " [M]" : "";
  $h = fopen("C:/xampp/htdocs/itrc/users.txt","a");
  fwrite($h,"User Unregister(" . date('D M j, g:i:sa') . ")$mobile: " . $_SESSION['musicPlayerUID'] . " - " . $_SERVER['REMOTE_ADDR'] . "\n");
  fclose($h);
  session_destroy();
  echo(json_encode(""));
}

if (isset($_GET['albumart'])) {
	$getID3 = new getID3;
	$filename = $currentTrack->location;
	$getID3->option_tags_images = true;
	$fileinfo = $getID3->analyze($filename);
	$picture = @$fileinfo['id3v2']['APIC'][0]['data']; // binary image data
	$mimetype = @$fileinfo['id3v2']['APIC'][0]['image_mime'];

	if ($picture) {
		header("Content-Type: " . $mimetype);
		die($picture);
	} else {
		// header("Content-Type: image/png");
		readfile('C:/xampp/htdocs/itrc/noart.png');
	}
	die();
}

if (isset($_GET['register'])) {
  session_start();
  $mobile = (isset($_GET['mobile']) || isset($_SESSION['musicPlayerMobile'])) ? " [M]" : "";
  if (isset($_GET['mobile']))
    $_SESSION['musicPlayerMobile'] = $_GET['mobile'];
  if (isset($_SESSION['musicPlayerUID']) && isset($_SESSION['musicPlayerTime']) && $_SESSION['musicPlayerTime'] < ((ceil(time()/420)) * 420) || (isset($_SESSION['musicPlayerUID']) && !isset($_GET['force']))) {
    $musicUID = $_SESSION['musicPlayerUID'] . " - " . $_SERVER['REMOTE_ADDR'];
  } else {
    $_SESSION['musicPlayerUID'] = $_GET['register'];
    $musicUID = $_GET['register'] . " - " . $_SERVER['REMOTE_ADDR'];
    $_SESSION['musicPlayerTime'] = time();
    $h = fopen("C:/xampp/htdocs/itrc/users.txt","a");
    fwrite($h,"User Register(" . date('D M j, g:i:sa') . ")$mobile: " . $_SESSION['musicPlayerUID'] . " - " . $_SERVER['REMOTE_ADDR'] . "\n");
    fclose($h);
  }
  if (in_array($_SERVER['REMOTE_ADDR'],$banned)) {
    die(json_encode("document.write('You have been banned from the music player.');stop=true;play=false;"));
  } else {
    $pid = $_SESSION['musicPlayerUID'];
    $init = '';
    if ($_GET['count'] == '0' && !isset($_GET['mobile'])) {
      $init = 'canvas.fillText(\'Successfully Registered!\',width/2,3*height/4);play=' . $streamMusic . ';var t = setTimeout(\'download();update();\',2400);';
    }
    if ($_GET['count'] == 0 && isset($_GET['mobile'])) {
      $init = 'refresh(1);update();';
    }
    echo(json_encode("setPlayerId($pid);$init"));
  }
  die();
}

// Update Timer in Seconds :
$crossfade = 6; // 6 seconds of crossfade
$update = 30;
$freq = 10;

$pos = ceil(20*($position / $info["duration"]));
$rem = 20 - $pos;
$bar = str_pad("|",$pos,"#") . "|" . str_pad("",$rem,"-") . "|";

$count = ($info["duration"] / $freq > $update || $info["duration"] / $freq < 3) ? $update : ceil($info["duration"] / $freq);
$u = 0;
if ($info['status'] == 1) {
  $status = "Now Playing";
  $u = 1;
} else {
  $status = "Paused";
  $count = 90;
}

$barpos = ($position / $info["duration"]);
$barpos = ceil($barpos * 100);

$finalc = ($info["duration"] - $position - 2 < (0 + $crossfade)) ? 2 : ($info["duration"] - $position - 2);
die();
echo "<html>
<head>
<script src='jquery.js'></script>
<script type='text/javascript'>
  function convert(x) {
    var out = '';
    var sec = Math.floor(x)%60;
    if (sec < 10) {
      sec = '0' + sec;
    }
    var min = Math.floor(x/60);
    out = min + ':' + sec;
    return out;
  }
  
  function updateTrack() {
    $('#name').html(current.name);
    $('#artist').html(current.artist);
    $('#album').html(current.album);
    if (current.status == 1) {
      $('#status').html('Now Playing');
    } else {
      $('#status').html('Paused');
    }
    if (parent.document.title != current.name) {
      parent.document.title = current.name;
      $('#rate').html('Updated!');
    }
  }
  
  function update() {
    var updatetime = (current.duration) / ($('#progbar').width() * 3);
    $('#elapsed').html('-' + convert(current.duration - current.position));
    $('#remain').html(convert(current.position));
    $('#prog').width($('#progbar').width() * (current.position/current.duration));
    $('#volume').width((current.volume) + '%');
    if (current.status == 1) {
      current.position += updatetime;
    }
    current.remaining = convert(Math.ceil(current.duration - current.position));
    current.elapsed = convert(Math.floor(current.position));
    setTimeout('update()',1000 * updatetime);
    $('#rate').html('');
  }
  
  var current = new Array();
  current['name'] = '';
  function refresh() {
    $(document).ready( function(){
      $.ajaxSetup ({  
        cache: false  
      });
      $.getJSON('ctrack.php', { 'update': 'true'},
        function(data){
          if (current.name != data.name || current.volume != data.volume || current.status != data.status || current.position != data.position) {
            $('#rate').html('Syncing...');
            current = data;
            updateTrack();
          }
          setTimeout('refresh()',data.update);
        });
    });
    
  }
  refresh();
  

  
</script>
<style type='text/css'>
  body {background:#dddfea;}
</style>

</head>
<body style='overflow:hidden;' onload='update()'><center>
<small id='status'>No Song Selected</small><hr style='padding:0px;margin:0px;'>
<b>
<h1 style='margin:0px;padding:0px;' id='name'>NaN</h1></b>
<h2 style='margin:0px;padding:0px;' id='artist'>NaN</h2>
<h3 style='margin:0px;padding:0px;' id='album'>NaN</h3>
<span id='progbar' style='width:98%;height:20px;background:#eef;display:block;border:1px solid #333;margin:0px;'>
<div id='prog' style='background:#aaf;width:0%;float:left;text-align:left;height:20px;border-right:1px #333 solid;'></div>
<div style='position:absolute;width:95%;'>
<div id='remain' style='float:left;'></div>
<div id='elapsed' style='float:right;'></div>
</div>
</span>
<span style='width:300px;height:5px;background:#fffffe;display:block;border:1px solid #333;border-top:0px;'>
<span id='volume' style='background:#fffa70;width:0%;float:left;text-align:left;height:5px;'></span>
</span>
<hr>
<div id='rate'></div>
</center>
</body></html>
";
?>
