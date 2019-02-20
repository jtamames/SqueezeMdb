<?php

class Projects extends MY_User_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    function index() {
        $this->load->model('Project_model');

        $user = $this->session->userdata("user");
        $data['projects'] = $this->Project_model->get_user_projects($user);
        $data['section'] = 'projects';
        $this->load->view('templates/header', $data);
        $this->load->view('projects/list', $data);
        $this->load->view('templates/footer', $data);
    }

}
