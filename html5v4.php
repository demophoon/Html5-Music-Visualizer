<?php session_start(); ?>
<html>
<head>
<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
<title>Html 5 iTunes Controller</title>

<style type='text/css'>
  canvas {
    margin-top:0px;
  }
  body {
    background:#999;
    background:#000;
    margin:0px;
    padding:0px;
	color:#fff;
	overflow:hidden;
  }
</style>
<script src='jquery.js'></script>
<script src='assets/musicPlayer.js'></script>

<script type='text/javascript'>
function init() {
	
	// Create Player's Polling Id and Globals
	var playerId = ~~(Math.random() * 1000);
	var current = new Array();
	current['name'] = '';
	
	// Canvas Variables, settings, functions
	var canvas = document.getElementById('visualizer');
	var info = document.getElementById('info');
	// canvas.height = document.height/4;
	// canvas.width = document.width/4;
	var width = canvas.width;
	var height = canvas.height;
	var ctx = canvas.getContext('2d');
	var infoctx = info.getContext('2d');
	var loadtime = 0;
	var fps = 60;
	
	function clear() {  
		ctx.globalAlpha = .07;
		ctx.fillStyle = colorb[color];  
		// canvas.beginPath(); 
		ctx.fillRect(0,0,width,height); 
		// canvas.closePath();
		// ctx.fill();
		ctx.globalAlpha = 1;
	}
	
	function convert(x) {
		if (x<0) x = 0;
		var out = '';
		var sec = Math.floor(x)%60;
		if (sec < 10) {
		  sec = '0' + sec;
		}
		var min = Math.floor(x/60);
		out = min + ':' + sec;
		return out;
	}
	
	function updateInfo() {
		infoctx.clearRect(0,0,width,height);
		
		infoctx.globalAlpha = .5;
		infoctx.fillStyle = '#000';
		infoctx.fillRect(0,height/8,width,5*height/8);
		 
		infoctx.globalAlpha = 1;
		infoctx.fillStyle = '#f1f1f1';
		infoctx.font = 'bold 1.5em Verdana';
		infoctx.textAlign = 'center';
		infoctx.fillText(current.name,width/2,height/4);
		infoctx.font = '1.3em Verdana';
		infoctx.fillText(current.artist,width/2,3*height/8);
		infoctx.fillText(current.album,width/2,height/2);
		
		
		//bars
		infoctx.fillStyle = '#4A4C59';
		infoctx.fillRect(5,height - 20,(width - 10),10);
		infoctx.fillStyle = '#52C4FE';
		infoctx.fillRect(5,height - 20,(((audioContext.currentTime - loadtime) + current.position)/current.duration) * (width - 10),10);
		infoctx.fillStyle='#664';
		infoctx.fillRect(5,height - 10,(width - 10),5);
		infoctx.fillStyle='#fffa70';
		infoctx.fillRect(5,height - 10,(current.volume/100) * (width - 10),5);
	}
	
//-------------------------------------------------------------
	// Create a webkitAudioContext Instance
	if (typeof AudioContext == "function") {
		var audioContext = new AudioContext();
	} else if (typeof webkitAudioContext == "function") {
		var audioContext = new webkitAudioContext();
	}
	
	// Create Channels, Sound Vars, Set Settings
	var source = audioContext.createBufferSource();
	var analyser = audioContext.createAnalyser();
	var compressor = audioContext.createDynamicsCompressor();
	var gain = audioContext.createGainNode();
	var delay = audioContext.createDelayNode();
	var filter = audioContext.createBiquadFilter();
	var filter2 = audioContext.createBiquadFilter();
	var freqs = new Float32Array(1024);
	var waves = new Uint8Array(1024);
	var waveform = 4;
	var effect = 5;
	var changed = false;
	var colors = ['#ccccff','#f1f1f1','#9900ff','#28caff','#333333','#9911aa'];
	var colorb = ['#000033','#303030','#220055','#026799','#aaaaaa','#338811']
	var color = 1;
	analyser.smoothingTimeConstant = .8;
	analyser.fftSize = 2048;
	analyser.minDecibels = 0;
	analyser.maxDecibels = 2;
	delay.delayTime.value = .5;
	filter.type = 1;
	filter.frequency.value = 5000;
	// filter.Q.value = 10;
	filter.gain.value = 100;
	filter2.type = 1;
	filter2.frequency.value = 5000;
	// filter2.Q.value = 10;
	filter2.gain.value = -200;
	gain.minValue = 0.0;
	gain.maxValue = 0.5;
	
	// Connect Channels Together
	source.connect(delay);
	// filter.connect(filter2);
	// filter2.connect(analyser);
	// compressor.connect(gain);
	gain.connect(audioContext.destination);
	delay.connect(analyser);
	analyser.connect(gain);
	
	
	// Load Data from Analysis Node
	var max = 0;
	var lastPeak = 0;
	var lastSum = 0;
	var lastAvg = 0;
	var lastBeat = -1;
	var bps = -1;
	var beat = -1;
	
	function update() {
		clear();
		ctx.save();
		switch (effect) {
			case 0:	// Circle
				ctx.translate( width/2, height/2);
				ctx.rotate(2*Math.PI / 90);
				ctx.translate( -width/2, -height/2);
				ctx.drawImage(canvas,0,0,width-0,height-0);
			break;
			
			case 1:	// Expand Y-axis
				ctx.drawImage(canvas,-1,-20,width+2,height+40);
			break;
			
			case 2:	// Expand X-axis
				ctx.drawImage(canvas,-20,1,width+40,height-2);
			break;
			
			case 3:	// Expand X,Y-axis
				ctx.drawImage(canvas,-20,-20,width+40,height+40);
			break;
			
			case 4:	// Super Expand X&Y-axis
				ctx.drawImage(canvas,-50,-50,width+100,height+100);
			break;
			
			case 5: // Shake Canvas
				var shakex = (Math.random()*10)-5;
				var shakey = (Math.random()*10)-5;
				ctx.drawImage(canvas,shakex,shakey,width,height);
			break;
			
			case 6: // Sine Random Zooms
				ctx.drawImage(canvas,20,-10,width+(Math.sin(audioContext.currentTime/5)*40),height+(Math.sin(audioContext.currentTime/2)*40));
			break;
			
			case 7: // Sine Random Translations
				ctx.drawImage(canvas,12-(Math.sin(audioContext.currentTime/8)*25),12-(Math.sin(audioContext.currentTime/3)*20),width,height);
			break;
			
			case 8: // Sine Randomness
				ctx.drawImage(canvas,12-(Math.sin(audioContext.currentTime/7)*30),12-(Math.sin(audioContext.currentTime/4)*11),width+(Math.sin(audioContext.currentTime/5)*37),height+(Math.sin(audioContext.currentTime/2)*12));
			break;
			
			case 9: // Sine Spin
				var centerw = Math.abs(Math.sin(audioContext.currentTime) * (width));
				var centerh = Math.abs(Math.sin(audioContext.currentTime/3) * (height));
				ctx.translate( centerw, centerh);
				ctx.rotate((Math.sin(audioContext.currentTime*2)) * 2*Math.PI / 45);
				ctx.translate( -centerw, -centerh);
				ctx.drawImage(canvas,-10,-10,width-(Math.sin(audioContext.currentTime*2)*20),height-(Math.sin(audioContext.currentTime*2)*20));
				// ctx.fillStyle = '#ff0000';
				// ctx.fillRect(centerw,centerh,5,5);
			break;
			default:
				
		}
		ctx.restore();
		ctx.globalCompositeOperation = 'source-over';
		analyser.getFloatFrequencyData(freqs);
		analyser.getByteTimeDomainData(waves);
		var avg = 0;
		var peak = 0;
		var sum = 0;
		for (var x=0; x<freqs.length-512; x+=1) {
			if (waves[x] > peak) {
				peak = (waves[x] + freqs[x])/2;
			}
			sum += (waves[x] + freqs[x])/2;
			ctx.fillStyle = colors[color];
			
			switch (waveform) {
				case 0:	// Spectrum
					ctx.globalAlpha = .25;
					ctx.fillRect(x*(width/(1024 - 512)),height,(width/(1024 -512)),-(height/2*(freqs[x]/70))-height);
					ctx.globalAlpha = 1;
				break;
				case 1:	// Waveform 1
					ctx.fillRect(x*(width/(1024 - 512)),((waves[x]/128)*height/2)-.5,(width/(1024 - 512))*2,2);
				break;
				case 2: // Waveform 2
					ctx.fillRect(x*(width/(1024 - 512)),((waves[x]/128)*height/2)-.5,(width/(1024 - 512))*2,(waves[x]-128));
				break;
				case 3: // Spectrum Peaks
					ctx.fillRect(x*(width/(1024 - 512)),-(height*(freqs[x]/70))-(height/4),(width/(1024 -512)),2);
				break;
			}
			
		}
		avg = sum/(freqs.length-512);
		switch (waveform) {
			case 4: // Volume Squares
				ctx.fillRect(0,-avg,width,avg);
				ctx.fillRect(0,height,width,avg);
				ctx.fillRect(-avg,0,avg,height);
				ctx.fillRect(width,0,avg,height);
			break;
		}
		
		if (Math.abs(sum)>Math.abs(max)) {
			max = Math.abs(sum);
		}
		// for (i=.5;i<height;i+=2){
			// ctx.beginPath();
			// ctx.moveTo(0,i);
			// ctx.lineTo(width,i);
			// ctx.stroke();
		// }
		// for (i=.5;i<width;i+=2){
			// ctx.beginPath();
			// ctx.moveTo(i,0);
			// ctx.lineTo(i,height);
			// ctx.stroke();
		// }
		// ( 60  >  54)
		
		// var lastFrame = ctx.getImageData(0,0,width,height);
		
		
		// ctx.fillText(current.name,width/2,height/2)
		
		avg = sum / 1024 * -2;
		
		if (127+-avg > 139+-lastAvg) {
			// ctx.globalCompositeOperation = 'xor';
			beat = audioContext.currentTime;
			bps = ((1/((beat - lastBeat)*1))*60 < 200) ? (1/((beat - lastBeat)*1))*60 : bps;
			document.getElementById('data2').innerHTML = 'Beat!';
			// ctx.globalAlpha = .05;
			// ctx.fillStyle = colors[color];  
			// ctx.fillRect(0,0,width,height);
			lastBeat = beat;
		} else {
			document.getElementById('data2').innerHTML = 'bps: ' + bps;
			ctx.globalCompositeOperation = 'source-over';
		}
		document.getElementById('data').innerHTML = 127+-avg;
		// document.getElementById('data2').innerHTML = audioContext.currentTime;
		if (~~(audioContext.currentTime)%17 == 0 && changed == false) {
			effect = ~~(Math.random() * 11);
			// effect = 11;
			waveform = ~~(Math.random() * 5);
			// waveform = 4;
			color = ~~(Math.random() * (colors.length));
			// color = 1
			changed = true;
		} else if (~~(audioContext.currentTime)%17 != 0) {
			changed = false;
		}
		lastSum = sum;
		lastPeak = peak;
		lastAvg = avg;
	}
	
	
	// Initialize Mp3
	var pushStart = function(x) {
		if (!x)
			x = 'update';
		$.getJSON('connector.php', {"action":x,"playerId":playerId,"data":true}, 
		function(data) {
			if (data.refresh) {
				if (data.name != current.name || current.name == '') {
					current = data;
					var xhr = new XMLHttpRequest();
					xhr.open("GET", "ctrack.php?music=current" + Math.random() + ".mp3", true);
					xhr.responseType = "arraybuffer";
					xhr.onload = function() {
						if (x != 'force') {document.location.href = 'html5v4.php';}
						var buffer = audioContext.createBuffer(xhr.response, false);
						source.buffer = buffer;
						if (data.position>30) {
							source.noteGrainOn(audioContext.currentTime,data.position+(audioContext.currentTime - loadtime + 1.25),data.duration-(data.position));
						} else {
							source.noteGrainOn(audioContext.currentTime,0,data.duration-(data.position));
						}
						source.noteOn(audioContext.currentTime);
					};
					loadtime = audioContext.currentTime;
					xhr.send();
				} else {
					
				}
				gain.gain.value = (data.volume/200)	;
			}
			pushStart();
		});
	};
	
	pushStart('force');
	
	window.setInterval(update,1000/fps);
	window.setInterval(updateInfo,1000/4);

}
</script>

</head>
<body onload='init();'>
<center>
<div id='canvas' style='width:512px;'>
	<canvas height='550px' width='1024px' style='z-index: 1;position:absolute;left:10%;top:10%;' id='visualizer'></canvas>
	<canvas height='550px' width='1024px' style='z-index: 2;position:absolute;left:10%;top:10%;' id='info'></canvas>
</div>
<div id='data' style='position:absolute;top:600px;color:#000'></div>
<div id='data2' style='position:absolute;top:625px;color:#000'></div>
</center>
</body>
</html>