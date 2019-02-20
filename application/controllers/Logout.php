<?php

class Logout extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    function index() {
        $type = $this->session->userdata('user_type');
        $user = $this->session->userdata('user');

        // Remove user session
        $this->load->library('session');
        $this->session->set_userdata('isLoggedIn', FALSE);
        $this->session->unset_userdata('user');
        $this->session->unset_userdata('user_type');
        $this->session->sess_destroy();

        // Redirect to view
        redirect("Login");
        
    }
}