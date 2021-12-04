# Chicnado
### _Minimalist, pure PHP, static image gallery_
No external libraries!\
Only uses php-gd extension (if you generate thumbnails).\
Install: `apt-get install php-gd` 

## Features
- __No external libraries!__
- Seamless integration (no branding, doesn't look like a separate app)
- Optional thumbnail generation
- Minimal code base (easy to edit)
- Read-only interface has no UI clutter
- Multiple Albums
- Sort and filter (mod time, file size, alphabetical, random, unordered) (portrait, landscape)
- Auto resizing to fill rows evenly

## Instructions
Just clone the repo into a folder on your web server. Then go to that URL for setup.\
After you add your first gallery, you'll have to go to the `generator.php` page to add or remove galleries.\
Removing a gallery will remove the symlink and delete any generated thumbnails, but not touch the originals.

### Controls
- `P` Play / Pause image carousel (5s interval)
- `F` Fullscreen / unFullscreen
- `ESC` unFullscreen / Close image
- `Left` Previous image
- `Right` Next image
- `Any` Next image

On mobile there is a swipe gesture for cycling the fullview image.\
There are also button controls for the fullview image viewer. Clicking the grayed-out background also closes the image.\
You can manually cycle images while the carousel is on.

### Nginx timeout
If thumbnail generation takes longer than 5min, nginx may close the connection. Add this setting to your site's config to increase the timeout to one hour.\
`proxy_read_timeout 3600;`

PHP will not timeout, because the timeout is overwritten in the code.

### Security
After you've added your galleries, __delete generator.php__\
You can always re-downloaded it, but if you leave it on there, someone could use it to access any files your web server user has access to.

## How it Works
The generator will create two directories, _galleries_ and _thumbnails_. Every gallery you make will be a symlink under _galleries_. If you generate thumbnails, they will be in a folder by the same name under _thumbnails_. Otherwise it will be a duplicate symlink under _thumbnails_.

If there is no thumbnail for an image, the viewer will use the original for the thumbnail. This can happen if the image has the wrong file type extension, or the thumbnail was deleted, or a new image was added to the source folder. You will be able to identify these issues because the image will be given a <u>red border</u>.

The viewer gets the file list from the source folder, so any changes you make will show up instantly.

Any non-images in the source directory will show up as broken images.

Images shorter than the thumbnail height (default 268px) will be copied -not transcoded- into a thumbnail.

## Pictures
The top left breadcrumb pagination starts with the album name.\
Below it are the subdirectories.\
![Chicnado sample gallery](https://i.imgur.com/9CaDQE0.png)
![Chicnado sample fullview](https://i.imgur.com/boaXKBV.png)
![Chicnado sample generator](https://i.imgur.com/agqMSrV.png)

### License
[MIT License](LICENSE.txt)
