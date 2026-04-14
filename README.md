# Chicnado
### _Minimalist, pure PHP, static image gallery_
__Seamless integration into any site, with no branding of its own.__

Requires: php-gd extension (if you generate thumbnails).\
Install: `apt-get install php-gd` 

## Features
- Seamless integration (no branding, does not look like a separate app)
- No external libraries
- Optional thumbnail generation
- Simple vanilla code base
- Read-only interface has no UI clutter
- Multiple Albums
- Sort and filter (mod time, file size, alphabetical, random, unordered) (portrait, landscape)

## Instructions
Just clone the repo into a folder on your web server. Then go to that URL for setup.\
After you've added your first gallery, you'll have to go to the `generator.php` page manually to add or remove galleries.\
Removing a gallery will remove the symlink and delete any generated thumbnails, but not touch the originals.

### Controls
- `P` Play / Pause image carousel (5s interval)
- `F` Fullscreen / unFullscreen
- `ESC` unFullscreen / Close image
- `Left` Previous image
- `Right` `Space` `Enter` Next image
- `Click` zoom image
- `Ctrl` + `Click` super zoom image

### Security
After you've added your galleries, __delete generator.php__\
You can always re-downloaded it, but if you leave it on there, someone could create a gallery to any directory and access your files.

## How it Works
Galleries are symlinks to the image directory.\
Thumbnails are stored in the same directory as Chicnado.

Images added since the last thumbnail generation will have a magenta border around them and use the full size image as the thumbnail.

Thumbnail formats supported: gif, jpg, png, bmp, webp

SVGs and images smaller than the thumbnail size will be copied -not transcoded- into a thumbnail.

The file list is read from the source folder, so any changes will show up instantly.

Non-images display as blank with a red border.

## Pictures
The top left breadcrumb pagination starts with the album name.\
Below it are the subdirectories.\
![Chicnado sample gallery](https://i.imgur.com/9CaDQE0.png)
![Chicnado sample fullview](https://i.imgur.com/boaXKBV.png)
![Chicnado sample generator](https://i.imgur.com/agqMSrV.png)

### Live Demo
[https://voldrixia.com/chicnado/](https://voldrixia.com/chicnado/)

### License
[MIT License](LICENSE.txt)
