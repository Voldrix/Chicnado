<?php
//GET ALBUMS
if(empty($_GET['album'])) {

  if($dir = opendir('galleries')) { //list existing albums
    while($folder = readdir($dir))
      if(is_dir('galleries/'.$folder) && $folder !== '.' && $folder !== '..')
        $albums[] = $folder;
    closedir($dir);
  }
  echo json_encode($albums);
}


//GET DIR
else {
  $album = 'galleries/' . str_replace(array('\\','"','$'), '', $_GET['album']);
  if(substr($album, -1) !== '/') $album .= '/';
  $filter = isset($_GET['filter']) ? $_GET['filter'] : false;

  if(!is_dir($album)) {
    http_response_code(403);
    exit();
  }

  $res = new stdClass();
  $res->dirs = array();
  $res->files = array();

  $dir = opendir($album);

  while($file = readdir($dir)) {
    if($file === '.' || $file === '..') continue;

    if(is_dir($album.$file)) //dir
      array_push($res->dirs, (object) array("name" => $file, "mtime" => filemtime($album.$file), "size" => filesize($album.$file)));
    else { //file
      if($filter) {
        list($x,$y,$type) = getimagesize($album.$file);
        if(!$type || $type === 5 || ($filter === 'land' && $x < $y) || ($filter === 'port' && $x > $y)) continue;
      }
      $res->files[] = (object) array("name" => $file, "mtime" => filemtime($album.$file), "size" => filesize($album.$file), "valid" => 1);
    }
  }

  closedir($dir);

  echo json_encode($res);
}
?>
