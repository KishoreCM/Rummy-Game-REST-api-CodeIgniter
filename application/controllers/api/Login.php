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
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->password)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($data->email) || empty($data->password)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (!filter_var($data->email, FILTER_VALIDATE_EMAIL)){
            $this->response(array(                
                "message" => "Invalid Email",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $userId = $this->user_model->validate_user($data->email, $data->password);
            if ($userId) {
                // print_r("Logged In");
                $this->session->set_userdata('user_'.$userId, 1);
                $this->response(array(
                    "userId" => $userId,
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