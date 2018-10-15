<?php

class Home extends MY_User_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }
    
    function index() {
        redirect("user/projects");
    }
    
}