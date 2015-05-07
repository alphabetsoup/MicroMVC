<?
class AdminController extends BaseController {

	var $id;
	var $action;

	var $obj;
	var $name = 'All';
	var $form_template;
//	var $view_template;
//	var $list_template;

	// DB tables we will administrate from here
	var $tables = array("galleries","images","products","downloadables");

	

	function init() {
		$this->name   = $this->table = requireArgument('table');
        $this->id     = getArgument('id',false);
        $this->action = getArgument('action','index');

		$this->obj = getModelFor($this->table); // active migration???
		
		// Pass the ID through to the object model too, if we have one
        if (is_object($this->obj) && $this->id) {
            //$this->obj->id = $this->id;
			$this->obj = $this->obj->find($this->id);
			
        }
		$this->setLayout('admin.html');

		$this->form_template = $this->table.'_form.html';
	}

	function ucfirst($str) { echo ucfirst($str); }

	//function unpluralise($str) { echo str_ireplace(array("ies","s"),array("y",""),$str); }

	function unpluralise($str) { echo unpluralise($str); }

	function getAdminURL($tablename,$action = '',$id = false) {
		echo $this->webroot."admin/".$tablename."/".$action.(($id)?'/'.$id:'');
	}

/*
	function start() {
		$this->addElements($this->obj->fields);

		if (! empty($_POST)) {
			$this->addElements($this->cleanSource($_POST));

			switch ($this->action) {
				case 'edit':
					if ($this->obj->update($this->cleanSource($_POST))) {
						$this->notices[] = 'Changes saved';
					} else {
						$this->errors[] = $this->obj->ui_error;
					}
					break;
				case 'add':
					if ($new_id = $this->obj->insert($this->cleanSource($_POST))) {
						$this->notices[] = $this->name . ' added';
						$this->addElements($this->obj->fields, true); // Reset elements for a fresh form
					} else {
						$this->errors[] = $this->obj->ui_error;
					}
					break;
			}
		}
	}
*/
	function edit() {
		global $params;
		//$this->setWrapper('form_wrapper.html');

		if (!empty($_POST)) {
			$this->addElements($params);
		}
		else {
			$this->addElements($this->obj->fields);
		}
		/* DEPRECIATED - now handled in $this->distillElements() called by $this->render()
		foreach ($this->obj->belongsToAssociations as $assoc) {
			if (isset($this->obj->fields[$assoc->foreign_key])) 
				$this->getElement($assoc->foreign_key)->setOptions($assoc->getAssociated()->getAllAsSelectable());
		}*/

		if (!empty($_POST)) {
			if ($this->obj->update($this->cleanSource($params))) {
				$this->notices[] = 'Changes saved';
			} else {
				$this->errors[] = $this->obj->ui_error;
			}
		}
		$this->render();
	}

	function add() {
		global $params;
		
		/*
		$select_lists = array();
		foreach ($this->obj->belongsToAssociations as $assoc) {
            if ($assoc->type == 'belongs_to' && isset($this->obj->fields[$assoc->foreign_key]))
                $select_lists[$assoc->foreign_key] = $assoc->getAssociated()->getAllAsSelectable();
        }
		
		$this->addElements($select_lists);
		*/

		//$this->setWrapper('form_wrapper.html');
		if (!empty($_POST)) {
			$this->addElements($params);
			if ($new_id = $this->obj->insert($this->cleanSource($params))) {
				$this->notices[] = $this->name . ' added';
				$this->obj = getModelFor($this->table);
				$this->addElements($this->obj->fields, true); // Reset elements for a fresh form
			} else {
				$this->errors[] = $this->obj->ui_error;
			}
		}
		$this->render();
	}

	function distillElements($elements,$template_elements) {
		// belongsTo associations specified in template
		//  as select tags need the appropriate options.
		$belongsToAssociations = $this->obj->belongsToAssociations;

		// attempt to find element and add options
		foreach ($this->obj->belongsToAssociations as $assoc) {
        	if (isset($template_elements[$assoc->foreign_key]) && $template_elements[$assoc->foreign_key]->tag == 'select') {
       			if (!isset($elements[$assoc->foreign_key])) 
				{
					$elements[$assoc->foreign_key] = new HTML_Template_Flexy_Element();	
				}
				$elements[$assoc->foreign_key]->setOptions($assoc->getAssociated()->getAllAsSelectable());
       		}
		}
		return $elements;
	}

	function delete() {
		global $params;
	
		if ($this->obj->id) $this->obj->delete();
		$this->redirectTo('index',false,$this->table);
	}


	function index() {
		//$this->records = $this->obj->getAll();		
		$this->records = $this->obj->find(false,false,true); //findAll
		$this->render();
	}

/*	function renderForm() {
		$form = new HTML_Template_Flexy($this->getFlexyOptions());
		// form_template will be either ->view_template or
		// ->form_template depending on whether we're viewing values or
		// making changes, respectively. It is set in the method that controls
		// the page (e.g. showSuccess, showNonCredit, showFail)
		$form->compile($this->form_template);
		$form->outputObject($this, $this->elements);
	}*/
	
	function isEditing() {
		if (isset($this->action) and ($this->action=='edit')) {
			return true;
		} else {
			return false;
		}
	}
}

