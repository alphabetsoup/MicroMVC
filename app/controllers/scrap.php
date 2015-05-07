<?
/*class AutoPage extends BaseController {

	function AutoPage() {
		parent::Page();
		$this->output();
	}

	function init() {
		$this->layout_template = 'default.html';

	}

	function index() {
		$this->render();
	}
}

class SpecialsPage extends BaseController {

	var $specialsbox;
	var $promotionsbox;
	var $eventsbox;
	var $has_specials;
	var $has_events;
	var $has_promotions;

	function SpecialsPage() {
		// Load the data model
		$this->special = new iDB_Special;
		
		$this->specialsbox = array();
		$this->promotionsbox = array();
		$this->eventsbox = array();
		
		parent::Page();
	}

	function index() {
		$this->specials = $this->special->getAll(null, 'weight ASC');

		// add an extra attribute to each row so we know if it has any extra gallery images
		foreach ($this->specials as $_ => $p) {
			$this->specials[$_]['has_external_url'] = ( strlen($p["external_url"]) > 0);
			$this->specials[$_]['has_attachment'] = ( strlen($p["attachment"]) > 0);
			$this->specials[$_]['has_image'] = (strlen($p["image"]) > 0);

			//$this->specials[$_]['debug'] = "DEBUGSTR: count ext url = ".strlen($p["external_url"])." count attach = ".strlen($p["attachment"]);
			
			switch (intval($p["type"])) {
				case 1:
					$this->specialsbox[] = $this->specials[$_];
					break;
				case 2:
					$this->promotionsbox[] = $this->specials[$_];
					break;
				case 3:
					$this->eventsbox[] = $this->specials[$_];
					break;
			}
		}
		$this->has_specials = ( count($this->specialsbox) > 0 );
                $this->has_promotions = ( count($this->promotionsbox) > 0 );
                $this->has_events = ( count($this->eventsbox) > 0 );
	}
}*/
