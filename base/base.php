<?

require_once('basehelpers.php');

abstract class BaseController {

	var $layout_template;
	var $template;
	var $title;
	var $webroot;
	var $html_body;
	var $html_body_from_template;
	var $elements;

	var $notices;
	var $errors;
	
	function Page($title='', $template='index.htm') {
		$this->title = $title;
	}
	function __construct() {
		$this->webroot = WEBROOT;
		$this->html_body_from_template = false;
		$this->body_wrapper_template = false;
		$this->layout_template = 'default.html';
		$this->elements = array();
		require_once 'HTML/Template/Flexy.php';
		//require_once 'HTML_Template_Flexy-1.3.9/Flexy.php';
		$this->init();
	}

	abstract function init();
	
	function var_dump($v) {var_dump($v);}

	function getFlexyOptions() {
		global $views;
		$this_dir = dirname(__FILE__);
		$current_script = $_SERVER['SCRIPT_FILENAME'];
		return array(
			'compileDir' => '/tmp/'.str_replace('/','_',$current_script).'_flexyc',
			'templateDir' => getABSViewDir(),
			'forceCompile' => '0',
			'filters' => 'Php,SimpleTags,BodyOnly',
			'debug' => 0,
			'url_rewrite' => '/:'.dirname($_SERVER['PHP_SELF']).'/'
			);
	}

	//function start() { }

	function addElements($e=array(), $reset=false) {
		require_once 'HTML/Template/Flexy/Element.php';
		//require_once 'HTML_Template_Flexy-1.3.9/Flexy/Element.php';
		if (is_array($e) and (count($e) > 0)) {
			if ($reset) $this->elements = array();
			foreach ($e as $k => $v) {
				$this->elements[$k] = new HTML_Template_Flexy_Element;
				if (! empty($v)) {
					if (is_array($v)) {
						$this->elements[$k]->setOptions($v); 
					} else {
						$this->elements[$k]->setValue($v);
					}
				}
			}
		}
	}

	function getElement($element_name) {
		return $this->elements[$element_name];
	}

	function clearElementOptions($element_name) {
		$this->getElement($element_name)->setOptions(array());
	}

	/**
	 * Clean an array of field/values. Specifically, remove certain fields or
	 * those starting with _. Apply stripslashes if necessary in this
	 * environment
	 *
	 * @param array $array Associative array of field/values to treat
	 * @return array Treated values
	 */
	function &cleanSource($source) {
		if (is_array($source) and count($source) > 0) {
			foreach ($source as $name => $value) {
				if ((strpos($name, 'action') === 0) or ($name{0} == '_')) {
					unset($source[$name]);
				}
				elseif (get_magic_quotes_gpc()) {
					$source[$name] = stripslashes($value);
				}
			}
		}
	    return $source;
	}

	function nl2br($str) {
		print nl2br($str);
	}

	// RESTful methods below here
	abstract public function index();


	// render function
	function render($partial = false) {
		if ($partial) $template_path = $this->getView($partial);
		else $template_path = $this->getLayout();
		$output = new HTML_Template_Flexy($this->getFlexyOptions());
		$output->compile($template_path);
		$this->elements = $this->distillElements($this->elements,$output->getElements());
		$output->outputObject($this, $this->elements);
	}

	// Placeholder - override in child controller for more fine-grained control over elements.
	function distillElements($elements,$template_elements) {
		return $elements;
	}

	function yield($template_name = false) {
	    if (!$template_name) {
	        if (!($template_name = $this->getWrapper())) $template_name = getActionAsViewName();
	    }
	    $this->render($template_name);
	}

	function setWrapper($template) {
	    $this->body_wrapper_template = $template;
	}

	function getWrapper() {
	    return $this->body_wrapper_template;
	}

	function setLayout($layout_template) {
	    $this->layout_template = $layout_template;
	}

	function getLayout() {
	    $layout_path = getLayoutDir().$this->layout_template;
	    if (!file_exists(getABSViewDir().$layout_path)) throw new FatalException ("Layout $this->layout_template could not be found at $layout_path");
	    return $layout_path;
	}

	protected function redirectTo($action,$id = false,$prefix = false) {
	    header("Location: ".$this->urlFor($action, $id, $prefix),true,307);
	    exit();
	}

	function urlFor($action,$id  = false,$actionprefix= false) {
	    if (!method_exists($this,$action)) throw new FatalException("Action $action is not callable on ".get_class($this));
	    else return WEBROOT.getControllerName().'/'.($actionprefix?$actionprefix.'/':'').$action.($id?'/'.$id:'');
	}
	
	// directory methods
	function getSmallName() {	
		return strtolower(str_ireplace('Controller','',get_class($this)));
	}

	function getViewDir() {
		return $this->getSmallName($class).DIRSEP;
	}

	// polymorphic - tries to find template in app/views/<controllername>/template
	// if not there, tries for the parent (if parent is not BaseController)
	function getView($template) {
		$template_path = $this->getViewDir().$template;
		$exists = file_exists(getABSViewDir().$template_path);
		if (get_class() == 'BaseController' && !$exists) throw new FatalException ("Could not find view $template at $template_path");
		else if (!$exists) return parent::getView($template);
		else return $template_path;
	}

	function __get($property) {
		$classname = 'iDB'.$property;
		if (class_exists($classname)) {
			return new $classname;
		}
		else return '';
		//else return 'BAD VARIABLE';//else throw new FatalException("Property $property for controller ".get_class($this)." does not exist.");
	}

	// VIEW HELPERS
	function radio_tag($value,$name) {
		$id = $name.'_'.$value;
		?><input type="radio" id="<?=$id?>" value="<?=$value?>" name="<?=$name?>" /><?
	}
}
include_once 'DB.php';  // PEAR::DB
/* HAVE ONLY ONE DB CONNECTION */
$global_database_connection = DB::connect(DB_CONNECT_STR, true);

/**
 * General re-usable database bits and pieces (much specific to this schema)
 * 
 * external requirements: is_number(), DB_CONNECT_STR
 */
abstract class iDB {

	var $db;
	var $db_table;
	var $fields;
	var $mandatory_fields;

	var $file_image_fields;

	var $autoincrement;

	var $ui_error;

	var $data;

	var $associations;

	function __construct() {
		$this->db = $this->getDB();
		$this->db_table = $this->getTableNameFromClass();
		$this->autoincrement = 'id';
		$this->associations = array();
		$this->file_image_fields = array(); // initialise now, set in $this->init()
		$this->list_columns = array('label');
		$this->init();
	}

	abstract function init();
	
	function getTableNameFromClass() {
		return pluralise(str_ireplace('idb','',strtolower(get_class($this))));
	}

	function getDB() {
		global $global_database_connection;
		if (! isset($this->db)) {
			$this->db = $global_database_connection; //DB::connect(DB_CONNECT_STR, true);
		}
		if (DB::isError($this->db)) {
			die("Database error.");
			$this->db = null;
			return false;
		}
		return $this->db;
	}

	function getRowById($id, $table=null) {

		if (! is_number($id)) return false;
		if (! $table) $table = $this->db_table;

		$sql = 
			"SELECT *, DATE_FORMAT(date_added, '%d.%m.%y') AS date_added_nice ".
			"FROM $table ".
			"WHERE $table.id=$id ".
			"LIMIT 1";

		$res = $this->query($sql, "Failed getting row from $table");

		if ($res->numrows()===1) {
			$row = $res->fetchrow(DB_FETCHMODE_ASSOC);
			foreach ($row as $key=>$val) {
				$row[$key] = stripslashes($val);
			}
			return $row;
		} else {
			return false;
		}
	}

	function is($id) {
		return $id == $this->id;
	}

	function isnot($id) {
		return $id != $this->id;
	}

	// TODO make more Rails-like. DO NOT CALL STATICALLY
	function find ($args = false,$orderby = false, $as_array = false,$error='') {
		if (is_numeric($args)) {
			$this->fields = $this->getRowByID($args);
			return $this;
		}
		else {
			$sql = 'SELECT * FROM '.$this->db_table;
			if (is_array($args)) {
				$sql .= ' WHERE ';
				foreach ($args as $key => $val) $args[$key] = $key."='".$val."'";
				$sql .= implode(' AND ',$args);
			}
			else if ($args == false) $sql .= '';
			else if (is_string($args)) $sql.= ' WHERE '.$args;
			else throw new FatalException ("Unsupported find criteria: ".var_export($args,true));

			if ($orderby) $sql .= " ORDER BY $orderby ";

			if (!$error) $error = "Failed finding {$this->db_table} using sql $sql";
			$res = $this->query($sql,$error);
			if ($res->numrows() === 0) return false;
			else if ($res->numrows()===1 && !$as_array) {
 	        	$this->fields = $this->rowToFields($res->fetchrow(DB_FETCHMODE_ASSOC));
 	        	return $this;
 		   	} else {
				$objs = array();
				$classname = get_class($this); // hack because the self keyword resolves incorrectly when called from __get
		    	while (($fields = $this->rowToFields($res->fetchrow(DB_FETCHMODE_ASSOC))) != false) {
					$tmp = new $classname();
					$tmp->fields = $fields;
					$objs[] = $tmp;
				}
				return $objs;
		    }
		}
	}

	static function findAll($conditions = false) {
		$classname = get_called_class();
		$tmp = new $classname();
		return $tmp->find($conditions,false,true);
	}


	private function rowToFields($row) {	
	    if (!$row) return false;
		foreach ($row as $key=>$val) {
	    	$row[$key] = stripslashes($val);
	    }
	    return $row;
	}


	function query($sql, $app_error="Default error") {
		// Perform query
		$res = $this->db->query($sql);
		if (DB::isError($res) and (DB::errorMessage($res) !== 'already exists')) {
			die("Database error.");
		}
		return $res;
	}

	// A front-end to PEAR::autoExecute (mode DB_AUTOQUERY_UPDATE)
	function autoupdate($table, $values, $where) {
		$res = $this->db->autoExecute($table, $values, DB_AUTOQUERY_UPDATE, $where);
		if (DB::isError($res) and (DB::errorMessage($res) !== 'already exists')) {
			// Call $res->getDebugInfo() for more info
			return $res;
		} else {
			return true;
		}
	}

	// A front-end to PEAR::autoExecute (mode DB_AUTOQUERY_INSERT)
	function autoinsert($table, $values) {
		$id = $values[$this->autoincrement] = $this->nextId($table);
		$values['date_added'] = date('Y-m-d H:i:s');
		$res = $this->db->autoExecute($table, $values, DB_AUTOQUERY_INSERT);
		if (DB::isError($res) and (DB::errorMessage($res) !== 'already exists')) {
			// fixme should return false
			return $res;
		} else {
			return $id;
		}
	}
	
	function getRand() {
		return $this->db->getAll(
			sprintf("SELECT *, DATE_FORMAT(date_added, '%%d.%%m.%%y') AS date_added_nice FROM %s ORDER BY RAND() LIMIT 1", 
				$this->db_table
				), 
			DB_FETCHMODE_ASSOC
			);
	}

	function nextId($seq_name) {
	    return DBSequence::nextId($seq_name, $this->getDB(), 'sequence');
	}	

	function getAll($limit=null, $order=null) {
		if (! $order) $order = 'date_added DESC';
		return $this->db->getAll(
			sprintf("SELECT *, DATE_FORMAT(date_added, '%%d.%%m.%%y') AS date_added_nice FROM %s ORDER BY %s %s", 
				$this->db_table,
				$order,
				($limit) ? sprintf(" LIMIT %d ", $limit) : ''
				), 
			DB_FETCHMODE_ASSOC
			);
	}
   
	// Assumes this model has a column 'label'. TODO make settable in model header. Default to 'label'.
	function getAllAsSelectable () {
		$order = 'date_added DESC';
		$sql = "SELECT id as value, label FROM {$this->db_table} ORDER BY $order ";
	    $rows = $this->db->getAll($sql, DB_FETCHMODE_ASSOC);
		if (DB::isError($rows)) throw new FatalException($rows->getMessage()."\n\n".$sql);
		$list = array();
		foreach ($rows as $r) $list[$r['value']] = $r['label'];
		return $list;
	}

	function datetime_valid_mysql($datetime) {
	    if(preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/', $datetime, $date)) {
	        $e = mktime($date[4], $date[5], $date[6], $date[2], $date[3], $date[1]);
	        return ($datetime == date('Y-m-d H:i:s', $e));
	    }
	    return false;
	}

	function datetime_mysql2ts($datetime) {
	    if (iDB::datetime_valid_mysql($datetime)) {
	        preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/', $datetime, $date);
	        return mktime($date[4], $date[5], $date[6], $date[2], $date[3], $date[1]);
	    }
	    return null;
	}

	function date_valid_mysql($date) {
	    if(preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $date, $matches)) {
	        $e = mktime(0,0,0, $matches[2], $matches[3], $matches[1]);
	        return ($date == date('Y-m-d', $e));
	    }
	    return false;
	}

	function date_mysql2ts($date) {
	    if (iDB::date_valid_mysql($date)) {
	        preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $date, $matches);
	        return mktime(0,0,0, $matches[2], $matches[3], $matches[1]);
	    }
	    return null;
	}

	function nicedate($mysql_date) {
		return date('d/m/Y', $this->date_mysql2ts($mysql_date));
	}

	function validateFields() {
		// Mandatory fields
		$violations = array();
		foreach ($this->mandatory_fields as $f) {
			if (! isset($this->data[$f])) {
				$violations[] = $f;
			} elseif (isset($this->data[$f]) and (empty($this->data[$f]))) {
				$violations[] = $f;
			}
		}
		if (count($violations)) {
			return $violations;
		} else {
			return true;
		}
	}

	function insert($data) {

		$this->data = $data;
		$this->preProcessFields();

		foreach ($this->data as $k => $v) {
			if (! isset($this->fields[$k])) continue;
			$values[$k] = $this->fields[$k] = $v;
		}

		$violations = $this->validateFields();
		if (is_array($violations)) {
			$this->ui_error = 'Some fields were left blank: '.implode(',', $violations);
			return false;
		}

		if ($id = $this->autoinsert($this->db_table, $values)) {
			//$this->id = mysql_insert_id(); // FIXME use sequences
			$this->id = $id;
			$this->postProcessFields();
			return $this->id;
		} else {
			$this->ui_error = 'There was a problem saving your changes';
			return false;
		}
	}

	function update($data, $do_validation=false, $do_prepostprocess = true) {

		$this->data = $data;
		if ($do_prepostprocess) $this->preProcessFields();
		
		// Get the id from the $data array, or from this object if we already have it
		if (! isset($this->data['id'])) {
			if (! is_number($this->id)) {
				$this->ui_error = 'No id specified';
				return false;
			}
		} else {
			$this->id = $this->data['id'];
		}

		foreach ($this->data as $k => $v) {
			if (! isset($this->fields[$k])) continue;
			$values[$k] = $this->fields[$k] = $v;
		}

		// We may want to bypass mandatory field validation when we're
		// internally updating one field (e.g. image or attachment in
		// postProcessFields() 
		if ($do_validation) {
			$violations = $this->validateFields();
			if (is_array($violations)) {
				$this->ui_error = 'Some fields were left blank: '.implode(', ', $violations);
				return false;
			}
		}

		if ($this->autoupdate($this->db_table, $values, sprintf("id=%s", $this->db->quoteSmart($this->id)))) {
			if ($do_prepostprocess) $this->postProcessFields();
			return true;
		} else {
			$this->ui_error = "There was a problem saving your changes";
			return false;
		}
	}

	function delete() {
		if (! is_number($this->id)) return false;

		// Delete database records
		$sql = sprintf("DELETE FROM %s WHERE id=%d", $this->db_table, $this->id);
		$this->query($sql);

		// Delete files
		rmdirr(UPLOADS_ROOT.dirname($this->getUploadRelPath()));

		$this->ui_notice = 'Record deleted';
	}

	function preProcessFields() { } 
	function postProcessFields() { 
		if (empty($_FILES)) {
			if (count(array_intersect($this->mandatory_fields,array_keys($this->file_image_fields)))) throw new FatalException ("Please choose a file for upload");
			else return;
		}
		
		foreach ($this->file_image_fields as $f => $col) {
	        // If the file field is not also listed in the main field list, ignore it.
	        if (! isset($this->fields[$col])) throw new FatalException ("File $f not a field in this model");

	        if (isset($_FILES[$f]) && is_uploaded_file($_FILES[$f]['tmp_name'])) {
	            $hash = md5(microtime());
	            $file_ext = array_pop(explode('.', $_FILES[$f]['name']));
	            $filename = $hash.'.'.$file_ext;
	            $target = UPLOADSROOT.$this->getUploadRelPath($filename);
	            if (! is_dir(dirname(dirname($target)))) mkdir(dirname(dirname($target))); // parent dir
	            if (! is_dir(dirname($target))) mkdir(dirname($target)); // target dir
	            if (! move_uploaded_file($_FILES[$f]['tmp_name'], $target)) {
	                die("File upload failed");
	            }
	            chmod($target, 0755);
	            $this->update(array($col => $filename), false,false);
	        }
	    }		
	}

	function getUploadRelPath($filename = false,$alttable = false) {
		$alttable = ($alttable) ? $alttable : $this->db_table;
		return $alttable.'/'.$this->id.($filename?'/'.$filename:'');
	}
/*
	// DRY helper for file field column names
	// Returns field/column name of input file descriptor
	function fileFor($f) {
		return $f.'_path';
	}

	// returns the path to the image variant
	function imageVariantName($f,$variant) {
		return $this->fields[$f.'_'.$variant.'_path'];
	}

	// returns url of image variant for this model
	function imageURL($f,$variant = false) {
		if ($variant) return WEBROOT."img/custom/$variant/".$f; // 8/12 DONE? FIXME use exhibit for on-the-fly caching and resizing.
		else return WEBROOT."img/images/".$f;
	}
*/

	function imageURL($f,$variant = 'standard',$alttable = false) {
	    return WEBROOT."img/$variant/".$this->getUploadRelPath($f,$alttable); // 8/12 DONE? FIXME use exhibit for on-the-fly caching and resizing.
	}


	function htmlerise($in) {
		return nl2br($in);
	}

	// read aloud as 'has many $table as $label on $foreign_key
	function addHasMany($table,$label = false,$foreign_key = false,$order_by = false) {
		if (!$label) $label = $table;
		if (!$foreign_key) $foreign_key = unpluralise($this->db_table).'_id';
		// verify model for $table exists
		if (!existsModelForTable($table)) throw new FatalException ("Error when creating has-many association on {$this->db_table} with $table : $table has no defined model class.");
	
		$assoc = new HasManyAssociation($table,$label,$foreign_key,$order_by);
		$this->setAssociation($assoc);
	}

	// read aloud as 'belongs to $table as $label on $foreign_key'
	function addBelongsTo($table,$label = false,$foreign_key = false, $order_by = false) {	
		if (!$label) $label = unpluralise($table);
		$table = pluralise($table);
	    if (!$foreign_key) $foreign_key = $label.'_id';
		//if (!in_array($this->fields[$foreign_key]) || !$this->fields[$foreign_key] ) return false;
	    // verify model for $table exists
	    if (!existsModelForTable($table)) throw new FatalException ("Error when creating belongs-to association on {$this->db_table} with $table : $table has no defined model class.");
		$assoc = new BelongsToAssociation($table,$label,$foreign_key,$order_by);
		$this->setAssociation($assoc);
	}

	private function setAssociation($assoc) {
		if (isset($this->fields[$assoc->label])) {
			throw new FatalException ("Field is already set for {$assoc->label}. Please choose another alias.");
		}
		else if (isset($this->associations[$assoc->label])) {
	        throw new FatalException ("Association is already set for {$assoc->label}. Please choose another alias.");
	    }
		else {
			$this->associations[$assoc->label] = $assoc; //array('type'=>$type,'table' => $table, 'foreign_key'=>$foreign_key);
	    } 
	}


	function flexyGet($property) {
		return $this->$property;
	}

	// TODO implement has_many_to_many and has_one

	function __get($property) {
		if (isset($this->fields[$property])) return $this->fields[$property];
		else if (isset($this->associations[$property])) {
			$assoc = $this->associations[$property];
			// TODO move to Association class somehow
			switch ($assoc->type) {
				case 'belongs_to':
					
					$criteria = array('id' => $this->fields[$assoc->foreign_key]);
					$order_by = $assoc->order_by;
					$as_array = false;
					break;
				case 'has_many':
				
					$criteria = array($assoc->foreign_key=>$this->fields['id']);
					$order_by = $assoc->order_by;
					$as_array = true;
					break;
			}
			// find all of this model type
			$obj = getModelFor($assoc->table);
			$this->$property = $obj->find($criteria,$order_by,$as_array,"Failed querying {$assoc->type} association on {$assoc->table} using foreign key {$assoc->foreign_key}");
			return $this->$property;
		}
		else if (preg_match_all("#^(.+)Associations$#i",$property,$matches)) return Association::filterByType($this->associations,underscore($matches[1][0]));
		else if ($property == 'getAll') return $this->getAll();
		else throw new FatalException ("Model does not have property $property");
	}
}
/*
function makeSelectSQL ($table,$conditions = array(),$joins = false) {
	$whereclause = array();
	foreach ($conditions as $a => $c) $whereclause[] = $a."='".$c."'";
	$whereclause = implode(' AND ',$whereclause);
	return "SELECT *,  FROM $table WHERE $whereclause"; // ignore joins for now
}
*/
// convert underscore to CamelCase
function camelize($str) {
	return str_replace(' ','',ucwords(str_replace('_',' ',$str)));
}

function underscore($str) {
	return strtolower(ltrim(preg_replace("#([A-Z])#","_$1",$str),'_'));
}

class Association {
	var $type;
	var $table;
	var $label;
	var $foreign_key;
	var $order_by;
	function __construct($type,$table,$label,$foreign_key,$order_by) {
		$this->type = $type;
		$this->table = $table;
		$this->label = $label;
		$this->foreign_key = $foreign_key;
		$this->order_by = $order_by;
	}
	function getAssociated() {
	    return getModelFor($this->table);
	}
	static function filterByType($assocs,$type) {
		$res = array();
		foreach ($assocs as $a) if (strtolower($a->type) == strtolower($type)) $res[] = $a;
		return $res;
	}
}

class HasManyAssociation extends Association {
	function __construct($table,$label,$foreign_key,$order_by) {
	    parent::__construct('has_many',$table,$label,$foreign_key,$order_by);
	}
}	

class BelongsToAssociation extends Association {
	function __construct($table,$label,$foreign_key,$order_by) {
		parent::__construct('belongs_to',$table,$label,$foreign_key,$order_by);
	}	
}

/**
 * Return true if the value is exactly a whole number
 */
function is_number($val) {
	// Convert the value to an integer and back - double check integrity
	if (strval($val)===strval(intval($val))) {
	    return true;
	} else {
	    return false;
	}
}

/**
 * Delete a file, or a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.2
 * @param       string   $dirname    Directory to delete
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
function rmdirr($dirname) {
	// Sanity check
	if (!file_exists($dirname)) {
	    return false;
	}
 
	// Simple delete for a file
	if (is_file($dirname)) {
	    return unlink($dirname);
	}
 
	// Loop through the folder
	$dir = dir($dirname);
	while (false !== $entry = $dir->read()) {
	    // Skip pointers
	    if ($entry == '.' || $entry == '..') {
	        continue;
	    }
 
	    // Recurse
	    rmdirr("$dirname/$entry");
	}
 
	// Clean up
	$dir->close();
	return rmdir($dirname);
}

function daySelectOptions() {
	$out = array();
	for ($i=1;$i<=31;$i++) {
		$out[] = $i;
	}
	return $out; 
}

function monthSelectOptions() {
	$out = array();
	for ($i=1;$i<=12;$i++) {
		$out[] = $i;
	}
	return $out; 
}

function yearSelectOptions() {
	$out = array();
	for ($i=date('Y')-1;$i<=date('Y')+3;$i++) {
		$out[] = $i;
	}
	return $out; 
}

class MyDB extends iDB {
	function init() {
		;
	}
}
