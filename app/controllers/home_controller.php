<?
class HomeController extends BaseController {
    function init() {
        $this->setLayout('default.html');

		// for navigation - TODO make DRY for all public facing controllers
		$this->galleries = iDBGallery::findAll(array('active'=>'y'));

		//$this->gallery = $this->galleries[rand(0,count($this->galleries)-1)];
		// fixme make selectable. for nowdefault to 'outdoor'
		//$this->gallery = $this->galleries[count($this->galleries)-1];
		$temp = new iDBImage();
		$this->images = $temp->find(array('show_on_home'=>'y'),'weight ASC',true);
	}

    function index() {
        $this->render();
    }
}

