<?
class iDBProduct extends iDB {

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
			'title' => '',
			'content' => '' ,
			'created_at' => date("Y-m-d H:i:s"), 
			'updated_at' => null
			);

		// Mandatory fields is a simple array (not associative) of fields that
		// can't be blank during order submission
		// 
		$this->mandatory_fields = array(
			'title','content'
			);

		$this->addHasMany('downloadables');
	}

	function preProcessFields() {
	}

	function postProcessFields() {
		if (empty($_FILES)) return;

		$file_fields = array('downloadable','attachment');

		foreach ($file_fields as $f) {
			// If the file field is not also listed in the main field list, ignore it.
			if (! isset($this->fields[$f])) continue;

			if (is_uploaded_file($_FILES[$f]['tmp_name'])) {
				$hash = md5(microtime());
				$file_ext = array_pop(explode('.', $_FILES[$f]['name']));
				$filename = $hash.'.'.$file_ext;
				$target = UPLOADS_ROOT.$this->db_table.'/'.$this->id.'/'.$filename;
				if (! is_dir(dirname(dirname($target)))) mkdir(dirname(dirname($target))); // parent dir
				if (! is_dir(dirname($target))) mkdir(dirname($target)); // target dir
				if (! move_uploaded_file($_FILES[$f]['tmp_name'], $target)) {
					die("File upload failed");
				}
				chmod($target, 0755);
				$this->update(array($f => $filename), false);
			}
		}
	}
}
