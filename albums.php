<!DOCTYPE html>
<html lang=en><head><meta charset=utf-8 />
<title>Image Gallary</title>
<link rel=icon href=/favicon.ico /><meta name=theme-color content="#222">
<meta name=viewport content="width=device-width">
<style>
body {display:flex;flex-flow:row nowrap;justify-content:center;align-items:center;text-align:left;height:100vh;font-family:Arial;color:#F0F0F0;background-color:#202020;font-size:16px;line-height:1.5;margin:0;padding:0;}
div,h1 {border:none;margin:0;padding:0;}
a {color:#FFF;text-decoration:none;}
:root {--primeColor:#5c275e;}
.container {background-color:#383838;border:3px solid var(--primeColor);border-radius:7px;}
h1 {display:block;font-size:30px;padding:8px 12px;background-color:var(--primeColor);}
.albumLink {display:block;background-color:#444;padding:4px 10px;margin:6px;border-bottom:1px solid black;border-radius:5px;}
.albumLink:hover {background-color:#555;}
</style>
</head><body><div class=container>
<h1>Albums</h1>
<?php
if(is_dir('galleries') && $dir = opendir('galleries')) { //list existing albums
  while($folder = readdir($dir))
    if(is_dir('galleries/'.$folder) && $folder !== '.' && $folder !== '..')
      echo '<a href="index.php?album='.$folder.'/" class=albumLink>'.$folder.'</a>';
  closedir($dir);
}
else echo '<i style="display:block;text-align:center;">none</i>';
?>
</div></body></html>

