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

        // $data = json_decode(file_get_contents("php://input"));
        $name = $this->input->post("name");
        $email = $this->input->post("email");
        $password = $this->input->post("password");

        $this->form_validation->set_rules("name", "Name", "required");
        $this->form_validation->set_rules("email", "Email", "required|valid_email");
        $this->form_validation->set_rules("password", "Password", "required");
      
        if ($this->form_validation->run() === FALSE) {
            $this->response(array(                
                "message" => "All Field(s) are Either Needed or Not Valid",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($name) || empty($email) || empty($password)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif(strlen($password) < 5) {
            $this->response(array(                
                "message" => "Password Length Should Be Atleast of Length 5",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif($this->user_model->is_exist($email)){
            $this->response(array(                
                "message" => "An User With this Email is Already Been Registered. Try Loggin In",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $encrypted_password = password_hash($password, PASSWORD_DEFAULT);            

            $user = array(
                "name" => $name,
                "email" => $email,
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