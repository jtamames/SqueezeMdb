<?php

class Users extends MY_Admin_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }
    
    function index() {
         $this->load->model('User_model');
        
        $data['users'] = $this->User_model->get_users();
        $data['section'] = 'users';
        $this->load->view('templates/header',$data);
        $this->load->view('users/list',$data);
        $this->load->view('templates/footer',$data);
    }
    
    function block($user_id) {
        if (!isset($user_id) || $user_id == NULL) {
            // TODO: handle error
        }
        else {
            $this->load->model('User_model');
            $user = $this->User_model->get_user($user_id);
            $user->is_blocked = 1;
            $this->User_model->update_user($user);
            
            redirect("admin/Users");
        }
    }

    function create() {
        $this->load->model('User_model');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', 'Name', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required|matches[password]');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|callback_validate_email');
        $this->form_validation->set_rules('type', 'User Type', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $data = array();
            $data['section'] = 'users';
            $this->load->view('templates/header',$data);
            $this->load->view('users/form',$data);
            $this->load->view('templates/footer',$data);
        }
        else {
            $user = new Game_User();
            $user->name = $this->input->post("name");
            $user->surname = $this->input->post("surname");
            $user->email = $this->input->post("email");
            $user->type = $this->input->post("type");
            // Create new user
            $user->password = $this->input->post("password");
            $this->User_model->create_user($user);
            
            redirect("admin/Users");
        }
    }
    
    function edit($user_id = NULL) {
        $this->load->model('User_model');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', 'Name', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('type', 'User Type', 'required');
        
        if ($this->form_validation->run() == FALSE)
        {
            $data = array();
            $user = $this->User_model->get_user_by_id($user_id);                
            $data['user'] = $user;
            $data['section'] = 'users';
            $this->load->view('templates/header',$data);
            $this->load->view('users/form',$data);
            $this->load->view('templates/footer',$data);
        }
        else {
            $uid = $this->input->post("id");
            $user = new Game_User();
            $user->name = $this->input->post("name");
            $user->surname = $this->input->post("surname");
            $user->email = $this->input->post("email");
            $user->type = $this->input->post("type");
            // Update user data
            $user->id = $uid;
            $this->User_model->update_user($user);
            redirect("admin/Users");
        }
    }
    
    function delete($user_id) {
        log_message("debug", ":::::> User_id: {$user_id}");
        if (!isset($user_id) && $user_id == NULL) {
            log_message("dedbug", "Missing parameter user_id in delete user");
        } else {
            $this->load->model("User_model");
            $this->User_model->delete_user($user_id);
        }
        redirect("admin/Users");
    }
    
    function validate_email($email) {
        $result = TRUE;
        $this->load->model("User_model");
        
        $user = $this->User_model->get_user_by_email($email);
        if (isset($user) && $user != NULL) {
            $result = FALSE;
        }
        return $result;
    }
}
