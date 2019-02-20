<?php

class Login extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    function index($id = NULL, $red_url = NULL) {
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->model('User_model');
        if (isset($id) && $id != NULL && strtolower($id) !== 'null') {
            $data['message'] = line("msg_account_act");
            $data['cadena'] = $id;
            $new = true;
        } else {
            $data['message'] = '';
            $data['cadena'] = '';
            $data['red_url'] = $red_url;
            $new = false;
        }

        // Setting validations rules
        $this->form_validation->set_rules('email', 'Email', 'trim|strtolower|required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|md5|callback_validate_credentials[email]');

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('templates/header');
            $this->load->view('login', $data);
            $this->load->view('templates/footer');
        } else {
            $red_url = $this->input->post("red_url");
            if ($this->input->post("val_cod") > "A") {
                // Retrieve user data
                $user = $this->User_model->get_new_user($this->input->post("email"), $this->input->post("password"), $this->input->post("val_cod"));
            } else {
                $user = $this->User_model->get_user($this->input->post("email"), $this->input->post("password"));
            }
            // Create session
            $this->load->library('session');
            $this->session->set_userdata('isLoggedIn', TRUE);
            $this->session->set_userdata('user', $user->ID);
            $this->session->set_userdata('user_type', $user->type);

            // Redirect to the home
            $this->load->helper('url');
            if (isset($red_url) && $red_url != NULL) {
                $url = base64_decode(urldecode($red_url));
                redirect($url);
            } else {
                if ($user->type == USER_TYPE_ADMIN) {
                    redirect('admin/Home');
                } else {
                    redirect('user/Home');
                }
                
            }
        }
    }

    function validate_credentials($password, $email) {
        $result = TRUE;
        // Validates user credentials
        if (!$this->User_model->validate_user()) {
            $this->form_validation->set_message('validate_credentials', "Invalid email or password");
            $result = FALSE;
        }

        return $result;
    }

}
