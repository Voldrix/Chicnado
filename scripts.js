var imgIndex = 1, files, filesDefOrd, columns = [], numOfCols, carousel, backOrForward, FS, oldWindowWidth, paused = true;

//get url parameters
var urlParams = new URLSearchParams(window.location.search);
var album = urlParams.get('album');
rowsOrColsBtn.value = urlParams.get('orient') || 'cols';
sortBtn.value = urlParams.get('sort') || '';
filterBtn.value = urlParams.get('filter') || '';

window.history.replaceState({album: album, viewer: 'closed'}, '', null);

genCols();
if(album) getAlbum();
else getAlbums();


//POP STATE
window.onpopstate = function(event) {
  var uri = new URLSearchParams(location.search);

  if(sortBtn.value && sortBtn.value !== 'def')
    uri.set('sort', sortBtn.value);
  else uri.delete('sort');

  if(filterBtn.value)
    uri.set('filter', filterBtn.value);
  else uri.delete('filter');

  if(rowsOrColsBtn.value === 'rows')
    uri.set('orient', rowsOrColsBtn.value);
  else uri.delete('orient');

  window.history.replaceState({album: event.state.album, viewer: event.state.viewer}, '', '?'+uri);

  if(!event.state.album) getAlbums();
  else if(album !== event.state.album) {album = event.state.album; getAlbum();}
  if(event.state.viewer === 'closed' && viewerbg.checkVisibility()) viewer('close', 0, true);
  if(event.state.viewer !== 'closed') viewer('open', event.state.viewer, true);
};


//GET ALBUMS
function getAlbums() {
  breadcrumbs.innerHTML = '';
  dirs.innerHTML = '';
  albumList.innerHTML = '';
  picsRow.innerHTML = '';
  columns.forEach((c) => c.innerHTML = '');

 var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      var _albums = JSON.parse(this.responseText);
      if(!_albums || _albums == 0) {
        location.href = 'generator.php';
      }
      for(a of _albums) {
        albumList.innerHTML += `<a href="?album=${a}/" class=albumLink onclick='navigate("${a}/")'>${a}</a>`;
      }
      albumContainer.style.display = 'inline-block';
    }
  }
  xhttp.open('GET', 'api.php', true);
  xhttp.send();
}


//GET ALBUM
function getAlbum() {
  dirs.innerHTML = '';
  picsRow.innerHTML = '';
  columns.forEach((c) => c.innerHTML = '');
  const _album = album;

  var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      files = JSON.parse(this.responseText);
      filesDefOrd = structuredClone(files);
      if(sortBtn.value !== '' && sortBtn.value !== 'def')
        sort(false);
      render(_album);
    }
    else {
      alert("Coulnd not get dir:\n" + _album + "\nError: " + this.status);
    }
  }

  xhttp.open('GET', 'api.php?album=' + _album + '&filter=' + filterBtn.value, true);
  xhttp.send();
}


//SORT
function sort(userMuted) {
  if(userMuted) {
    var uri = new URLSearchParams(location.search);
    var srt = uri.get('sort') || '';
    if(sortBtn.value && sortBtn.value !== 'def')
      uri.set('sort', sortBtn.value);
    else
      uri.delete('sort');
    window.history.replaceState({album: album, viewer: 'closed'}, '', '?'+uri);
  }

  if(!files) return;

  var _sort = sortBtn.value;
  switch(_sort) {
    case 'mtime':
    case 'size':
      files.dirs.sort((a, b) => b[_sort] - a[_sort]);
      files.files.sort((a, b) => b[_sort] - a[_sort]); break;
    case 'name':
      files.dirs.sort((a, b) => a.name.localeCompare(b.name, "en", {sensitivity: "base"}));
      files.files.sort((a, b) => a.name.localeCompare(b.name, "en", {sensitivity: "base"})); break;
    case 'rand':
      for(x of [files.dirs, files.files]) {
        let i, j, temp;
        for(i = x.length - 1; i > 0; i--) {
          j = Math.floor(Math.random() * (i + 1));
          temp = x[i]; x[i] = x[j]; x[j] = temp;
        }
      }; break;
    default:
      files = structuredClone(filesDefOrd); break;
  }
}


//FILTER
function filter(_filter) {
  var uri = new URLSearchParams(location.search);
  if(filterBtn.value)
    uri.set('filter', filterBtn.value);
  else
    uri.delete('filter');
  window.history.replaceState({album: album, viewer: 'closed'}, '', '?'+uri);

  getAlbum();
}


//ORIENTATION
function orientation() {
  var uri = new URLSearchParams(location.search);
  if(rowsOrColsBtn.value === 'rows')
    uri.set('orient', rowsOrColsBtn.value);
  else
    uri.delete('orient');
  window.history.replaceState({album: album, viewer: 'closed'}, '', '?'+uri);

  render(album);
}


//NAVIGATE
function navigate(_albumDir) {
  event.preventDefault();
  if(!_albumDir || album === _albumDir) return;
  album = _albumDir;

  var uri = new URLSearchParams(location.search);
  uri.set('album', _albumDir);
  window.history.pushState({album: _albumDir, viewer: 'closed'}, '', '?'+uri);

  getAlbum();
}


//RENDER
async function render(_album) {
  breadcrumbs.innerHTML = '';
  dirs.innerHTML = '';
  picsRow.innerHTML = '';
  columns.forEach((c) => c.innerHTML = '');
  fullViewImg.src = '';
  albumContainer.style.display = 'none';
  imgIndex = 1;
  var winWidth = window.innerWidth;

  if(!_album) return;

  if(_album[_album.length - 1] !== '/')
    _album += '/';
  let cumulative = '';
  for(a of _album.split('/')) { //breadcrumbs
    cumulative += a + '/';
    if(a) breadcrumbs.innerHTML += `<a href="?album=${cumulative}" onclick='navigate("${cumulative}")'>${a}</a> / `;
  }

  if(!files || _album !== album || winWidth !== window.innerWidth) return;

  for(d of files.dirs) { //directories
    dirs.innerHTML += `<a href="?album=${_album}${d.name}/" onclick='navigate("${_album}${d.name}/")'>${d.name}</a>`;
  }

  var domPTR = picsRow;

  for([idx,f] of files.files.entries()) {
    if(_album !== album || winWidth !== window.innerWidth) return;

    //preload img
    var err = 0;
    await waitForImageToLoad('thumbnails/' + _album + f.name)
      .catch(error => {err = 1;});
    if(err) {
      if(_album !== album || winWidth !== window.innerWidth) return;
      if(f.name.match(/\.(jpg|jpeg|png|gif|webp|ico|bmp|heic)$/i)) {
        await waitForImageToLoad('galleries/' + _album + f.name)
          .catch(error => {err = 2; files.files[idx].valid = 0;});
      }
      else {err = 2; files.files[idx].valid = 0;}
    }
    if(_album !== album || winWidth !== window.innerWidth) return;

    //find shortest column
    if(rowsOrColsBtn.value === 'cols') {
      domPTR = columns[0];
      for(var col = 0; col < numOfCols; col++)
        domPTR = (columns[col].scrollHeight < domPTR.scrollHeight) ? columns[col] : domPTR;
    }

    var thumbnail = (err === 1) ? 'galleries' : 'thumbnails';
    var border = (err === 1) ? 'style="border:5px solid #B0B;"' : '';
    if(err === 2) //invalid file
      domPTR.innerHTML += `<div class=imgErrContainer><span>${f.name}</span></div>`;
    else //img thumbnail
      domPTR.innerHTML += `<div class=imgContainer><img src="${thumbnail}/${_album}${f.name}" onclick=viewer('open',${imgIndex},false) ${border} onerror='this.onerror=null;this.src="galleries/${_album}${f.name}";this.style.border="5px solid #0BB"'><span>${f.name}</span></div>`;
    imgIndex += 1;
  }
}


async function waitForImageToLoad(imageSrc) {
  const image = new Image();
  return new Promise((resolve, reject) => {
    image.onload = () => resolve();
    image.onerror = (error) => reject(error);
    image.src = imageSrc;
  });
}


//GENERATE COLUMNS
function genCols() {
  oldWindowWidth = document.documentElement.clientWidth;
  numOfCols = Math.floor(oldWindowWidth / 230);
  var colWidth = Math.floor(oldWindowWidth / numOfCols);

  columns = [];
  picsCol.innerHTML = '';
  for(let i = 0; i < numOfCols; i++) {
    let newCol = document.createElement('div');
    newCol.classList.add('col');
    newCol.style.width = colWidth + 'px';
    columns.push(newCol);
    picsCol.appendChild(newCol);
  }
}


//VIEWER
function viewer(openOrClose, pageNum, popState) {
  clearInterval(carousel);
  paused = true;
  if(openOrClose === 'open') { //open
    viewerbg.style.display = 'flex';
    if(!popState)
      window.history.pushState({album: album, viewer: pageNum}, '', null);
    imgIndex = pageNum;
    turnPage(0);
    viewerbg.focus();
  }
  else { //close
    if(popState)
      viewerbg.style.display = 'none';
    else
      history.back();
    fullViewImg.src = '';
    FS = false;
  }
}


//zoom img
function zoomImg(reset=false) {
  if(reset || fullViewImg.style.cursor === 'zoom-out') { //zoom out
    fullViewImg.style.cursor = 'zoom-in';
    fullViewImg.style.maxWidth = '100%';
    fullViewImg.style.maxHeight = '100%';
    fullViewImg.style.width = 'auto';
    fullViewImg.style.height = 'auto';
  }
  else { //zoom in
    fullViewImg.style.cursor = 'zoom-out';
    fullViewImg.style.maxWidth = 'none';
    fullViewImg.style.maxHeight = 'none';

    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    const imgWidth = fullViewImg.naturalWidth;
    const imgHeight = fullViewImg.naturalHeight;

    if(event.ctrlKey && ((imgWidth < screenWidth) !== (imgHeight < screenHeight))) {
      if(imgWidth < screenWidth) fullViewImg.style.width = '100%';
      if(imgHeight < screenHeight) fullViewImg.style.height = '100%';
    }

    if(imgWidth < screenWidth && imgHeight < screenHeight) {
      if(imgWidth / screenWidth < imgHeight / screenHeight) {
        if(event.ctrlKey) fullViewImg.style.width = '100%';
        else fullViewImg.style.height = '100%';
      }
      else {
        if(event.ctrlKey) fullViewImg.style.height = '100%';
        else fullViewImg.style.width = '100%';
      }
    }
  }
}


//turn page
function turnPage(previousOrNext) {
  backOrForward = previousOrNext;
  imgIndex += previousOrNext;
  if(imgIndex > files.files.length) imgIndex = 1;
  if(imgIndex < 1) imgIndex = files.files.length;
  zoomImg(true);
  if(document.fullscreenElement == null) FS = false;
  window.history.replaceState({album: album, viewer: imgIndex}, '', null);
  if(files.files[imgIndex-1].valid)
    fullViewImg.src = 'galleries/' + album + files.files[imgIndex-1].name;
  else
    turnPage(previousOrNext);
}


function pausePlay() {
  paused = !paused;
  pausePlayBtn.style.color = paused ? null : '#191';
  if(!paused)
    carousel = setInterval(turnPage, 5000, 1); //5 second autoplay
  else clearInterval(carousel);
}


function fullscreenToggle(openClose) {
  if((openClose === 'open' || openClose === 'toggle') && document.fullscreenElement == null) {
    FS = true;
    var rfs = viewerbg.requestFullscreen || viewerbg.mozRequestFullscreen || viewerbg.webkitRequestFullscreen;
    rfs.call(viewerbg);
  }
  else if((openClose === 'close' || openClose === 'toggle') && document.fullscreenElement) {
    var rfs = document.exitFullscreen || document.mozCancelFullScreen || document.webkitExitFullscreen;
    rfs.call(document);
  }
}


//EVENTS

//click background to close viewer
viewerbg.addEventListener('click', function(event) {
  if(event.target.id === 'viewerbg') {
    fullscreenToggle('close');
    viewer('close', 0, false);
}}, false);


//keyboard
document.addEventListener('keydown', event => {
  if(event.repeat || !viewerbg.checkVisibility()) return;
  switch(event.key) {
    case "Escape": fullscreenToggle('close'); viewer('close', 0, false); break;
    case "ArrowLeft": if(fullViewImg.width <= document.documentElement.clientWidth) turnPage(-1); break;
    case " ":
    case "Enter":
    case "ArrowRight": if(fullViewImg.width <= document.documentElement.clientWidth) turnPage(1); break;
    case 'f': fullscreenToggle('toggle'); break;
    case 'p': pausePlay(); break;
  }}, false);


//preload next image
fullViewImg.addEventListener('load', () => {
  var imgIndexOffset = -1;
  if(backOrForward < 0 && imgIndex > 1) imgIndexOffset = 2;
  if(backOrForward >= 0 && imgIndex < files.files.length) imgIndexOffset = 0;
  if(imgIndexOffset !== -1) (new Image()).src = 'galleries/' + album + files.files[imgIndex].name;
});


//window resize
window.addEventListener('resize', function(event) {
  if(FS || document.documentElement.clientWidth === oldWindowWidth) return;
  genCols();
  if(rowsOrColsBtn.value === 'cols')
    render(album);
});


//Swipe Gestures
var xDown, swipe = 0;
document.addEventListener('touchstart', function(evt) {
  xDown = evt.touches[0].clientX;
  swipe = 0;
}, false);
document.addEventListener('touchmove', function(evt) {
  var xUp = evt.touches[0].clientX;
  var xDiff = xDown - xUp;
  if(xDiff > 60 && swipe === 0) {turnPage(1); swipe=1;}
  if(xDiff < -60 && swipe === 0) {turnPage(-1); swipe=1;}
}, false);

