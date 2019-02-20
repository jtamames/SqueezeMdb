<?php

class User_model extends CI_Model {

    function __construct() {
        parent::__construct();

        $this->load->database('default');
    }

    /**
     * Creates a new user
     */
    function create_user($user) {
        $usr_arr = array("name"=>$user->name
                ,"surname"=>$user->surname
                ,"email"=>$user->email
                ,"password"=>md5($user->password)
                ,"type"=>$user->type
                ,"is_blocked"=> 0
                );
        $this->db->insert('game_user', $usr_arr);
    }
    
    function update_user($user) {
        $query = $this->db->query("UPDATE game_user SET name=?, surname=?, email=?, type=? WHERE id=?",
                array($user->name,$user->surname, $user->email, $user->type, $user->id));
    }

    function update_user_password($user_id, $password) {
        $query = $this->db->query("UPDATE game_user SET password=?, val_code=NULL, val_code_expiration=NULL WHERE id=?",
                array($password, $user_id));
        
    }
    /**
     * Check whether an email alreday exists in the database
     * @param unknown_type $email
     * @return boolean
     */
    function email_unique($email) {
        $query = $this->db->query('SELECT * FROM game_user where e_mail = \'' . $email . '\'');
        return ($query->num_rows() == 0);
    }

    /**
     * 
     * @return type
     */
    function validate_user() {
        $this->load->helper('security');
        $hashed_pass = hash('md5', $this->input->post('password'));
        $sqlQuery = 'SELECT id FROM game_user where email=? and password=?';

        $query = $this->db->query($sqlQuery, array($this->input->post('email'), $hashed_pass));

        // TODO: check if the user account is active or not

        return ($query->num_rows() > 0);
    }

    /**
     * 
     * @param string $email
     * @param string $password
     * @return User
     */
    function get_user($email, $password) {
        $query = $this->db->query('SELECT * FROM game_user where email=? and password=?', array($email, $password));
        $user = NULL;
        if ($query->num_rows() > 0) {            
            foreach ($query->result("Game_User") as $rows) {
                $user = $rows;
            }
        }

        return $user;
    }
    
    function get_user_by_email($email) {
        $query = $this->db->query('SELECT * FROM game_user where email=?', array($email));
        $user = NULL;
        if ($query->num_rows() > 0) {            
            foreach ($query->result("Game_User") as $rows) {
                $user = $rows;
            }
        }

        return $user;
    }
    
    function get_user_by_id($id) {
        $query = $this->db->query('SELECT * FROM game_user where id=?', array($id));
        $user = NULL;
        if ($query->num_rows() > 0) {            
            foreach ($query->result("Game_User") as $rows) {
                $user = $rows;
            }
        }

        return $user;
    }
    
    function get_users($user_type = NULL) {
        if (isset($user_type) AND $user_type != NULL) {
            $query = $this->db->query('SELECT * FROM game_user WHERE type=?',array($user_type));
        }
        else {
            $query = $this->db->query('SELECT * FROM game_user ');
        }
        
        return $query->result("Game_User");
    }
    
    function delete_user($user_id) {
        $this->db->query("DELETE FROM project_user WHERE game_user_ID=?",array($user_id));
        $this->db->query("DELETE FROM game_user WHERE ID=?",array($user_id));
    }
    
    function set_validation_code($user_id, $code, $code_exp) {
        $this->db->query("UPDATE game_user SET val_code=?, val_code_expiration=? WHERE ID=?",
                array($code,$code_exp,$user_id));
    }
    
    function get_user_by_val_code($code) {
        $query = $this->db->query('SELECT * FROM game_user where val_code=?', array($code));
        $user = NULL;
        if ($query->num_rows() > 0) {            
            foreach ($query->result("Game_User") as $rows) {
                $user = $rows;
            }
        }

        return $user;        
    }
}