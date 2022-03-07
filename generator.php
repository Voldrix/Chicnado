<?php
if(array_key_exists('removeAlbum',$_POST)) { //remove album
  if(file_exists('galleries/'.$_POST['removeAlbum']) && is_link('galleries/'.$_POST['removeAlbum']))
    unlink('galleries/'.$_POST['removeAlbum']);
  if(file_exists('thumbnails/'.$_POST['removeAlbum'])) {
    if(is_link('thumbnails/'.$_POST['removeAlbum']))
      unlink('thumbnails/'.$_POST['removeAlbum']);
    else {
      foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('thumbnails/'.$_POST['removeAlbum'],RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        if($file->isDir()) rmdir($file->getRealPath());
        else unlink($file->getRealPath());
      }
      rmdir('thumbnails/'.$_POST['removeAlbum']);
    }
  }
  exit();
}

if(array_key_exists('imgdir',$_POST) && $_POST['refresh'] === 'false') { //create album
  $imgDir = str_replace(array('\\','"','$'),'',$_POST['imgdir']);
  $albumName = trim(str_replace(array('\\','/','"','$',';','?','#','<','>'),'',$_POST['aname']));
  $genThumbs = (array_key_exists('genthumbs',$_POST) && $_POST['genthumbs'] == 1) ? true : false;
  if(substr($imgDir,-1) === '/') $imgDir = substr($imgDir,0,-1);

  clearstatcache();
  if(!empty($albumName) && is_dir($imgDir) && !is_dir('thumbnails/'.$albumName) && !is_dir('galleries/'.$albumName)) {
    if(!is_dir('galleries')) mkdir('galleries',0777);
    if(!is_dir('thumbnails')) mkdir('thumbnails',0777);
    symlink($imgDir,'galleries/'.$albumName);
    if($genThumbs) { //thumbnails
      mkdir('thumbnails/'.$albumName,0777);
      if(file_exists('thumbnails/'.$albumName)) makeThumbs($imgDir,'thumbnails/'.$albumName);
      exit();
    }
    else //symlink instead of thumbs
      symlink($imgDir,'thumbnails/'.$albumName);
  }
}

if(isset($_POST['refresh']) && $_POST['refresh'] !== 'false') { //refresh album
  $albumName = trim(str_replace(array('\\','/','"','$',';','?','#','<','>'),'',$_POST['refresh']));
  $imgDir = 'galleries/'.$albumName;
  if(substr($imgDir,-1) === '/') $imgDir = substr($imgDir,0,-1);
  clearstatcache();
  if(empty($albumName) || !is_dir($imgDir) || is_link('thumbnails/'.$albumName)) exit();
  if(file_exists('thumbnails/'.$albumName)) makeThumbs($imgDir,'thumbnails/'.$albumName); //thumbnails
  ///delete unneeded thumbnails
  foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('thumbnails/'.$albumName,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $img) {
    if(is_file($img)) {
      if(!is_file($imgDir.'/'.$iterator->getSubPathname())) unlink($img);
    }
  }
  foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('thumbnails/'.$albumName,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $img) {
    if(is_dir($img)) {
      if(!(new FilesystemIterator($img))->valid()) rmdir($img);
    }
  }
  exit();
}

function makeThumbs($source,$dest) { //make thumbnails
  set_time_limit(0);
  foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $img) {
    if(is_dir($img)) {
      if(!is_dir($dest.'/'.$iterator->getSubPathname())) mkdir($dest.'/'.$iterator->getSubPathname());}
    else {
      $imgThumb = $dest.'/'.$iterator->getSubPathname();
      if(is_file($imgThumb)) continue;
      $imgExt = strtolower(pathinfo($imgThumb,PATHINFO_EXTENSION));
      $supportedFormats = ['bmp','jpg','jpeg','png','gif','webp','svg'];
      if(in_array($imgExt,$supportedFormats,true)) {
        list($x,$y) = getimagesize($img);
        if($imgExt !== 'svg' && $y > 268) {
          switch($imgExt) {
            case 'jpeg':
            case 'jpg': $tmpImg = imagecreatefromjpeg($img); break;
            case 'png': $tmpImg = imagecreatefrompng($img); break;
            case 'gif': $tmpImg = imagecreatefromgif($img); break;
            case 'bmp': $tmpImg = imagecreatefrombmp($img); break;
            case 'webp':
                         $webpFile = fopen($img,'rb');
                         $data = fread($webpFile,90);
                         $webp_header = unpack('A4Riff/I1Filesize/A4Webp/A4Vp/A74Chunk',$data);
                         fclose($webpFile); unset($webpFile); unset($data);
                         if(!isset($webp_header['Riff']) || strtoupper($webp_header['Riff']) !== 'RIFF' || !isset($webp_header['Webp']) || strtoupper($webp_header['Webp']) !== 'WEBP' || !isset($webp_header['Vp']) || strpos(strtoupper($webp_header['Vp']),'VP8') === false) {
                           echo $img.'<br>'; continue 2;} //invalid webp
                         if(strpos(strtoupper($webp_header['Chunk']),'ANIM') !== false || strpos(strtoupper($webp_header['Chunk']),'ANMF') !== false) { //is animated
                           copy($img,$imgThumb); //can't re-encode these
                           continue 2;}
                         $tmpImg = imagecreatefromwebp($img);
                         if(strpos(strtoupper($webp_header['Chunk']),'ALPH') !== false || strpos(strtoupper($webp_header['Vp']),'VP8L') !== false) { //has alpha channel
                           imagepalettetotruecolor($tmpImg);
                           imagealphablending($tmpImg,true);
                           imagesavealpha($tmpImg,true);
                           imagepng(imagescale($tmpImg,-1,268,IMG_BICUBIC),$imgThumb); //save as png
                           imagedestroy($tmpImg);
                           continue 2;}
                         break; //else into jpg like normal
          }
          if($tmpImg)
            imagejpeg(imagescale($tmpImg,floor((268/$y)*$x),268,IMG_BICUBIC),$imgThumb,80); //scale and save thumbnail
          else echo $img.'<br>';
          unset($tmpImg);
        }
        else //too small to resize
          copy($img,$imgThumb);
        echo '                                          '; //keep connection open (arbitrary string long enough to frequently fill 4k output buffer)
      }
      //else not a supported format. we could copy the img over without resizing, but skipping these saves us from non-images
    }
  }
}
?>
<!DOCTYPE html>
<html lang=en><head><meta charset=utf-8 />
<title>Image Gallary Generator</title>
<link rel=icon href=/favicon.ico /><meta name=theme-color content="#222">
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
</style>
</head><body><div class=container id=container>
<h1>Image Gallery Generator</h1>
<div class=main id=main>
<form id=albumform method=post onsubmit=event.preventDefault();newAlbum()>
Image Directory<input type=text id=imgdir placeholder='/absolute/path/' pattern='[^\\\x22$]+' title='Disallowed Characters: \ " $' required name=imgdir /><br>
Album Name<input type=text id=aname placeholder='Album Name' pattern='[^\\/\x22\x27$;<>?#]+' title="Disallowed Characters: \ / '' ' $ ; ? # < >" required name=aname /><br>
<input type=checkbox id=cb name=genthumbs value=1><label for=cb> Generate Thumbnails</label>
<input type=submit id=sub value=Generate>
</form>
<p id=msg></p>
</div>
<h2 id=albums>Albums</h2>
<?php
if(is_dir('galleries') && $dir = opendir('galleries')) { //list existing albums
  while($folder = readdir($dir))
    if(is_dir('galleries/'.$folder) && $folder !== '.' && $folder !== '..')
      echo '<a href="index.php?album='.$folder.'/" class=albumLink name="'.$folder.'">'.$folder.'<span onclick=\'event.preventDefault();removeAlbum("'.$folder.'")\'>&#128465;</span><span onclick=\'event.preventDefault();newAlbum("'.$folder.'")\'>&#8635;</span></a>';
  closedir($dir);
}
else echo '<i style="display:block;text-align:center;">none</i>';
?>
</div>
<script>
function newAlbum(refresh=false) { //create or refresh album
  document.getElementById('sub').disabled = true;
  var imgdir = document.getElementById('imgdir').value.trim();
  var aname = document.getElementById('aname').value.trim();
  var cb = (document.getElementById('cb').checked) ? 1 : 0;

  if(cb === 0 && !refresh) {document.getElementById('albumform').submit(); return true;}

  var errbox = document.getElementById('container');
  errbox.innerHTML += '<h2>Failed Images</h2>';

  fetch(location.toString(), {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-store'},
    body: 'imgdir='+encodeURIComponent(imgdir)+'&aname='+encodeURIComponent(aname)+'&genthumbs='+cb+'&refresh='+refresh
  })
  .then(async (response) => {
    const decoder = new TextDecoder();
    const reader = response.body.getReader();
    while(true) {
      const {value, done} = await reader.read();
      if(done) break;
      errbox.innerHTML += decoder.decode(value,{stream: true}).trim(); //append img transcoding errors
    }
    reader.releaseLock();
    document.getElementById('msg').innerText = 'Done!';
    document.getElementById('sub').disabled = false;
    if(!refresh) {
      var newAlbumLink = document.createElement('a'); //new album tile
      newAlbumLink.setAttribute('class','albumLink');
      newAlbumLink.setAttribute('href','index.php?album='+aname+'/');
      newAlbumLink.setAttribute('name',aname);
      newAlbumLink.innerHTML = aname + '<span onclick=\'event.preventDefault();removeAlbum("'+aname+'")\'>&#128465;</span><span onclick=\'event.preventDefault();newAlbum("'+aname+'")\'>&#8635;</span>';
      document.getElementById('albums').insertAdjacentElement('afterend',newAlbumLink);
    }
  })
  .catch(err => document.getElementById('msg').innerHTML = 'Connection failed<br>'+err+'<br><br>(possibly nginx/apache timeout. see readme.)');

  document.getElementById('msg').innerHTML = 'This may take several minutes or longer if you have a large collection.<br>Do not close this page';
  return false;
}

function removeAlbum(album) { //remove album
  fetch(location.toString(), {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-store'},
    body: 'removeAlbum='+encodeURIComponent(album)
  })
  .then(document.querySelector('a[name="'+album+'"]').remove())
  .catch(err => alert('Remove failed\nERROR: '+err));
}
</script></body></html>
