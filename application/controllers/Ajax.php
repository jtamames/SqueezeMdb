<?php

class Ajax extends CI_Controller {

    static protected $search_ops = [
        "string" => ["Equals","Contains","Starts with"]
        ,"numeric" => ["=", ">", ">=", "<", "<=", "<>"]
    ];
    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }
    
    function get_table_fields($table) {
        $model = $this->session->userdata("search_model");
        
        $tables = $model[$table];
        
        echo json_encode(array_keys($tables));
    }
    
    function get_operators($table, $field) {
        $model = $this->session->userdata("search_model");
        
        $type = $model[$table][urldecode($field)]["type"];
        echo json_encode(self::$search_ops[$type]);
    }
}