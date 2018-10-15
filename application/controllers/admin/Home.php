<?php

class Home extends MY_Admin_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }
    
    function index() {
        redirect("admin/projects");
    }
    
}