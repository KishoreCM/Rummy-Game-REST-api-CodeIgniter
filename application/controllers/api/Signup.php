<?php
require APPPATH.'libraries/REST_Controller.php';

class Signup extends REST_Controller {


    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->model(array("api/user_model"));  
        $this->load->library(array("form_validation"));              
    }


    public function index_post() {
        // echo "rummygame POST method";

        $data = json_decode(file_get_contents("php://input"));
      
        if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($data->name) || empty($data->email) || empty($data->password)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (!filter_var($data->email, FILTER_VALIDATE_EMAIL)){
            $this->response(array(                
                "message" => "Invalid Email",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif(strlen($data->password) < 5) {
            $this->response(array(                
                "message" => "Password Length Should Be Atleast of Length 5",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif($this->user_model->is_exist($data->email)){
            $this->response(array(                
                "message" => "An User With this Email is Already Been Registered. Try Loggin In",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $encrypted_password = password_hash($data->password, PASSWORD_DEFAULT);            

            $user = array(
                "name" => $data->name,
                "email" => $data->email,
                "password" => $encrypted_password
            );
            if ($this->user_model->insert_user($user)) {
                $this->response(array(                    
                    "message" => "User Registered",                    
                ), REST_Controller::HTTP_CREATED);
            } else {
                $this->response(array(                    
                    "message" => "Failed To Create User",
                ), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

    }
}

?>