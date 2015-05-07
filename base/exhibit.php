<?php 
/* * * * * * * CONFIGURATION  * * * * * * * * * * * * * * * * * * * * * * * * */

require_once('config.php');
require_once('base.php');


// Database connection settings sent to PEAR 
define('DB_TABLE_GALLERY', 'exh_dimensions');

// Imagemagick's `convert` is used for image resampling
define('CONVERT_BIN', '/usr/bin/convert'); // Path to executable
define('CONVERT_OPTIONS', '-quality 80 -density 72x72');

// HTTP path to the gallery script
define('EXHIBIT', WEBROOT.'img'); 

// HTTP path to the admin script
define('EXHIBIT_ADMIN', WEBROOT.'admin/images/edit'); 

// HTTP path to the root directory of the whole website, in case we need to
// link to other parts of a website
define('EXHIBIT_WEBROOT', WEBROOT); 

// Directory with your images in (default)
define('LIBPATH', EXH_LIBRARY); // defined externally

// The cache must be both readable and writable by the web server.
define('CACHEPATH', EXH_CACHE);
define('LOG_ENABLED', true);

/* * * * * * * Non-host specific configuration  * * * * * * * * * * * * * * * */

// Default image sizes. Overwritten by gallery specific settings
// ** Moved these constants to application.php **
// define('IMG_THUMB_SIZE', 170);
// define('IMG_STD_SIZE', 350);

// Browse view stuff
define('BROWSE_PAGE_X', 4); // Images across the page (columns)
define('BROWSE_PERPAGE', 999); // Total number of items on a page

// Recurse mode - show all images in a folder hierarchy on one page, or
// provide explorer like interface? Values can be FLAT or TREE.
// FLAT means all the images from the specified path will be shown on the same
// page, in a categorised manner. TREE means you only see the images from the
// current path, although you can "browse" into subdirectories.
define('RECURSE_MODE', 'FLAT');

// DB_CONNECT_STR is defined externally

/* * * * * * * CONFIGURATION FINISHED * * * * * * * * * * * * * * * * * * * * */

/*
 * ImageSize
 * simple class to hold dimensions of an image size
 */
class ImageSize {
	function __construct($obj) {	
		$this->id = $obj->id;
		$this->width = $obj->width;
		$this->height = $obj->height;
		$this->size_string = $obj->size;
		$this->crop = $obj->crop;
		$this->gravity = "center";
		$this->modulate = $obj->modulate;
		$this->forcewidth = $obj->forcewidth;   // takes priority over forceheight
		$this->forceheight = $obj->forceheight;
		$this->geometry_string = $this->width.'x'.$this->height;
	}
	function getImageMagickOptions($fullpath) {
		if ($this->forcewidth) {

            $imageinfo = getimagesize($fullpath);
            $x = $imageinfo[0];
            $y = $imageinfo[1];

            if ($x >= $y) {
                // Landscape. Imagemagick will take care of it, so pass equal figures
                $x = $this->width;
                $y = $this->height;
            } else {
                // Portrait. We need to specify the geometry
                $ratio = $y/$x;
                $x = $this->width;
                $y = round($x*$ratio);
            }

            $options = "-geometry ${x}x${y}";
		} elseif ($this->forceheight) {
			$imageinfo = getimagesize($fullpath);
            $x = $imageinfo[0];
            $y = $imageinfo[1];

			$req_ratio = $this->height / $this->width;
			$exist_ratio = $y / $x;

            if ($exist_ratio >= $req_ratio ) {
                // existing image is more portraity than target dimensions
				// just scale to required height.
                $x = round((1/$exist_ratio) * $this->height);
                $y = $this->height;
				$options = "-geometry ${x}x${y}";
            } else { 
                // existing image is more landscapy.
				$options = "-geometry x{$this->height} -gravity {$this->gravity} -crop {$this->width}x{$this->height}+0+0";
            }   
        } elseif ($this->crop) {

            $target_x = $this->width;
            $target_y = $this->height; //round($size / 0.144);

            $imageinfo = getimagesize($fullpath);
            $x = $imageinfo[0];
            $y = $imageinfo[1];

            $target_ratio = $target_x / $target_y;
            $ratio = $x / $y;

            if ($ratio >= $target_ratio) {
                // for images more landscapy than our target dimensions they must be resampled to be the right height before cropping
                $options = "-geometry x${target_y} -gravity {$this->gravity} -crop ${target_x}x${target_y}+0+0";
            } else {
                // for images more portraity than our target dimensions they must be resampled to be the right width before cropping
                $options = "-geometry ${target_x}x -gravity {$this->gravity} -crop ${target_x}x${target_y}+0+0";
            }
        } else {
            $options = "-geometry {$size->width}x{$size->height}";
        }
		if ($this->modulate) $options .= " -modulate {$this->modulate} ";

		return $options;
	}
	function toString() {
		$vars = array();
		foreach (get_object_vars($this) as $k => $v) $vars[] = "$k = '$v'";
		return implode(', ',$vars);
	}
}

class Exhibit {

	var $exhroot;
	var $webroot;
	var $libpath;
	var $cachepath;
	var $convert;
	var $convertOptions;
	var $imgThumbSize;
	var $imgStdSize;
	var $isAdmin;
	var $recurseMode;

	// Sub-objects
	var $f;
	var $i;
	var $out;
	var $url;
	var $db;

	// Initialisation
	function Exhibit() {
		$this->setExhRoot();
		$this->setWebRoot(EXHIBIT_WEBROOT);
		$this->setLibPath(LIBPATH);
		$this->setCachePath(CACHEPATH);
		$this->setConvert(CONVERT_BIN);
		$this->setConvertOptions(CONVERT_OPTIONS);
		$this->setImgStdSize(IMG_STD_SIZE);
		$this->setImgThumbSize(IMG_THUMB_SIZE);
		$this->setRecurseMode(RECURSE_MODE);

		$this->f =& new ExhibitFile($this);
		$this->i =& new ExhibitImgLib($this);
		$this->url =& new ExhibitURL($this);
		$this->db =& new MyDB(); //fixme! yuk

		if ($this->isAdmin()) {
			$this->out =& new ExhibitAdminOutput($this);
		} else {
			$this->out =& new ExhibitOutput($this);
		}
	}

	function setRecurseMode($mode) {
		$this->recurseMode = $mode;
	}

	function getRecurseMode() {
		return $this->recurseMode;
	}

	function setImgStdSize($imgStdSize) {
		$this->imgStdSize = $imgStdSize;
	}

	function setImgThumbSize($imgThumbSize) {
		$this->imgThumbSize = $imgThumbSize;
	}

	// Determine the size an image should be, based on the gallery-specific
	// setting from the database or the default specified in this script.
	function getImgSize($path=NULL, $size=NULL) {

		if (empty($path)) return $this->imgThumbSize;

		$gallery = $this->escape($this->getGallery($path));
		$sql = 
			"SELECT * FROM ".DB_TABLE_GALLERY.
			" WHERE label = '$gallery' AND size = '$size' LIMIT 1";

		$res = $this->db->query($sql, "Failed checking standard image size");

		if ($res->numrows()!==1) return false;

		$row = $res->fetchrow(DB_FETCHMODE_OBJECT);

		return new ImageSize($row);

		/*switch ($size) {
			case 'THUMB':
				if ($row->thumbsize != 0)
					return $row->thumbsize;
				else
					return $this->imgThumbSize; // None set; default
			case 'STANDARD':
				if ($row->stdsize != 0)
					return $row->stdsize; // Gallery specific standard size
				else
					return $this->imgStdSize; // None set; default
			default:
				return $size;
		} */
	}

	function setExhRoot() {
		// If the user can access EXHIBIT_ADMIN via basic HTTP level
		// authentication, show them administrative functions.
		if (strstr($_SERVER['PHP_SELF'], $this->stripTrailingSlash(EXHIBIT_ADMIN))) {
			$this->exhroot = $this->stripTrailingSlash(EXHIBIT_ADMIN);
			$this->setIsAdmin(true);
		} else {
			$this->exhroot = $this->stripTrailingSlash(EXHIBIT);
			$this->setIsAdmin(false);
		}
	}

	function setIsAdmin($admin=false) {
		$this->isAdmin = $admin;
	}

	function isAdmin() {
		return $this->isAdmin;
	}

	function setWebRoot($path) {
		$this->webroot = $this->stripTrailingSlash($path);
	}

	function getWebRoot() {
		return $this->webroot;
	}

	function getExhRoot() {
		return $this->exhroot;
	}

	function setLibPath($path) {
		if (! is_readable($path)) {
			die('The image library is not accessible. Check your configuration and directory permissions.');
		}
		$this->libpath = $this->stripTrailingSlash($path);
	}

	function getLibPath() {
		return $this->libpath;
	}

	function setCachePath($path) {
		if (! is_writable($path) or (! is_readable($path))) {
			die('The cache path is not usable. Check your configuration and directory permissions.');
		}
		$this->cachepath = $this->stripTrailingSlash($path);
	}

	function getCachePath() {
		return $this->cachepath;
	}

	function setConvert($path) {
		if (! function_exists('is_executable')) {
			die('Unable to test if convert is installed');
		} else {
			if (! is_executable($path)) {
				die('The convert location is not valid. Check your configuration.');
			}
			$this->convert = $path;
		}
	}

	function getConvert() {
		return $this->convert;
	}

	function setConvertOptions($options) {
		$this->convertOptions = $options;
	}

	function getConvertOptions() {
		return $this->convertOptions;
	}

	function convert($args, $infile, $outfile) {
		$args = $this->getConvertOptions() . ' ' . $args;
		// Run convert. exec() doesn't give us any output.
		exec("\"".$this->getConvert()."\" $args \"$infile\" \"$outfile\"");
		if (is_writable($outfile)) {
			chmod($outfile, 0777);
			return true;
		} else {
			return false;
		}
	}

	// Return the relative path (to libPath) for a file inside it, given a full
	// system path to start
	function relPath($file, $stripTrailingSlash=true) {
		$out = substr($file, strlen($this->getLibPath()));
		// Don't want leading slashes on the paths we use
		if ($out[0]=='/') $out = substr($out, 1);
		if ($stripTrailingSlash===true)
			$out = $this->stripTrailingSlash($out);
		return $out;
	}

	// Return the value minus a trailing slash/, if it started with one.
	function stripTrailingSlash($val) {
		if (substr($val, -1)=='/') {
			return substr($val, 0, -1);
		} else {
			return $val;
		}
	}

	// Determine the root level gallery of the given path
	function getGallery($path) {
		if (strstr($path, '/')) {
			$bits = explode('/', $path);
			return $this->stripTrailingSlash($bits[0]);
		} else {
			return $path;
		}
	}

	function escape($str) {
		return mysql_escape_string($str);
	}

	function uriencode($str) {
		$str = rawurlencode($str);
		$str = str_replace('%2F', '/', $str);
		$str = str_replace('#', '^H^', $str);
		$str = str_replace('%23', '^H^', $str);
		return $str;
	}

	function uridecode($str) {
		$str = str_replace('^H^', '%23', $str);
		$str = str_replace('^H^', '#', $str);
		$str = str_replace('/', '%2F', $str);
		$str = rawurldecode($str);
		return $str;
	}
}

class ExhibitImgLib {

	var $exh;

	function ExhibitImgLib(&$exh) {
		$this->exh =& $exh;
	}

	// Return the filesystem location of a cached copy of the requested image
	// at a requested size
	function get($image, $size, $forcewidth=false, $crop=false,$desaturate=false) {
		
		// First confirm the image request exists in the library
		if ($this->libCheck($image, $size)) {
			// If it exists, then see if we can get it from the cache
			if ($location = $this->cacheCheck($image, $size, $forcewidth, $crop, $desaturate)) {
				return $location;
			} else {
				_log("Cache STORED $image, {$size->toString()}");
				if ($location = $this->cacheStore($image, $size, $forcewidth, $crop, $desaturate)) {
					_log("Cache STORED $image");
					return $location;
				} else {
					return false;
				}
			}
		} else {
			_log("LIBRARY NOT_FOUND $image");
			return false;
		}
		
	}

	// Check for the existance of an image in the library. Even though we may
	// hold a cached copy of recently deleted material, don't show it.
	function libCheck($image) {
		$path = $this->exh->getLibPath().'/'.$image;
		if (is_file($path) and is_readable($path)) {
			return true;
		} else {
			return false;
		}
	}

	// Resize and store a copy of an image in the cache
	function cacheStore($image, $size, $forcewidth=false, $crop=false, $desaturate=false) {

		$fullpath = $this->exh->getLibPath().'/'.$image;
		$relpath = $this->exh->relPath($fullpath);

		$versionpath = $this->getCacheVersionPath($relpath, $size,$forcewidth,$crop,$desaturate);

		$cachedcopy = $this->exh->getCachePath().$versionpath;

		$newtree = dirname($versionpath);

		if (! $this->exh->f->buildTree($newtree)) {
			_log("CACHE failed to make cache dir $newtree");
			return false; // Failed making cache directories, probably a permissions problem
		}
	
		$options = $size->getImageMagickOptions($fullpath);
	
		/* DEPRECIATED 04-01-2010 - use ImageSize class *
		if ($forcewidth==true) {
		  
			$imageinfo = getimagesize($fullpath);
			$x = $imageinfo[0];
			$y = $imageinfo[1];
	
			if ($x >= $y) { 
				// Landscape. Imagemagick will take care of it, so pass equal figures
				$x = $size;
				$y = $size; 
			} else {
				// Portrait. We need to specify the geometry
				$ratio = $y/$x;
				$x = $size;
				$y = round($x*$ratio);
			}
		
			$options = "-geometry ${x}x${y}";

		} elseif ($crop==true) {

			$target_x = $size;
			// FIXME hard-coded ratio here.
			$target_y = round($size / 0.144);

			$imageinfo = getimagesize($fullpath);
			$x = $imageinfo[0];
			$y = $imageinfo[1];

			$target_ratio = $target_x / $target_y;
			$ratio = $x / $y;

			if ($ratio >= $target_ratio) {
				// for images more landscapy than our target dimensions they must be resampled to be the right height before cropping
				$options = "-geometry x${target_y} -gravity Center -crop ${target_x}x${target_y}+0+0";
			} else {
				// for images more portraity than our target dimensions they must be resampled to be the right width before cropping
				$options = "-geometry ${target_x}x -gravity Center -crop ${target_x}x${target_y}+0+0";
			}
		} else {
			$options = "-geometry ${size}x${size}";
			$x = $y = $size;
		}

		// for desaturation, add the -modulate option
		if ($desaturate) $options .= " -modulate 100,0 ";
		*/

		if (! $this->exh->convert($options, $fullpath, $cachedcopy)) {
			//_log("CACHE failed to convert and crop $fullpath to $cachedcopy at size ${x}x${y}");
			_log("CACHE failed to convert and crop $fullpath to $cachedcopy at size {$size->geometry_string}");
			return false; // Failed to convert image?
		} else {
			_log("Convert called with options $options");
			_log("CACHE converting {$size->geometry_string}");
		}

		return $cachedcopy;
	}

	function getCacheVersionPath ($image, $size, $forcewidth=false, $crop=false, $desaturate=false) {	
		//$flags = ($forcewidth ? '_fw':'').($crop ? '_cr':'').($desaturate ? '_ds':'');
		//return '/'.$size.$flags.'/'.$image;

		/* NEW WAY 04-01-2010 : use size ID for ALL transactions. 
		   Different size for each type. Extra flags are stored in DB */
		return '/'.($size->id).'/'.$image;
	}

	// Check existance and currency of the requested image in the cache. We
	// know that the file exists in the library.
	function cacheCheck($image, $size, $forcewidth=false, $crop=false, $desaturate=false) {
		$cachefile = $this->exh->getCachePath().$this->getCacheVersionPath($image, $size,$forcewidth,$crop,$desaturate);
		$libfile = $this->exh->getLibPath().'/'.$image;
		if (is_file($cachefile) and is_readable($cachefile)) {
			$cachetime = filemtime($cachefile);
			$libtime = filemtime($libfile);
			if ($cachetime >= $libtime) {
				// Simple HIT, pass back cache location
				return $cachefile;
			} else {
				// Out of date cache
				_log("Cache UPDATING because its copy was created " . 
					date("Y-m-d H:i", $cachetime) . 
					", before the original at " . 
					date("Y-m-d H:i", $libtime));
				return false;
			}
		} else {
			// MISS or invalid query
			return false;
		}
	}
}

class ExhibitOutput {

	var $exh;
	var $browsePageX;

	function ExhibitOutput(&$exh) {
		$this->exh =& $exh;
		$this->setBrowsePageX(BROWSE_PAGE_X);
	}

	function setBrowsePageX($x) {
		$this->browsePageX = $x;
	}

	function getBrowsePageX() {
		return $this->browsePageX;
	}

	// Send image data to the browser. This is the only output function that
	// sends HTTP errors because the others will form only a part of another
	// HTML stream
	function image($path, $size, $forcewidth=false, $crop=false, $desaturate=false) {

		if (!is_a($size,"ImageSize")) {
			_log("404 INVALID_SIZE ($size) $path");
			header("HTTP/1.0 404 Not Found");
			exit;
		}

		// Admin can see inactive images
		if ($size===false and (! $this->exh->isAdmin())) {
			_log("404 GALLERY_UNAVAILABLE $path");
			header("HTTP/1.0 404 Not Found");
			exit;
		}

		if ($imagepath = $this->exh->i->get($path, $size, $forcewidth, $crop, $desaturate)) {

			$timeformat = "D, d M Y H:i:s";
			$filemtime = filemtime($imagepath);
			$filemdate = gmdate($timeformat, $filemtime);

			// Caches may be happy with this
			global $HTTP_IF_MODIFIED_SINCE;
			if (isset($HTTP_IF_MODIFIED_SINCE) and $HTTP_IF_MODIFIED_SINCE === $filemdate) {
				_log("304 NOT_MODIFIED $imagepath");
				header("HTTP/1.0 304 Not Modified");
				exit;
			}

			// Or, actually send the file
			$path = $this->exh->uriencode($path);

			// As an alterative to passing all image data through this script
			// we can redirect the browser to the actual cached image using a
			// 302. We can't do as much filename escaping though so hash
			// characters in filenames tend to break things.
			/*
			_log("302 MOVED $size/$path");
			header("HTTP/1.0 302 Moved Temporarily");
			header("Location: ".$this->exh->getExhRoot()."/img-cache/$size/$path");
			*/
			
			$f = fopen($imagepath, 'r');
			$now = gmdate($timeformat) . ' GMT';
			header("Date: " . $now);
			header("Last-Modified: " . $filemdate);
			header("Content-Type: image/jpeg");
			fpassthru($f);
			fclose($f);
			_log("200 OK $imagepath");

		} else {
			// The file wasn't found 
			_log("IMAGE NOT_FOUND $path");
			header("HTTP/1.0 404 Not Found");
		}
	}

	function firstimg($browse_path, $size, $extra='') {

		$path = $this->exh->stripTrailingSlash($browse_path);
		$browselist = $this->exh->f->getBrowseList($path);

		// The subdirectory may not exist.
		if ($browselist === false) {
			# echo "Invalid directory";
			return false;
		}

		$pwd = dirname($path.'../');

		foreach ($browselist as $subdir => $filelist) {
			if (is_array($filelist) and count($filelist) > 0) { // There are files in this subdirectory
				foreach ($filelist as $file) {
					?><img src="<?=$this->exh->getExhRoot()."/custom/$size/$subdir/$file"?>" border="0" <?=$extra?>/><?
					return; // EXIT on the first image
				}
			}
		}
	}

	// Browse a gallery (HTML)
	function browse($path, $popups=true, $size=IMG_THUMB_SIZE, $link_all_images_to=null, $forcewidth=false) {

		if (! is_numeric($size)) die("Invalid browse() size");

		$path = $this->exh->stripTrailingSlash($path);
		$browselist = $this->exh->f->getBrowseList($path);

		// The subdirectory may not exist.
		if ($browselist === false) {
			# echo "Invalid directory";
			return false;
		}

		$pwd = dirname($path.'../');

		$x = 1; // Counter for files iterated through
		$c = 0; // Counter for columns

		foreach ($browselist as $subdir => $filelist) {
			?>
			<table border="0" cellpadding="3" width="100%">
				<tr valign="top">
			<?
			if (is_array($filelist) and count($filelist) > 0) { // There are files in this subdirectory
				foreach ($filelist as $file) {
					$colbreak = $this->getBrowsePageX();
					if ($c >= $colbreak) { 
						$c = 0;
						print "</tr><tr>";
					}
					?>
					<td align="center">
					<?
					if ($popups) {
						?><a href="javascript:void(0)" onclick="popstuff('<?=$this->exh->getExhRoot()."/images/$subdir/$file"?>')"><?
					} elseif ($link_all_images_to) {
						?><a href="<?=$link_all_images_to?>"><?
					}
					?>
					<? if ($forcewidth){ ?>
						<img src="<?=$this->exh->getExhRoot()."/forcewidth/$size/$subdir/$file"?>" border="0" />
					<? } else {?>
						<img src="<?=$this->exh->getExhRoot()."/custom/$size/$subdir/$file"?>" border="0" />
					<? } ?>
					<?
					if ($popups or $link_all_images_to) {
						?></a><?
					}
					echo '</td>';

					$c++;
					$x++;
				}
			}

			// If there is less than a standard row worth of images then add
			// empty columns until we're done.
			if (($this->getBrowsePageX()-$c) > 0) {
				for (; ($this->getBrowsePageX()-$c) > 0; $c++) {
					echo "<td>&nbsp;</td>\n";
				}
			}
			?>
				</tr>
			</table>
			<?
			$c = 0;
		}
	}

	// Browse a gallery (HTML)
	function browse_with_zoom($path) {

		$path = $this->exh->stripTrailingSlash($path);
		$browselist = $this->exh->f->getBrowseList($path);

		// The subdirectory may not exist.
		if ($browselist === false) {
			return false;
		}

		$pwd = dirname($path.'../');

		$x = 1; // Counter for files iterated through
		$c = 0; // Counter for columns

		foreach ($browselist as $subdir => $filelist) {
			?>
			<table border="0" cellpadding="2" cellspacing="0">
				<tr valign="top">
			<?
			if (is_array($filelist) and count($filelist) > 0) {
				foreach ($filelist as $file) {
					$colbreak = $this->getBrowsePageX();
					if ($c >= $colbreak) { 
						$c = 0;
						?>
						</tr>
						<tr>
						<?
					}
					?>
					<td align="center" width="149">
						<a id="thumb<?=$x?>" href="<?=$this->exh->getExhRoot()."/custom/303/$subdir/$file"?>" class="highslide" onclick="return hs.expand(this)"><img src="<?=$this->exh->getExhRoot()."/cropped/149/$subdir/$file"?>" alt="" title="Click to zoom" border="0" /></a>
					</td>
					<?
					$c++;
					$x++;
				}
			}

			// If there is less than a standard row worth of images then add
			// empty columns until we're done.
			if (($this->getBrowsePageX()-$c) > 0) {
				for (; ($this->getBrowsePageX()-$c) > 0; $c++) {
					echo "<td>&nbsp;</td>\n";
				}
			}
			?>
				</tr>
			</table>
			<?
			$c = 0;
		}
	}

	// Detail view for an image (HTML)
	function detail($path) {

		$pwd = dirname($path);

		if (! $this->exh->i->libCheck($path)) {
			_log("DETAIL NOT_FOUND $path");
			exit;
		}

		$detail = $this->exh->uriencode($path);

		?>
		<div align="center">
			<img src="<?=$this->exh->getExhRoot().'/images/'.$detail?>" border="0" />
		</div>
		<?
	}

	// List the available galleries (HTML)
	function galleries() {

		$sql = "SELECT path, title FROM ".DB_TABLE_GALLERY." WHERE visible=1 ORDER BY title";
		$res = $this->exh->db->query($sql, "Failed to get list of galleries");

		if ($res->numrows() > 0) {
			?>
			<ul>
			<?
			while ($row = $res->fetchrow(DB_FETCHMODE_OBJECT)) {
				$row->path = stripslashes($row->path);
				$row->title = stripslashes($row->title);

				if (! $this->exh->f->galleryExistsOnFS($row->path)) continue;
				?>
				<li><a href="<?=WEBROOT?>admin/gallery/edit/<?=$row->path?>"><?
					print $row->title ?></a></li>
				<?
			}
			?>
			</ul>
			<?
		} else {
			?>
			<p>No galleries to show!</p>
			<?
		}
	}
}

class ExhibitAdminOutput extends ExhibitOutput {

	// Browse a gallery in ADMINISTRATIVE MODE
	function browse($path) {

		$browselist = $this->exh->f->getBrowseList($path);
		if ($browselist === false) {
			# echo "Invalid directory";
			return false;
		}

		$pwd = dirname($path.'../');

		$c = 0;
		foreach ($browselist as $subdir => $filelist) {
			if (is_array($filelist) and sizeof($filelist)) {
				?>
				<form method="POST">
				<input type="hidden" name="ACTION" value="UpdateImage" />
				<input type="submit" value="Save changes &raquo;" class="submit" />
				<br/>
				<br/>
				<table border="0" cellpadding="3" width="100%">
				<tr valign="top">
				<?
				foreach ($filelist as $file) {
					if ($c >= $this->getBrowsePageX()) { 
						$c = 0;
						?>
						</tr>
						<tr>
						<? 
					}
					?><td><?
					?><img src="<?=$this->exh->getExhRoot()."/thumbs/$subdir/$file"?>" border="0" /><?
					?><br/>
					<input type="checkbox" name="Delete[<?=$subdir?>/<?=$file?>]" value="true" /> Delete<br/>
				
					<span style="font-size: xx-small">Links to
						<? foreach(array(300,400) as $size) { ?>
							<a href="<?=WEBROOT?>img/custom/<?=$size?>/<?=$subdir?>/<?=$file?>"><?=$size?>px</a>
					<? } ?>
					</span>
					
					</td>
					<?
					$c++;
				}
				?>
				</tr>
				</table>
				</form>
				<?
			}
			$c = 0;
		}
	}
}

class ExhibitURL {

	var $exh;

	function ExhibitURL(&$exh) {
		$this->exh =& $exh;
	}
	
	// Determine what the script should do/output based on the URI
	function getCommand() {

		$self = $this->exh->uridecode($_SERVER['PHP_SELF']);
		$self = preg_replace('/\/+/', '/', $self); // Remove duplicate slashes
		$self = $this->exh->stripTrailingSlash($self);
		$URI = explode('/', substr($self, strlen($this->exh->getExhRoot().'/')));
		$path = '';

		// We can safely consider $URI to be free of empty values since
		// we've already replaced all repeating slashes in the URI.

		if (! isset($URI[1]))
			return array('cmd' => 'SHOW_GALLERIES'); // No object specified, show galleries.

		switch ($URI[0]) {

			// User wants an IMG_STD_SIZE sized image
			//	usage: EXHIBIT/images/path-to-image
			case 'images':
				unset($URI[0]);
				$path = $this->exh->stripTrailingSlash(implode('/', $URI));
				return array(
					'cmd' => 'STANDARD', //'SHOW_IMAGE', 
					'path' => $path
					);
				break;

			// Show an IMG_THUMB_SIZE sized image
			//	usage: EXHIBIT/thumbs/path-to-image
			case 'thumbs':
				unset($URI[0]);
				$path = implode('/', $URI);
				return array(
					'cmd' => 'THUMB', //'SHOW_THUMB', 
					'path' => $path
					);
				break;

			default:
				$cmd = strtoupper($URI[0]);
				unset($URI[0]);
                $path = implode('/', $URI);
                return array(
                    'cmd' => $cmd, 
                    'path' => $path
                    );
                break;

			/* DEPRECIATED 04-01-2010
               BECAUSE IT'S TOTALLY UN-DRY

			// Allow user to specify size of the image in the URL, instead of
			// the two standard sizes IMG_THUMB_SIZE and IMG_STD_SIZE.
			//	usage: EXHIBIT/custom/450/path-to-image
			case 'custom': 
				$size = $URI[1];
				unset($URI[0]);
				unset($URI[1]);
				$path = implode('/', $URI);
				return array(
					'cmd' => 'SHOW_CUSTOM', 
					'path' => $path,
					'size' => $size
					);
				break;
			
			case 'forcewidth':
				$size = $URI[1];
				unset($URI[0]);
				unset($URI[1]);
				$path = implode('/', $URI);
				return array(
					'cmd' => 'SHOW_FORCEWIDTH', 
					'path' => $path,
					'size' => $size
					);
				break;

			case 'cropped':
				$size = $URI[1];
				unset($URI[0]);
				unset($URI[1]);
				$path = implode('/', $URI);
				return array(
					'cmd' => 'SHOW_CROPPED', 
					'path' => $path,
					'size' => $size
					);
				break;
			
			case 'desatcropped':
				$size = $URI[1];
                unset($URI[0]);
                unset($URI[1]);
                $path = implode('/', $URI);
                return array(
                    'cmd' => 'SHOW_DESAT_CROPPED',
                    'path' => $path,
                    'size' => $size
                    );
                break;	


			// Browse a gallery
			// 	usage: EXHIBIT/browse/gallery-name
			case 'browse':
				unset($URI[0]);
				$path = implode('/', $URI);
				return array(
					'cmd' => 'SHOW_BROWSE', 
					'path' => $path
					);
				break;

			// Detail view for an image
			//	usage: EXHIBIT/detail/path-to-image
			case 'detail':
				unset($URI[0]);
				$path = implode('/', $URI);
				return array(
					'cmd' => 'SHOW_DETAIL', 
					'path' => $path
					);
				break;

			// List the available galleries
			default:
				return array('cmd' => 'SHOW_GALLERIES'); // No object specified, show galleries.
			*/
		}
	}
}

class ExhibitFile {

	var $exh;
	var $browseList = array();

	function ExhibitFile(&$exh) {
		$this->exh =& $exh;
	}

	function getBrowseList($path) {
		$this->makeBrowseList($path);
		return ($this->browseList) ? $this->browseList : false;
	}

	function makeBrowseList($path) {

		$dir = $this->exh->getLibPath().'/'.$path.'/';
		$mklist = array();

		if ($d = @opendir($dir)) {
			$i=0;
			while ($file = readdir($d)) {
				if ($file=='.' or $file=='..') continue;
				if (is_dir($dir.$file)) {
					$this->browseList[$this->exh->relPath($this->exh->uriencode($dir.$file))] = array();
					if ($this->exh->getRecurseMode()==='FLAT') {
						// Descend into subdirectories for this mode
						$this->makeBrowseList($this->exh->relPath($dir.$file));
					}
				} elseif (is_file($dir.$file)) {
					$mklist[$i]['dir'] = $this->exh->relPath($this->exh->uriencode($dir));
					$mklist[$i]['file'] = $this->exh->uriencode($file);
					$mklist[$i]['sorttime'] = @filemtime($dir.$file);
				}
				$i++;
			}
			closedir($d);

			// Use a custom sorting handler for sorting our file list by creation
			// time

			usort($mklist, "sortFilesByCTime");

			foreach ($mklist as $i=>$f) {
				$this->browseList[$f['dir']][] = $f['file'];
			}

			return;
		} else {
			return false; // Failed opening the dir
		}
	}

	// Find the next file in a directory. Won't recurse into sub-directories.
	// For use in a href, not filesystem.
	function nextFile($path) {
		
		$dir = $this->exh->getLibPath().'/'.dirname($path);
		if ($d = @opendir($dir)) {
			// Scan through the directory given. If we run into the file we're
			// already on, then return on the next iteration. If there isn't
			// one (we're at the end of the list), then return false;
			$pointer = false;
			while ($file = readdir($d)) {
				$fullpath = "$dir/$file";
				if ($file=='.' or $file=='..' or is_dir($fullpath)) continue;
				if ($pointer === false) {
					if ($file == basename($path))
						$pointer = true;
				} else {
					closedir($d);
					return dirname($path).'/'.$this->exh->uriencode($file);
				}
			}
			return false; // Probably reached end of the directory listing
		} else {
			return false; // Failed opening the directory
			
		}
	}

	// Find the previous file in a directory. Won't recurse into sub-directories.
	// For use in a href, not filesystem.
	function prevFile($path) {

		$dir = $this->exh->getLibPath().'/'.dirname($path);
		if ($d = @opendir($dir)) {
			// Scan through the directory given. If we run into the file we're
			// already on, then return the last file found. If we don't find
			// the file we're on, or it's the first file (so no previous),
			// return false.
			$pointer = false;
			$prevFile = false;
			while ($file = readdir($d)) {
				$fullpath = "$dir/$file";
				if ($file=='.' or $file=='..' or is_dir($fullpath)) continue;
				if ($pointer === false) {
					if ($file == basename($path)) {
						$pointer = true;
					} else {
						$prevFile = $file;
					}
				} else {
					closedir($d);
					break;
				}
			}
			if (! $prevFile) return false;
			return dirname($path).'/'.$this->exh->uriencode($prevFile);
		} else {
			return false; // Failed opening the directory
		}
	}
	
	// Determine if a gallery exists on the filesystem. Galleries may continue
	// to exist in the database after they are deleted.
	function galleryExistsOnFS($gal) {
		$path = $this->exh->getLibPath().'/'.$gal;
		if (is_dir($path) and is_readable($path)) {
			return true;
		} else {
			return false;
		}
	}

	// Scan the image library path from the top
	function scanLib() {
		_log("Scanning library: ".$this->exh->getLibPath());
		$this->scanPath($this->exh->getLibPath()); // Will recurse
	}

	// Scan a path for new directories and files.
	function scanPath($path) {

        $dir = @opendir($path);

        while ($file = readdir($dir)) {
            if ($file == '.' or $file == '..') continue;
			$file = "$path/$file";
			if (is_dir($file)) {
				// Look for first-level directories - they're galleries.
				if (! strstr($this->exh->relPath($file, false), '/')) {
					$val = $this->exh->escape($this->exh->relPath($file));
					$sql = "SELECT * FROM ".DB_TABLE_GALLERY." WHERE path = '$val'";
					$res = $this->exh->db->query($sql, "Failed locating record of gallery");
					if ($res->numrows() == 0) {
						$val = $this->exh->escape($this->exh->relPath($file));
						$sql = "INSERT INTO ".DB_TABLE_GALLERY." (path, title) VALUES ('$val', '$val')";
						$this->exh->db->query($sql, "Failed inserting record of gallery");
					}
				}
				// Continue recursing
				if (is_readable($file)) $this->scanPath($file);
			}
        }
        closedir($dir);
	}

	function buildTree($hierarchy) {
		$dirs = explode('/', $hierarchy);
		$exists = '';
		foreach ($dirs as $dir) {
			$target = $this->exh->getCachePath().'/'.$exists.$dir;
 			if (! is_dir($target)) {
				if (! mkdir($target, 0777)) {
					return false;
				}
			}
			$exists .= $dir.'/';
		}
		return true;
	}
}

function _log($append) {
	
	if (LOG_ENABLED === false) return;

	$log = file(CACHEPATH.'/exhibit.log');
	$log[] = $append;

	$rewrite = '';
	foreach ($log as $line => $content) {
		$content = trim($content);
		if (! empty($content)) {
			$rewrite .= $content."\n";
		}
	}
	
	if ($f = fopen(CACHEPATH.'/exhibit.log', 'w')) {
		fwrite($f, $rewrite);
		fclose($f);
	} else {
		die ("writing to log failed");
	}
}

function sortFilesByCTime($a, $b) {
	if ($a['sorttime'] < $b['sorttime']) {
		return 1;
	} else if ($a['sorttime'] > $b['sorttime']) {
		return -1;
	} else {
		return 0;
	}
}

?>
