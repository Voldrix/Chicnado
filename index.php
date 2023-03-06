<?php
if(array_key_exists('album',$_GET) && !empty($_GET['album'])) {
  $album = str_replace(array('\\','"','$',';','?','#','<','>'),'',$_GET['album']);
  if(substr($album,-1) !== '/') $album.='/';
}
else { //album not specified
  if($dir = opendir('galleries')) { //list existing albums
    while($folder = readdir($dir))
      if(is_dir('galleries/'.$folder) && $folder !== '.' && $folder !== '..')
        $albums[] = $folder;
    closedir($dir);
  }
  if($albums) {
    if(count($albums) === 1)
      $album = $albums[0].'/'; //only album
    else {require('albums.php');exit();} //multiple albums
  }
  else {require('generator.php');exit();} //no albums
}
$path = explode('/',substr($album,0,-1));
echo '<!DOCTYPE html>
<html lang=en><head><meta charset=utf-8 />
<link rel=stylesheet href=style.css />
<title>Image Gallary</title>
<link rel=icon href=/favicon.ico /><meta name=theme-color content="#222">
<meta name=viewport content="width=device-width">
</head><body><div class=header>';
foreach($path as $dir) {$x.=$dir.'/'; echo '<a href="?album='.$x.'">'.$dir.'</a> / ';} //breadcrumb pagination
echo '<select onchange="sortFilter(this.value)"><option value=def>Sort / Filter</option><option value=def>Default</option><option value=alpha>Alphabetical</option><option value=mod>Newest</option><option value=size>Filesize</option><option value=rand>Random</option><option value=port>Portrait</option><option value=land>Landscape</option></select></div>'; //sort and filter select menu

if(is_dir('galleries/'.$album)) { //get list of images
  $list = [];
  if(array_key_exists('saf',$_GET)) //sort and filter
    sortAndFilter($_GET['saf'],$list,'galleries/'.$album);
  else {
    $list = scandir('galleries/'.$album); //unsorted
    unset($list[array_search('.',$list,true)]); unset($list[array_search('..',$list,true)]); // . & ..
  }
  echo '<div class=dirs>';
  foreach ($list as $img) { //Iterate each directory
    if(is_dir('galleries/'.$album.$img))
      echo '<a href="?album='.$album.$img.'/">'.$img.'</a>';
  }
  $imgNum=1;
  echo '</div><div class=pics>';
  foreach ($list as $img) { //Iterate each image
    if(is_file('galleries/'.$album.$img)) {
      echo '<div><img class=YvY src="thumbnails/'.$album.$img.'" onclick=viewer(\'flex\','.$imgNum.') onerror=\'this.onerror=null;this.src="galleries/'.$album.$img.'";this.style.border="6px solid #BB0000"\' /><span>'.$img.'</span></div>';
      $imgNum++;
    }
  }
}
echo '</div><div class=viewer id=vwr><img id=fullViewImg /><span fullscreen onclick=fullscreen()>&#8689;</span><span previous onclick=turnPage(-1)>&#8249;</span><span next onclick=turnPage(1)>&#8250;</span><span close onclick=fullscreen(0);viewer("none")>&times;</span><span carousel onclick=pausePlay()>&#10157;</span></div>
<script src=scripts.js></script></body></html>'; //viewer control buttons

function sortAndFilter(string $_saf, array &$_list, string $_album) : void { //Sort and Filter
  function getFiles(string $gf_album, array &$gf_list) : void { //Get File List
    if(is_dir($gf_album) && $_dir=opendir($gf_album)) {
      while ($gf_list[]=readdir($_dir)) continue;
      closedir($_dir);
      unset($gf_list[array_search('.',$gf_list,true)]); unset($gf_list[array_search('..',$gf_list,true)]); array_pop($gf_list);
    }
  }
  switch($_saf) {
    case 'mod': $c=999; //file modified time DESC
                if(is_dir($_album) && $_dir=opendir($_album)) {
                  while ($file=readdir($_dir)) {$_list[filemtime($_album.$file).$c]=$file; $c--;}
                  closedir($_dir); unset($_list[array_search('.',$_list,true)]); unset($_list[array_search('..',$_list,true)]);
                  krsort($_list,SORT_NUMERIC);}
                break;
    case 'size': $c=999; //file size DESC
                 if(is_dir($_album) && $_dir=opendir($_album)) {
                   while ($file=readdir($_dir)) {$_list[filesize($_album.$file).$c]=$file; $c--;}
                   closedir($_dir); unset($_list[array_search('.',$_list,true)]); unset($_list[array_search('..',$_list,true)]);
                   krsort($_list,SORT_NUMERIC);}
                 break;
    case 'land': if(is_dir($_album) && $_dir=opendir($_album)) { //landscape filter
                   while ($file=readdir($_dir)) {
                      if(is_dir($_album.$file)) $_list[] = $file;
                      else {list($x,$y) = getimagesize($_album.$file); if($x > $y) $_list[] = $file;}}
                   closedir($_dir); unset($_list[array_search('.',$_list,true)]); unset($_list[array_search('..',$_list,true)]);}
                 break;
    case 'port': if(is_dir($_album) && $_dir=opendir($_album)) { //portrait filter
                   while ($file=readdir($_dir)) {
                     if(is_dir($_album.$file)) $_list[] = $file;
                     else {list($x,$y) = getimagesize($_album.$file); if($x < $y) $_list[] = $file;}}
                   closedir($_dir); unset($_list[array_search('.',$_list,true)]); unset($_list[array_search('..',$_list,true)]);}
                 break;
    case 'alpha': getFiles($_album,$_list); sort($_list,SORT_NATURAL|SORT_FLAG_CASE); break; //alphabetical ASC
    case 'rand': getFiles($_album,$_list); shuffle($_list); break; //random order
    default: getFiles($_album,$_list); //unordered
  }
}
?>
