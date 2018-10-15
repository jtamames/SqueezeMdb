<?php

class Reset_password extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

    function index() {
        log_message('debug', "RESETTTT");
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->model('User_model');
        $this->form_validation->set_rules('email', 'Email', 'trim|strtolower|required|valid_email');

        if ($this->form_validation->run() === FALSE) {
            $this->load->view('templates/header');
            $this->load->view('reset_password');
            $this->load->view('templates/footer');
        } else {
            $email = $this->input->post("email");
            log_message('debug', "::::::::::> post {$email}");
            $user = $this->User_model->get_user_by_email($email);
            if (!isset($user) || $user == NULL) {
                $error = "Invalid email";
                $this->load->view('templates/header');
                $this->load->view('reset_password', array("error" => $error));
                $this->load->view('templates/footer');
            } else {
                $code = hash("sha256", rand(0, 1000000));
                // update data
                $date = date("Y-m-d");
                $date = strtotime(date("Y-m-d", strtotime($date)) . " +1 day");
                $this->User_model->set_validation_code($user->ID, $code, $date);

                // send email
                $config['protocol'] = 'sendmail';
                $config['mailpath'] = '/usr/sbin/sendmail';
                $config['charset'] = 'iso-8859-1';
                $config['wordwrap'] = TRUE;

                $this->load->library('email');
                $this->email->initialize($config);
                $this->email->from('luis.cornide@gmail.com', 'GAme admin');
                $this->email->to($user->email);
                $this->email->subject('Email Test');
                $message = "<h2>Forgot your password?</h2>
<p>We got a request to change the password for the account with the username xolomon.
<p>If you don't want to reset your password, you can ignore this email.
<p>If you didn't request this change, please contact the GAme administrator.
<p>To complete the process, please follow this link: 
<a href='" . site_url("reset_password/validate{$code}") . "'>" . site_url("reset_password/validate/{$code}") . "</A>";
                $this->email->message($message);

                $res = $this->email->send();
                log_message('debug', "send: {$res}");
            }
        }
    }

    function validate($val_code = NULL) {
        $error_msg = NULL;
        if (!isset($val_code) && $val_code == NULL) {
            $error_msg = "Missing validation code";
        } else {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
            $this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required|matches[password]');
            $this->load->model('User_model');
            $user = $this->User_model->get_user_by_val_code($val_code);

            if (!isset($user) || $user == NULL) {
                $error_msg = "Validation code not found";
            } else {
                if ($user->val_code_expiration < date("Y-m-d") && 1 != 1) {
                    $error_msg = "This validation code has expired";
                } else {
                    if ($this->form_validation->run() == FALSE) {
                        log_message("debug", ":::::::> new password");
                        $this->load->view('templates/header');
                        $this->load->view('new_password', array("val_code" => $val_code, "user_id" => $user->ID));
                        $this->load->view('templates/footer');
                    } else {
                        $this->User_model->update_user_password($user->ID, md5($this->input->post("password")));
                        $this->load->view('templates/header');
                        $this->load->view('');
                        $this->load->view('templates/footer');
                    }
                }
            }
        }
    }

}
