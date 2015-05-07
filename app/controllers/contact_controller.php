<?
class ContactController extends BaseController {
    function init() {
        $this->setLayout('default.html');

		// for navigation - TODO make DRY for all public facing controllers
		$this->galleries = iDBGallery::findAll(array('active'=>'y'));

		$this->gallery = $this->galleries[rand(0,count($this->galleries)-1)];
	}

	function sanitizeString($s) {
		//return htmlspecialchars(stripslashes($s));
		return stripslashes($s);
	}

	function validateEmail($email) {
		if(eregi("^[a-z0-9+\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,3}$", $email)) return TRUE;
		else return FALSE;
	}

    function index() {
		if ($_POST) {
			//send mail
			$recipient = 'instajungle@eml.cc';
			$mail_fields = array('email','name','enquiry');	
			$crlf = "\r\n";

			$headers  = 'MIME-Version: 1.0' . $crlf;
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . $crlf;

			// Additional headers
			$headers .= 'To: '. $recipient . $crlf;

			if ($this->validateEmail($_POST['email'])) {
				$headers .= 'From: '.$this->sanitizeString($_POST['email']).$crlf;
			} else {
				$headers .= 'From: nobody@instajungle.com'.$crlf;
			}
			
			$body = '';

			foreach($mail_fields AS $field) {
				if (isset($_POST[$field])) {
					$body .= ucfirst(str_replace('_',' ',$this->sanitizeString($field))).': '.$this->sanitizeString($_POST[$field])."\r\n";
				}
			}
			
			if (mail($recipient, 'Enquiry from website', $body, $headers)) {
				$this->redirectTo('thanks');
			} else {
				$this->redirectTo('sorry');
			}
		}
		$this->render();
    }

	function thanks() {
		$this->render();
	}
	function sorry() {
		$this->render();
	}
}

