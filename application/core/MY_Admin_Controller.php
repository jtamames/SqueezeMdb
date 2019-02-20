<?php class MY_Admin_Controller extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('entities/Game_User');
        // Check if the user is logged in
        $logged_in = $this->session->userdata('isLoggedIn');
        if (!isset($logged_in) || !$logged_in) {
            // No session redirect to Login
            $this->load->helper('url');
            redirect('Login');
        } else {
            $user_type = $this->session->userdata('user_type');
            if (!$user_type == USER_TYPE_ADMIN) {
                log_message('error',"Trying to access to an admin section with a non admin account");
                show_error("Trying to access to an admin section with a non admin account");
            }
        }
    }
}

?>