var fullViewImg = document.getElementById('fullViewImg');
var viewerbg = document.getElementById('vwr');
var images = document.getElementsByClassName('YvY');
var imgIndex = 1, carousel, paused = false;

viewerbg.addEventListener('click',function(event) {if (event.target.id === 'vwr') {fullscreen(0); viewer('none');}},false); //click background to close viewer
document.addEventListener('keydown',event => {
  if (event.repeat || viewerbg.style.display !== 'flex') return;
  switch(event.key) {
    case "Escape": fullscreen(0); viewer('none');break;
    case "ArrowLeft": turnPage(-1);break;
    case "ArrowRight": turnPage(1);break;
    case 'f': fullscreen();break;
    case 'p': pausePlay();break;
    default: turnPage(1);
  }},false);

function viewer(openOrClose,pageNum=1) {
  viewerbg.style.display = openOrClose;
  clearInterval(carousel); paused = false;
  if(openOrClose === 'flex') { //open
    imgIndex = pageNum;
    turnPage(0);
  }
}

function turnPage(previousOrNext) {
  imgIndex += previousOrNext;
  if (imgIndex > images.length) imgIndex = 1;
  if (imgIndex < 1) imgIndex = images.length;
  fullViewImg.src = images[imgIndex-1].getAttribute('src').replace('thumbnails/','galleries/');
  //preload next image
  if (previousOrNext < 0 && imgIndex > 1) (new Image()).src = images[imgIndex-2].getAttribute('src').replace('thumbnails/','galleries/');
  if (previousOrNext >= 0 && imgIndex < images.length) (new Image()).src = images[imgIndex].getAttribute('src').replace('thumbnails/','galleries/');
}

function pausePlay() {
  paused = !paused;
  if (paused) carousel = setInterval(turnPage,5000,1); //5 second autoplay
  else clearInterval(carousel);
}

function sortFilter(sort) {
  uri = new URLSearchParams(location.search);
  uri.set('saf',sort);
  location.search = uri;
}

function fullscreen(fs=1) {
  if (document.fullscreenElement == null) {
    if (fs === 1) {
      var rfs = viewerbg.requestFullscreen || viewerbg.mozRequestFullscreen || viewerbg.webkitRequestFullscreen; rfs.call(viewerbg);
    }
  }
  else {
    var rfs = document.exitFullscreen || document.mozCancelFullScreen || document.webkitExitFullscreen; rfs.call(document);
  }
}

//Swipe Gestures
var xDown, swipe = 0;
document.addEventListener('touchstart', function(evt) {xDown = evt.touches[0].clientX; swipe = 0;}, false);
document.addEventListener('touchmove', function(evt) {
  var xUp = evt.touches[0].clientX;
  var xDiff = xDown - xUp;
  if(xDiff > 60 && swipe === 0) {turnPage(1); swipe=1;}
  if(xDiff < -60 && swipe === 0) {turnPage(-1); swipe=1;}
}, false);

