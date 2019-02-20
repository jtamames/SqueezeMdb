<?php

class MY_User_Controller extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
        // Check if the user is logged in
        $logged_in = $this->session->userdata('isLoggedIn');
        if (!isset($logged_in) || !$logged_in) {
            // No session redirect to Login
            $this->load->helper('url');
            redirect('Login');
        }
    }

}

?>