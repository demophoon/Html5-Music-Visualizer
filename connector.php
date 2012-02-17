<?php
// iTunes + JSON Connector
// Written by Britt Gresham with GetID3 Library

require_once('/getid3/getid3.php');

$banned = Array();

// Capabilitys
$STREAMING 			= 		true;
$DOWNLOAD		 	= 		true;
$REMOTE				=		true;


// Sec=>Min:Sec Conversion Function
function secondsToMinutes($seconds) {
	$mins = 0;
	$secs = 0;
	if ($seconds > 0) {
		$mins = floor ($seconds / 60);
		$secs = $seconds % 60;
	}
	$secs = str_pad($secs, 2, "0", STR_PAD_LEFT);
	return $mins.":".$secs;
}

// Update iTunes Info
$info = Array();
$info["refresh"] = false;

$iTunes = new COM('iTunes.Application');
$currentTrack = $iTunes->CurrentTrack;
iTunesUpdate();

function iTunesUpdate() {
	global $DOWNLOAD,$info,$iTunes,$currentTrack;
	$currentTrack = $iTunes->CurrentTrack;
	if($currentTrack) {
		$position = $iTunes->PlayerPosition;
		$info["refresh"] = true;
		$info["status"] = $iTunes->PlayerState;
		$info["name"] = $currentTrack->Name;
		// $info['name'] = 'Hello World! You are awesome! ...and stuff.';
		$info["num"] = $currentTrack->TrackNumber;
		$info["album"] = $currentTrack->Album;
		$info["artist"] = $currentTrack->Artist;
		$info["duration"] = $currentTrack->duration;
		$info["position"] = $position;
		$info["elapsed"] = secondsToMinutes($position);
		$info["remaining"] = "-".secondsToMinutes($info["duration"] - $position);
		$info["volume"] = $iTunes->SoundVolume;
		$info["rating"] = $currentTrack->Rating;
		$info["genre"] = $currentTrack->Genre;
		$info["bitrate"] = $currentTrack->bitrate;
		$info["filesize"] = $currentTrack->size;
		$info["type"] = $currentTrack->kind;
		$info["download"] = $DOWNLOAD;
		
		// Javascript
		$myFile = "C:/xampp/htdocs/itrc/sync.txt";
		$fh = fopen($myFile, 'r');
		$data = fread($fh, filesize($myFile));
		fclose($fh);
		$syncFile = json_decode($data,true);
		$info["syncState"] = $syncFile['syncState'];
		$info["javascript"] = $syncFile['javascript'];
		$info["register"] = $syncFile['register'];
  }
}

@$action = $_REQUEST['action'];
@$playId = $_REQUEST['playerId'];

// Comet Long Polling Script
// Psudo "Push" Server

switch ($action) {
case 'update':
		$time = time();
		while (time() - $time < 25) {
			if ($info["name"] != $iTunes->CurrentTrack->Name || $info["volume"] != $iTunes->SoundVolume || $info["status"] != $iTunes->PlayerState) {
				$d = (isset($_REQUEST['data'])) ? $_REQUEST['data'] : 'true';
				if ($d == 'true') {
					iTunesUpdate();
				} else {
					unset($info);
					$info = Array();
					$info['refresh'] = true;
				}
				iTunesUpdate();
				echo json_encode($info);
				unset($iTunes);
				die();
			}
			usleep(750000);
		}
		unset($iTunes);
		$info = Array();
		$info['refresh'] = false;
		echo json_encode($info);
	break;
case 'force':
		iTunesUpdate();
		echo json_encode($info);
	break;
case 'register':
		session_start();
		$pid = (isset($_SESSION['musicPlayerUID'])) ? $_SESSION['musicPlayerUID'] : rand();
		$_SESSION['musicPlayerUID'] = $pid;
		$out = '';
		if ($_GET['count'] == 0) {
			$out = "update();";
		}
		echo json_encode("setPlayerId($pid);" . $out);
	break;
case 'albumart':
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
			header("Content-Type: image/png");
			readfile('C:/xampp/htdocs/itrc/noart.png');
		}
		die();
	break;
case 'stream':
		$filename = $currentTrack->location;
		header('Content-type: audio/mpeg');
		header('Content-length: ' . filesize($filename));		
		print file_get_contents($filename);
		die();
	break;
default:
		echo json_encode($info);
}
die();

?>