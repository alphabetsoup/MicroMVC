<?


class GalleriesController extends BaseController {

	function index() {
		global $params;
		$this->showimgnav = false;
		$this->render();
	}

	function hasImages() {
		if (! empty($this->images)) {
			return true;
		} else {
			return false;
		}
	}

	function _firstImage() {
		return array_shift($this->images);
	}

	function init() {
        $this->layout_template = 'default.html';
		
		// for navigation - TODO make DRY for all public facing controllers
		$this->galleries = iDBGallery::findAll(array('active'=>'y'));

		$this->showimgnav = true;

		// init
		//$this->startNewImgNav();
    }

	function __call($name,$args) {
		// try to find gallery in DB with label==$name	
		$this->gallery = new iDBGallery();
		$this->gallery = $this->gallery->find(array('label'=>$name));
		if (!$this->gallery) throw new FatalException ("Gallery $name not found");	
		$this->setWrapper('gallery.html');
		$this->render();
	}

/*	function startNewImgNav() {
		$this->imgnavindex = 0;
	}*/

	// helpers- TODO move to a newhelper class and make available through pear flexy to views.

	function num_images() {
		return count($this->gallery->images);
	}

	function imgNavCSSDimensions($index) {
		$standard_lengths = array(23=>11,22=>11,21=>10,20=>10,19=>9,18=>9,17=>8,16=>8,15=>7,14=>7,13=>6,12=>6,11=>10,10=>9,9=>8,8=>7,7=>7,6=>5,5=>4,4=>3,3=>2,2=>2,1=>1);
		//$y_start = 111;
		//$x_start = 16;
		$y_start = 30;
		$x_start = 28;
		$looplength = $standard_lengths[$this->num_images()];
		$y_inc = 0;
		// todo make nicer??
		$relindex = $index % $looplength;
		$y_new = ($relindex >= ($looplength / 2)) ? $y_start - $y_inc * ($relindex - ($looplength/ 2)) : $y_start - $y_inc * (($looplength / 2)-$relindex);
	
		//$this->imgnavindex++;

		return "width:${x_start}px;height:${y_new}px;";
	}
		

}

