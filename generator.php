<?php
//REMOVE ALBUM
if(array_key_exists('removeAlbum', $_GET)) {
  if(file_exists('galleries/'.$_GET['removeAlbum']) && is_link('galleries/'.$_GET['removeAlbum']))
    unlink('galleries/'.$_GET['removeAlbum']);
  else {
    http_response_code(401);
    echo 'Album doess not exist:', $_GET['removeAlbum'];
  }
  if(file_exists('thumbnails/'.$_GET['removeAlbum']) && is_dir('thumbnails/'.$_GET['removeAlbum'])) {
    foreach($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('thumbnails/'.$_GET['removeAlbum'], RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
      if($file->isDir()) rmdir($file->getRealPath());
      else unlink($file->getRealPath());
    }
    rmdir('thumbnails/'.$_GET['removeAlbum']);
  }
  exit();
}

//CREATE ALBUM
if(array_key_exists('newAlbum', $_GET)) {
  $imgDir = str_replace(array('\\','"',';','$'),'', $_GET['imgdir']);
  $albumName = trim(str_replace(array('\\','/','"','$',';','?','#','<','>'), '', $_GET['newAlbum']));
  $genThumbs = (isset($_GET['generateThumbs']) && $_GET['generateThumbs'] == '1');
  if(substr($imgDir, -1) === '/') $imgDir = substr($imgDir, 0, -1);

  clearstatcache();

  if(!$albumName || !is_dir($imgDir) || is_dir('thumbnails/'.$albumName) || is_dir('galleries/'.$albumName)) {
    http_response_code(400);
    if(!$albumName) echo 'Invalid album name: ', $albumName;
    if(!is_dir($imgDir)) echo 'Image directory does not exist: ', $imgDir;
    if(is_dir('galleries/'.$albumName)) echo 'Album exists: ', $albumName;
    else if(is_dir('thumbnails/'.$albumName)) echo 'deleted album thumbnails remain: ', $albumName;
    exit();
  }

  if(!is_dir('galleries')) mkdir('galleries', 0777);
  if(!is_dir('thumbnails')) mkdir('thumbnails', 0777);
  symlink($imgDir, 'galleries/'.$albumName);
  if($genThumbs) { //gen thumbs
    mkdir('thumbnails/'.$albumName, 0777);
    if(file_exists('thumbnails/'.$albumName)) {
      set_time_limit(0);
      fastcgi_finish_request();
      makeThumbs($imgDir, 'thumbnails/'.$albumName); //thumbnails
    }
    else {
      http_response_code(500);
      echo 'Unable to create thumbnail directory: ', $albumName;
    }
  }
  exit();
}

//REFRESH THUMBS
if(isset($_GET['refreshThumbs'])) {
  $albumName = trim(str_replace(array('\\','/','"','$',';','?','#','<','>'), '', $_GET['refreshThumbs']));
  $imgDir = 'galleries/'.$albumName;

  if(!$albumName || !is_dir($imgDir)) {
    http_response_code(400);
    echo 'Invalid album: ', $albumName;
    exit();
  }

  clearstatcache();

  if(!file_exists('thumbnails/'.$albumName)) 
    mkdir('thumbnails/'.$albumName, 0777);
  if(!file_exists('thumbnails/'.$albumName)) {
    http_response_code(500);
    echo 'Unable to create thumbnail directory: ', $albumName;
    exit();
  }

  fastcgi_finish_request();
  makeThumbs($imgDir, 'thumbnails/'.$albumName); //thumbnails

  //delete unneeded thumbnails
  foreach($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('thumbnails/'.$albumName, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $img) {
    if(is_file($img)) { //del imgs
      if(!is_file($imgDir.'/'.$iterator->getSubPathname())) unlink($img);
    }
  }
  foreach($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('thumbnails/'.$albumName, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $img) {
    if(is_dir($img)) { //del empty dirs
      if(!(new FilesystemIterator($img))->valid()) rmdir($img);
    }
  }
  exit();
}

//MAKE THUMBNAILS
function makeThumbs($source, $dest) {
  set_time_limit(0);
  $rowHeight = 268;
  $colWidth = 240;

  foreach($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $img) {
    $tmpImg = $alpha = false;

    if(is_dir($img)) { //dir
      if(!is_dir($dest.'/'.$iterator->getSubPathname()))
        mkdir($dest.'/'.$iterator->getSubPathname());
    }
    else { //file
      $imgThumb = $dest.'/'.$iterator->getSubPathname();
      $imgExt = strtolower(pathinfo($img, PATHINFO_EXTENSION));
      $imgType = exif_imagetype($img);

      if(is_file($imgThumb)) //thumb exists
        continue;

      if($imgExt === 'svg')
        $imgType = 'svg';

      switch($imgType) {
        case IMAGETYPE_JPEG: $tmpImg = imagecreatefromjpeg($img); break;
        case IMAGETYPE_PNG: $tmpImg = imagecreatefrompng($img); break;
        case IMAGETYPE_GIF: $tmpImg = imagecreatefromgif($img); break;
        case IMAGETYPE_BMP: $tmpImg = imagecreatefrombmp($img); break;
        case IMAGETYPE_WEBP:
                    $webpFile = fopen($img, 'rb');
                    $data = fread($webpFile, 90);
                    $webp_header = unpack('A4Riff/I1Filesize/A4Webp/A4Vp/A74Chunk', $data);
                    fclose($webpFile);
                    unset($webpFile); unset($data);
                    if(!isset($webp_header['Riff']) || strtoupper($webp_header['Riff']) !== 'RIFF' || !isset($webp_header['Webp']) || strtoupper($webp_header['Webp']) !== 'WEBP' || !isset($webp_header['Vp']) || strpos(strtoupper($webp_header['Vp']),'VP8') === false) //invalid webp
                      break;
                    if(strpos(strtoupper($webp_header['Chunk']), 'ANIM') !== false || strpos(strtoupper($webp_header['Chunk']), 'ANMF') !== false) { //is animated
                      copy($img, $imgThumb); //can not re-encode
                      break;
                    }
                    $tmpImg = imagecreatefromwebp($img);
                    if(strpos(strtoupper($webp_header['Chunk']), 'ALPH') !== false || strpos(strtoupper($webp_header['Vp']), 'VP8L') !== false) { //has alpha channel
                      imagepalettetotruecolor($tmpImg);
                      imagealphablending($tmpImg, true);
                      imagesavealpha($tmpImg, true);
                      $alpha = true;
                    }
                    break;
        case 'svg': copy($img, $imgThumb); break;
      }

      if(!$tmpImg) continue;
      list($x, $y) = getimagesize($img);

      if($x && $y && $x > $colWidth && $y > $rowHeight) {
        if($y > $x) {$h = -1; $w = $colWidth;}
        else        {$h = $rowHeight; $w = -1;}
        if($alpha)
          imagepng(imagescale($tmpImg, $w, $h, IMG_BICUBIC), $imgThumb); //scale and save thumbnail as png
        else
          imagejpeg(imagescale($tmpImg, $w, $h, IMG_BICUBIC), $imgThumb, 88); //scale and save thumbnail as jpg
      }
      else //too small to resize
        copy($img, $imgThumb);
    }
  }
}
?>

<!DOCTYPE html>
<html lang=en><head><meta charset=utf-8>
<title>Image Gallary Generator</title>
<link rel=icon href=/favicon.ico>
<meta name=theme-color content="#222">
<meta name=viewport content="width=device-width">

<style>
body {display:flex;flex-flow:row nowrap;justify-content:center;align-items:center;text-align:left;height:100vh;font-family:Arial;color:#F0F0F0;background-color:#202020;font-size:16px;line-height:1.5;margin:0;padding:0;}
div,h1,h2 {border:none;margin:0;padding:0;}
a {color:#FFF;text-decoration:none;}
:root {--primeColor:#5c275e;}
.container {background-color:#383838;border:3px solid var(--primeColor);border-radius:7px;}
h1 {display:block;font-size:30px;padding:8px 12px;background-color:var(--primeColor);}
h2 {display:block;font-size:22px;padding:0 12px;background-color:var(--primeColor);}
.main {box-shadow:0 0 8px 0px #000 inset;text-align:center;padding:16px 0;}
.albumLink {display:block;background-color:#444;padding:4px 10px;margin:6px;border-bottom:1px solid black;border-radius:5px;}
.albumLink:hover {background-color:#555;}
span {float:right;font-size:24px;line-height:24px;padding:0 2px;}
span:hover {color:#FF7777;}
</style></head>

<body>
<div class=container id=container>
  <h1>Image Gallery Generator</h1>
  <div class=main id=main>
    Image Directory <input type=text id=imgdir placeholder='/absolute/path/' pattern='[^\\"$]+' title='Disallowed Characters: \ " $' required><br>
    Album Name <input type=text id=albumName placeholder='Album Name' pattern="[^\\&quot;'$;<>?#]+" title="Disallowed Characters: \ / '' ' $ ; ? # < >" required><br>
    <input type=checkbox id=cb name=genthumbs value=1 checked=true><label for=cb> Generate Thumbnails</label>
    <input type=button id=sub onclick=newAlbum() value=Generate>
    <p id=msg></p>
  </div>
  <h2 id=albums>Albums</h2>

<?php
  //list existing albums
  if(is_dir('galleries') && $dir = opendir('galleries')) {
    while($folder = readdir($dir))
      if(is_dir('galleries/'.$folder) && $folder !== '.' && $folder !== '..')
        echo '<a href="./?album='.$folder.'/" class=albumLink>'.$folder.'<span onclick=\'removeAlbum("'.$folder.'")\'>&#128465;</span><span onclick=\'refreshThumbs("'.$folder.'")\'>&#8635;</span></a>';
    closedir($dir);
  }
  else echo '<i style="display:block;text-align:center;">none</i>';
?>

</div>
</body>

<script>
//CREATE ALBUM
function newAlbum() {
  var _imgdir = imgdir.value.trim();
  var _albumName = albumName.value.trim();
  var gt = cb.checked ? 1 : 0;
  if(!_imgdir || !_albumName) return;
  sub.disabled = true;

 var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      msg.textContent = 'Album Added: ' + _albumName;
      if(gt) msg.innerHTML += '<br>Thumbnails generating in the background (on the server)';
      //new album tile
      var newAlbumLink = document.createElement('a');
      newAlbumLink.classList.add('albumLink');
      newAlbumLink.href = './?album=' + _albumName + '/';
      newAlbumLink.innerHTML = _albumName + '<span onclick=\'removeAlbum("' + _albumName + '")\'>&#128465;</span>';
      albums.insertAdjacentElement('afterend', newAlbumLink);
    }
    else msg.textContent = 'Error: ' + this.status + "\n" + this.responseText;
    sub.disabled = false;
  }

  xhttp.open('GET', '?newAlbum=' + _albumName + '&imgdir=' + _imgdir + '&generateThumbs=' + gt, true);
  xhttp.send();
}

//REFRESH THUMBS
function refreshThumbs(_albumName) {
  event.preventDefault();
  var targetRefreshIcon = event.target;
  targetRefreshIcon.remove();

  var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      targetRefreshIcon.remove();
      msg.textContent = 'Thumbnails generating in the background (on the server): ' + _albumName;
    }
    else msg.textContent = 'Error: ' + this.status + "\n" + this.responseText;
  }

  xhttp.open('GET', '?refreshThumbs=' + _albumName, true);
  xhttp.send();
}

//REMOVE ALBUM
function removeAlbum(_albumName) {
  event.preventDefault();
  var targetDeleteIcon = event.target;

  var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      msg.textContent = 'Album Deleted: ' + _albumName;
      targetDeleteIcon.parentElement.remove();
    }
    else msg.textContent = 'Error: ' + this.status + "\n" + this.responseText;
  }

  xhttp.open('GET', '?removeAlbum=' + _albumName, true);
  xhttp.send();
}

</script>
</html>
