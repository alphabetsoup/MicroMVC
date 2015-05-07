<?
class iDBImage extends iDB {



	function factory() {
		return new self;
	}

	function init() {

		// Fields are defined as fieldname => default_value
		//
		// Some fields may be input only, in the sense they are used on forms
		// to figure stuff out but not actually recorded or displayed
		// thereafter
		// 
		$this->fields = array(	
			'id'    => 0,
			'label' => '',
			'image_path' =>'',
			'weight'     =>999,
			//'image_nav_path' => '',
			//'image_med_path' => '',
			'gallery_id'=> 0,
			'show_on_home'=>0,
			'right_image_id'=>0,
			'show_in_nav'=>1
			);

		// Mandatory fields is a simple array (not associative) of fields that
		// can't be blank during order submission
		// 
		$this->mandatory_fields = array(	
			);

		$this->file_image_fields = array('image' => 'image_path');

		$this->list_columns = array('id','label','date_added');

		$this->addBelongsTo('gallery');
		$this->addBelongsTo('images','right_image');
	}

	// TODO pull these functions from database exh_dimensions based on image size and label?
	function heroURL() {
		return $this->imageURL($this->image_path,'standard','images'); // 888x587
	}

	function twoColURL() {
		return $this->imageURL($this->image_path,'twocol','images');
	}

	function rightImageURL() {
		return $this->right_image->imageURL($this->right_image->image_path,'twocol','images');
	}

	function navURL() {
		return $this->imageURL($this->image_path,'standard','navimages');
	}

	function navDesatURL() {
		return $this->imageURL($this->image_path,'desaturate','navimages');
	}

	function thumbURL() {
		return $this->imageURL($this->image_path,'thumb','images');
	}

	function preProcessFields() {
		if ($this->right_image_id > 0) {
			// if there was an old right_image, it needs to be displayed again.
			if ($this->right_image) $this->right_image->update(array('show_in_nav'=>1),false,false);
			
		}
	}

	function postProcessFields() {
		if ($this->right_image_id > 0) {
			// if there is a new right_image, it needs to be hidden in the nav.
			unset ($this->right_image);
			$this->right_image->update(array('show_in_nav'=>0),false,false);
		
		}
		parent::postProcessFields();
	}

	function delete() {
		// disassociate right image if it exists
		if ($this->right_image_id > 0) {
			// if there was an old right_image, it needs to be displayed again.
			if ($this->right_image) $this->right_image->update(array('show_in_nav'=>1),false,false);
		}
		parent::delete();
	}

}
