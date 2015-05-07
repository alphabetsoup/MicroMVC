<?
/*class iDB_Special extends iDB {

	var $id;

	function factory() {
		return new iDB_Special;
	}

	function iDB_Special() {

		$this->db_table = 'specials';

		// Fields are defined as fieldname => default_value
		//
		// Some fields may be input only, in the sense they are used on forms
		// to figure stuff out but not actually recorded or displayed
		// thereafter
		// 
		$this->fields = array(
			// Personal Details
			'type' => '',
			'label' => '',
			'day_added' => '', // input only
			'month_added' => '', // input only
			'year_added' => '', // input only
			'date_added' => '',
//			'excerpt' => '',
			'description' => '',
			'image' => '',
			'attachment' => '',
			'external_url' => '',
			'weight' => 999
			);

		// Mandatory fields is a simple array (not associative) of fields that
		// can't be blank during order submission
		// 
		$this->mandatory_fields = array(
			'type',
			'label',
			'date_added',
//			'excerpt',
			'description'
			);

		parent::iDB();
	}

	function preProcessFields() {
		if (isset($this->data['day_added'])) {
			$this->data['date_added'] =
				$this->data['year_added'].'/'.
				$this->data['month_added'].'/'.
				$this->data['day_added'];

			unset($this->data['day_added']);
			unset($this->data['month_added']);
			unset($this->data['year_added']);
		}
	}

	function postProcessFields() {
		if (empty($_FILES)) return;

		$file_fields = array('image','attachment');

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
}*/
