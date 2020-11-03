<?php
require APPPATH.'libraries/REST_Controller.php';

class Logout extends REST_Controller {
    
    public function __construct() {
        parent::__construct();                        
        $this->load->library('session');
    }    


    public function index_post() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->userId)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($data->userId)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->session->unset_userdata('user_'.$data->userId);
            $this->response(array(  
                "status" => "Logout Success",
                "message" => "Session Expired",                
            ), REST_Controller::HTTP_OK);
        }
    }
}

?>