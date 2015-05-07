<?
class iDBDownloadable extends iDB {

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
			'product_id'    => 0,
			'label' => '',
			'path' =>'',
			'platform'=>'osx'
			);

		// Mandatory fields is a simple array (not associative) of fields that
		// can't be blank during order submission
		// 
		$this->mandatory_fields = array(	
			);

		$this->file_image_fields = array();

		$this->list_columns = array('id','label');

		$this->addBelongsTo('products');
	}

	// TODO pull these functions from database exh_dimensions based on image size and label?
	function thumbURL() {
		return $this->imageURL($this->image_path,'thumb','images');
	}

	function preProcessFields() {
		parent::preProcessFields();
	}

	function postProcessFields() {
		parent::postProcessFields();
	}

	function delete() {
		parent::delete();
	}

}
