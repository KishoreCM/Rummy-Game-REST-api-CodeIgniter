<?php
require APPPATH.'libraries/REST_Controller.php';

class Login extends REST_Controller {


    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->model(array("api/user_model"));  
        $this->load->library(array("form_validation"));      
        $this->load->library('session');
    }    

    public function index_post() {
        // $data = json_decode(file_get_contents("php://input"));

        $email = $this->input->post("email");
        $password = $this->input->post("password");

        $this->form_validation->set_rules("email", "Email", "required|valid_email");
        $this->form_validation->set_rules("password", "Password", "required");

        if ($this->form_validation->run() === FALSE) {
            $this->response(array(                
                "message" => "All Field(s) are Either Needed or Not Valid",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($email) || empty($password)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $userId = $this->user_model->validate_user($email, $password);
            if ($userId) {
                // print_r("Logged In");
                $user = $this->user_model->get_user($userId);
                $this->session->set_userdata('user_'.$userId, 1);
                $this->response(array(
                    "userId" => $userId,
                    "userName" => $user[0]->name,
                    "status" => "Login Success",
                    "message" => "Session Initiated",                
                ), REST_Controller::HTTP_OK);                
            } else {
                $this->response(array(                
                    "status" => "Login Failed",
                    "message" => "Invalid Credentials Entered",                
                ), REST_Controller::HTTP_BAD_REQUEST);
            }
        }
    }
}



?>