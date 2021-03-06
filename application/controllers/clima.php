<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Clima extends CI_Controller {

	public function __construct(){
		parent::__construct();

		$this->load->database();
		$this->load->helper('url');
		$this->load->helper('language');
		$this->load->library('grocery_CRUD');
		$this->load->library('ion_auth');
	}

	public function index(){
		if (!$this->ion_auth->logged_in()){ 
			redirect('clima/login', 'refresh');
		}
		else{
			$this->estaciones();
		}
	}

	public function recuperar_clave(){
		$message = $this->session->flashdata('message');
		$this->form_validation->set_rules('email', 'Correo electrónico', 'valid_email|required');
		$this->form_validation->set_rules('captcha', 'Código de verificación', 'callback_captcha_check');
		if ($this->form_validation->run()){
			if ($this->_valid_csrf_nonce()){
				if ($this->ion_auth->email_check($this->input->post('email'))){
					if ($this->ion_auth->forgotten_password($this->input->post('email'))){
						$this->session->set_flashdata('message', $this->ion_auth->messages());
						redirect('clima/login', 'refresh');
					}
					else{ $message = $this->ion_auth->errors(); }
				} else{	$message = 'La dirección de email no está registrada.'; }
			} else{	$message = $this->lang->line('error_csrf'); }
		}
		$this->load->view('recuperar_clave.php', array(
			'captcha' => $this->_captcha(),
			'csrf' => $this->_get_csrf_nonce(),
			'message' => (validation_errors()) ? validation_errors() : $message,
		));
	}

	public function cambiar_clave($code = NULL){
		if (!$code){ show_404(); }
		$user = $this->ion_auth->forgotten_password_check($code);
		if (!$user) {
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect("clima/recuperar_clave", 'refresh');
		}

		$message = $this->session->flashdata('message');
		$this->form_validation->set_rules('pass', $this->lang->line('edit_user_validation_password_label'), 'required|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']');
		$this->form_validation->set_rules('pass2', $this->lang->line('edit_user_validation_password_label'), 'required|matches[pass]');
		if ($this->form_validation->run()){
			if ($this->_valid_csrf_nonce() && $user->id == $this->input->post('user_id')){
				$identity = $user->{$this->config->item('identity', 'ion_auth')};
				if ($this->ion_auth->reset_password($identity, $this->input->post('pass'))){
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					redirect('clima/login', 'refresh');
				} else { $message = $this->ion_auth->errors(); }
			} else { $message = $this->lang->line('error_csrf'); }
		}
		$this->load->view('cambiar_clave.php', array(
			'user_id' => $user->id,
			'code' => $code,
			'csrf' => $this->_get_csrf_nonce(),
			'message' => (validation_errors()) ? validation_errors() : $message,
		));
	}

	public function login(){
		$message = $this->session->flashdata('message');
		$this->form_validation->set_rules('identity', 'Identity', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required');
		if ($this->form_validation->run()){
			$remember = (bool) $this->input->post('remember');
			if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember)){
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect('clima', 'refresh');
			}
			else { $message = $this->ion_auth->errors(); }
		}
		$this->load->view('login.php', array(
			'message' => (validation_errors()) ? validation_errors() : $message,
		));
	}

	public function logout(){
		$this->ion_auth->logout();
		$this->session->set_flashdata('message', $this->ion_auth->messages());
		redirect('clima/login', 'refresh');
	}

	public function resumenes_diarios(){
		if (!$this->ion_auth->logged_in()){ redirect('clima/login', 'refresh'); }

		$crud = new grocery_CRUD();

		$crud->set_table('dailysummary');
		$crud->set_subject('Resumen Diario');
		$crud->set_relation('idStation','stations','location');
		$crud->unset_add();
    		$crud->unset_edit();
		$crud->unset_delete();

		$crud->columns('dates','idStation','mintempm','maxtempm','meantempm','minhumidity','maxhumidity','humidity','minpressurem','maxpressurem','meanpressurem','minwspdm','maxwspdm','meanwindspdm','meanwdird','precipm','heatingdegreedays');
		$crud->display_as('dates','Fecha')
		     ->display_as('idStation','Estación')
		     ->display_as('mintempm','Temp.Mín. [°C]')
		     ->display_as('maxtempm','Temp.Máx. [°C]')
		     ->display_as('meantempm','Temp.Med. [°C]')
		     ->display_as('minhumidity','Hum.Mín. [%]')
		     ->display_as('maxhumidity','Hum.Máx. [%]')
		     ->display_as('humidity','Hum.Med. [%]')
		     ->display_as('minpressurem','Pres.Mín. [mBar]')
		     ->display_as('maxpressurem','Pres.Máx. [mBar]')
		     ->display_as('meanpressurem','Pres.Med. [mBar]')
		     ->display_as('minwspdm','Viento Mín. [km/h]')
		     ->display_as('maxwspdm','Viento Máx. [km/h]')
		     ->display_as('meanwindspdm','Viento Med. [km/h]')
		     ->display_as('meanwdird','Dirección del Viento Med. [km/h]')
		     ->display_as('precipm','Lluvia caída [mm]')
		     ->display_as('heatingdegreedays','Índice de calor [°C]');
		$crud->order_by('dates','desc');

		$this->load->view('clima.php',array(
			'gc' => $crud->render(),
			'page' => 'resumenes',
		));
	}

	public function estaciones(){
		if (!$this->ion_auth->logged_in()){ redirect('clima/login', 'refresh'); }

		$crud = new grocery_CRUD();

		$crud->set_table('stations');
		$crud->set_subject('Estación');
		$crud->unset_add();
    		$crud->unset_edit();
		$crud->unset_delete();

		$crud->columns('city','location','wmo','lon','lat');
		$crud->display_as('idStation','ID')
		     ->display_as('city','Ciudad')
		     ->display_as('location','Localidad')
		     ->display_as('lon','Longitud')
		     ->display_as('lat','Latitud')
		     ->display_as('wmo','Identificador WU');
		$crud->order_by('city','asc');

		$this->load->view('clima.php',array(
			'gc' => $crud->render(),
			'page' => 'estaciones',
		));
	}

	public function muestras(){
		if (!$this->ion_auth->logged_in()){ redirect('clima/login', 'refresh'); }

		$crud = new grocery_CRUD();

		$crud->set_table('samples');
		$crud->set_subject('Muestra');
		$crud->set_relation('idStation','stations','location');
		$crud->unset_add();
    		$crud->unset_edit();
		$crud->unset_delete();

		$crud->columns('dates','idStation','tempm','windchillm','heatindexm','hum','wspdm','wgustm','wdird','pressurem','precimp');
		$crud->display_as('dates','Fecha')
		     ->display_as('idStation','Estación')
		     ->display_as('tempm','Temperatura [°C]')
		     ->display_as('windchillm','Sensación térmica [°C]')
		     ->display_as('heatindexm','Índice de calor [°C]')
		     ->display_as('hum','Humedad [%]')
		     ->display_as('wspdm','Velocidad del viento [km/h]')
		     ->display_as('wgustm','Ráfagas del viento [km/h]')
		     ->display_as('wdird','Dirección del viento [grados]')
		     ->display_as('pressurem','Presión atmosférica [mBar]')
		     ->display_as('precimp','Precipitación [mm]');
		$crud->order_by('dates','desc');

		$this->load->view('clima.php',array(
			'gc' => $crud->render(),
			'page' => 'muestras',
		));
	}

	public function capturas(){
		if (!$this->ion_auth->logged_in()){ redirect('clima/login', 'refresh'); }

		$crud = new grocery_CRUD();

		$crud->set_table('captures');
		$crud->set_subject('Capturas');
		$crud->set_relation('idStation','stations','location');
		$crud->unset_add();
    		$crud->unset_edit();
		$crud->unset_delete();

		$crud->columns('dates','idStation');
		$crud->display_as('idCapture','ID')
		     ->display_as('idStation','Estación')
		     ->display_as('dates','Fecha');
		$crud->order_by('dates','desc');

		$this->load->view('clima.php',array(
			'gc' => $crud->render(),
			'page' => 'capturas',
		));
	}

	public function pronosticos(){
		if (!$this->ion_auth->logged_in()){ redirect('clima/login', 'refresh'); }

		$crud = new grocery_CRUD();

		$crud->set_table('forecasts');
		$crud->set_subject('Pronóstico');
		$crud->set_relation('idCapture','captures','dates');
		$crud->set_relation('idStation','stations','location');
		$crud->unset_add();
    		$crud->unset_edit();
		$crud->unset_delete();

		$crud->columns('idCapture','idStation','dates','temp','wspd','wdir','humidity','windchill','heatindex','feelslike','mslp','pop');
		$crud->display_as('idCapture','Fecha de captura')
		     ->display_as('idStation','Estación')
		     ->display_as('dates','Fecha')
		     ->display_as('temp','Temperatura [°C]')
		     ->display_as('wspd','Velocidad del viento [km/h]')
		     ->display_as('wdir','Dirección del viento [grados]')
		     ->display_as('humidity','Humedad [%]')
		     ->display_as('windchill','Sensación térmica [°C]')
		     ->display_as('heatindex','Índice de calor [°C]')
		     ->display_as('feelslike','Feelslike')
		     ->display_as('mslp','Mslp')
		     ->display_as('pop','Probabilidad de precipitación');
		$crud->order_by('dates','desc');

		$this->load->view('clima.php',array(
			'gc' => $crud->render(),
			'page' => 'pronosticos',
		));
	}

	public function usuarios(){
		if (!$this->ion_auth->logged_in()){ redirect('clima/login', 'refresh'); }

		$crud = new grocery_CRUD();
		
		$crud->set_table('users');
		$crud->set_subject('Usuario');
		$crud->set_relation_n_n('groups', 'users_groups', 'groups', 'user_id', 'group_id', 'name');
		$crud->unset_fields('password','pass','pass2','salt','activation_code', 'forgotten_password_code', 'forgotten_password_time', 'remember_code', 'first_name','last_name','company','phone');
		$crud->add_fields('username','email','password');
		$crud->edit_fields('username','email','pass','pass2','active');
		if ($this->ion_auth->is_admin()){
			$crud->add_fields('username','email','password','groups');
			$crud->edit_fields('username','email','pass','pass2','groups','active');
		}
		else{
			$crud->add_fields('username','email','password');
			$crud->edit_fields('username','email','pass','pass2','active');
		}
		$crud->field_type('pass', 'password');
		$crud->field_type('pass2', 'password');
		$crud->field_type('password', 'password');
		$crud->unique_fields('username','email');
		$crud->required_fields('username','email','activo','groups','password');
 
		$crud->callback_delete(array($this,'_delete_user'));
		$crud->callback_insert(array($this,'_insert_user'));
		$crud->callback_update(array($this,'_update_user'));
		$crud->callback_column('created_on', array($this,'_callback_date'));
 
		$crud->set_rules('username','Nombre de usuario','alpha_numeric|required');
		$crud->set_rules('email','Correo electrónico','valid_email|required');
		$crud->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']');
		$crud->set_rules('pass', $this->lang->line('edit_user_validation_password_label'), 'max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[pass2]');

		$crud->columns('username','email','created_on','active');
		$crud->order_by('username','asc');
		$crud->display_as('username','Nombre de usuario')
		     ->display_as('email','Correo electrónico')
		     ->display_as('createon','Fecha de alta')
		     ->display_as('pass','Contraseña')
		     ->display_as('pass2','Confirmar contraseña')
		     ->display_as('active','Estado')
		     ->display_as('groups','Grupos');

		$this->load->view('clima.php',array(
			'gc' => $crud->render(),
			'page' => 'usuarios',
		));
	}

	public function _callback_date($value, $row){
		return ((int)$value != 0)?date("Y-m-d H:i:s", (int)$value):'-';
	}

	public function _delete_user($primary_key){
		if ($primary_key == $u = $this->ion_auth->user()->row()->id){
			return FALSE;
		}
    		return $this->ion_auth->delete_user($primary_key);
	}

	public function _insert_user($post) {
		if (isset($post['groups']) && !empty($post['groups'])) 
			$groups = $post['groups'];
		else{
			$groups = array('1');
		}
		return $this->ion_auth_model->register($post['username'], $post['password'], $post['email'], array(), $groups);
	}

	public function _update_user($post, $primary_key) {
		//si no es el admin o no es la cuenta del usuario logueado
		if (!$this->ion_auth->is_admin() && $this->ion_auth->user()->row()->id != $primary_key){
			return FALSE;
		}
		$data = array(
			'username'   => $this->input->post('username'),
			'email'      => $this->input->post('email'),
		);
		//update status if user is admin and is not his own account
		if ($this->ion_auth->is_admin() && $this->ion_auth->user()->row()->id != $primary_key){
			$data['active'] = $this->input->post('active');
		}
		//update the password if it was posted
		if ($this->input->post('pass')){
			$data['password'] = $this->input->post('pass');
		}
		// Only allow updating groups if user is admin
		if ($this->ion_auth->is_admin()){
			//Update the groups user belongs to
			$this->ion_auth->remove_from_group(array(), $primary_key);
			if ($this->input->post('groups')){
				$this->ion_auth->add_to_group($this->input->post('groups'), $primary_key);
			}
			//si era administrador, seguire siendolo (para prevenir quedar sin admin)
			if ($this->ion_auth->user()->row()->id == $primary_key){
				$this->ion_auth->add_to_group(array('1'), $primary_key);
			}
		}
		//check to see if we are updating the user
	   	return $this->ion_auth->update($primary_key, $data);
	}

	function _get_csrf_nonce(){
		$this->load->helper('string');
		$key = random_string('alnum', 8);
		$value = random_string('alnum', 20);
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);
		return array($key => $value);
	}

	function _valid_csrf_nonce(){
		return ($this->input->post($this->session->flashdata('csrfkey')) && 
			$this->input->post($this->session->flashdata('csrfkey')) == $this->session->flashdata('csrfvalue'));
	}

	function _captcha(){
		$expiration = $this->config->item('captcha_expiration');
		$path = $this->config->item('captcha_patch');
		// First, delete old captchas
		list($usec, $sec) = explode(" ", microtime());
		$time = ((float)$usec + (float)$sec) - $expiration;
		$result = $this->db->query("SELECT captcha_time FROM captcha WHERE captcha_time < ".$time)->result_array(); 
		foreach ($result as $r){
			try{ unlink('./'.$path.$r['captcha_time'].'.jpg'); }
			catch(Exception $e){ }
		}
		$this->db->query("DELETE FROM captcha WHERE captcha_time < ".$time);
		$this->load->helper('captcha');
		$cap = create_captcha(array(
		    'img_path' => './'.$path,
		    'img_url' => site_url($path).'/', 
		    'img_width' => '150',
		    'img_height' => 30,
		    'expiration' => $expiration,
		    //'font_path' => './fonts/texb.ttf',
		));
		$query = $this->db->insert_string('captcha', array(
		    'captcha_time' => $cap['time'],
		    'ip_address' => $this->input->ip_address(),
		    'word' => $cap['word']
		));
		$this->db->query($query);
		return $cap['image'];
	}

	function captcha_check($str){
		if (!$str){
			$this->form_validation->set_message('captcha_check', 'De ingresar el código de verificación.');
			return FALSE;
		}
		list($usec, $sec) = explode(" ", microtime());
		$time = ((float)$usec + (float)$sec) - $this->config->item('captcha_expiration');
		$result = $this->db->query("SELECT COUNT(*) AS count FROM captcha WHERE word = ? AND ip_address = ? AND captcha_time > ?", 
			array($this->input->post('captcha'), $this->input->ip_address(), $time));
		if ($result->row()->count == 0){
			$this->form_validation->set_message('captcha_check', 'El código de verificación es incorrecto.');
			return FALSE;
		}
		return TRUE;
	}

}
