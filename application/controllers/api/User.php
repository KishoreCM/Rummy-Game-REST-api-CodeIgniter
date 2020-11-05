<?php
require APPPATH.'libraries/REST_Controller.php';

class User extends REST_Controller {


    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->model(array("api/user_model"));  
        $this->load->library(array("form_validation"));      
        $this->load->library('session');
    }

    public function index_get() {            
        // echo "rummygame GET method";
        $id = $this->uri->segment(3);
        if ($id){
            $user = $this->user_model->get_user($id);
            if (count($user) > 0) {                
                $this->response(array(               
                    "message" => "User Found",
                    "data" => $user
                ), REST_Controller::HTTP_OK);
            } else {
                $this->response(array(                
                    "message" => "No User Found",
                    "data" => $user
                ), REST_Controller::HTTP_NOT_FOUND);
            }
            
        } else {            
            $users = $this->user_model->get_users();        
            if (count($users) > 0) {
                $this->response(array(               
                    "message" => count($users)." User(s) Found",
                    "data" => $users
                ), REST_Controller::HTTP_OK);
            } else {
                $this->response(array(                
                    "message" => "No User(s) Found",
                    "data" => $users
                ), REST_Controller::HTTP_NOT_FOUND);
            }
        }                    


        ///////////////////////////////////////////////////////////////////////////////////////////////
        // $card_values = [1, 2, 3, 4];

        // $matches = array();

        // $match = array(
        //     "score" => 10,
        //     "card_value" => $card_values
        // );

        // array_push($matches, $match);

        // // print_r(json_encode($matches));
        // $this->response($matches, REST_CONTROLLER::HTTP_OK);

        // $this->db->select("*");
        // $this->db->from("sample");
        // $query = $this->db->get();
        // // print_r($query->result());
        // foreach($query->result() as $sample) {
        //     $sample->card = json_decode($sample->card);
        // }
        // $this->response($query->result(), REST_CONTROLLER::HTTP_OK);
    }


    public function joinGameRoom_post() {        
        $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->userId) || !isset($data->gameId) || !isset($data->cardValues)) {
                $this->response(array(                
                    "message" => "Field(s) Are Missing",                
                ), REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($data->userId) || empty($data->gameId) || empty($data->cardValues)) {
                $this->response(array(                
                    "message" => "Field(s) Are Empty",                
                ), REST_Controller::HTTP_BAD_REQUEST);
            } elseif ($this->session->userdata('user_'.$data->userId)) {                    
                if (!$this->user_model->user_exist($data->userId)) {
                    $this->response(array(                
                        "message" => "User Does't Exist",                
                    ), REST_Controller::HTTP_BAD_REQUEST);
                } elseif ($this->user_model->is_joined($data->userId, $data->gameId)) {
                    $this->response(array(                
                        "message" => "The User Has Already Joined The Room", 
                    ), REST_Controller::HTTP_BAD_REQUEST);
                } elseif ($this->user_model->enter_game_details($data)) {
                    $this->response(array(                    
                        "message" => "User Joined The Room",  
                    ), REST_Controller::HTTP_CREATED);
                } else {
                    $this->response(array(                    
                        "message" => "Failed To Join User To The Room",
                    ), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }                
            } else {
                $this->response(array(                
                    "message" => "Unauthorized Access. Please Login.", 
                ), REST_Controller::HTTP_UNAUTHORIZED);
            }
    }

    public function listJoinedRoomUsers_post() {
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->gameId)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($data->gameId)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (!$this->user_model->is_room_created($data->gameId)) {
            $this->response(array(                
                "message" => "A Room Has Not Been Created For This game_id", 
            ), REST_Controller::HTTP_BAD_REQUEST);        
        } else {
            $roomUsers = $this->user_model->getJoinedRoomUsers($data->gameId);            
            $this->response(array(      
                "mesage" => "User(s) Joined This Room",
                "data" => $roomUsers, 
            ), REST_Controller::HTTP_OK);
        } 
    }

    public function betAmount_post() {
        $data = json_decode(file_get_contents("php://input"));
  
        if (!isset($data->userId) || !isset($data->gameId) || !isset($data->betAmount) || !isset($data->isBetClosed)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($data->userId) || empty($data->gameId) || empty($data->betAmount)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif ($this->session->userdata('user_'.$data->userId)) {        
            if (!$this->user_model->user_exist($data->userId)) {
                $this->response(array(                
                    "message" => "User Does't Exist",                
                ), REST_Controller::HTTP_BAD_REQUEST);
            } elseif (!$this->user_model->is_room_created($data->gameId)) {
                $this->response(array(                
                    "message" => "A Room Has Not Been Created For This game_id", 
                ), REST_Controller::HTTP_BAD_REQUEST);        
            } else {
                $balanceDetails = $this->user_model->get_balance_details($data->userId, $data->gameId, $data->betAmount, $data->isBetClosed);

                if (count($balanceDetails) > 0) {                
                    if (($balanceDetails[0]->balance === 0.00) || ($data->betAmount > $balanceDetails[0]->balance)) {                
                        $this->response(array(                
                            "message" => "Insufficient Balance",                
                        ), REST_Controller::HTTP_BAD_REQUEST);
                    } elseif ($this->user_model->update_bet_amount($data->userId, $data->gameId, $data->betAmount, $data->isBetClosed)) {
                        $balanceDetails = $this->user_model->get_balance_details($data->userId, $data->gameId, $data->betAmount);
                        $isAllBetClosed = $this->user_model->is_all_bet_closed($data->gameId);
                        $this->response(array(
                            "message" => "Bet Amount Updated",
                            "data" => array(
                                "userId" => $balanceDetails[0]->user_id,
                                "gameId" => $balanceDetails[0]->id,
                                "betAmount" => $balanceDetails[0]->bet_amount,
                                "balance" => ($balanceDetails[0]->balance - $balanceDetails[0]->bet_amount),
                                "isAllBetClosed" => $isAllBetClosed
                            )
                        ), REST_Controller::HTTP_OK);
                    }
                } else {
                    $this->response(array(                
                        "message" => "User Has Not Joined This Room",                
                    ), REST_Controller::HTTP_BAD_REQUEST);            
                }

            }
        } else {
            $this->response(array(                
                "message" => "Unauthorized Access. Please Login.", 
            ), REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function addBalance_post() {        
        // $data = json_decode(file_get_contents("php://input"));
        $userId = $this->input->post("userId");
        $amount = $this->input->post("amount");
  
        if (!isset($userId) || !isset($amount)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($userId) || empty($amount)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif ($this->session->userdata('user_'.$userId)) {                    
            if (!$this->user_model->user_exist($userId)) {
                $this->response(array(                
                    "message" => "User Does't Exist",                
                ), REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $user = $this->user_model->get_user($userId);
                if ($this->user_model->update_balance($userId, $user[0]->balance, $amount)) {
                    $user = $this->user_model->get_user($userId);
                    $this->response(array(      
                        "mesage" => "Balance Updated",
                        "data" => array("userId" => $user[0]->id, "balance" => $user[0]->balance), 
                    ), REST_Controller::HTTP_OK);              
                } else {
                    $this->response(array(                
                        "message" => "Balance Update Failed",                
                    ), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        } else {
            $this->response(array(                
                "message" => "Unauthorized Access. Please Login.", 
            ), REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function gameWinner_post() {
        $data = json_decode(file_get_contents("php://input"));
  
        if (!isset($data->userId) || !isset($data->gameId) || !isset($data->amountOnTable)) {
            $this->response(array(                
                "message" => "Field(s) Are Missing",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($data->userId) || empty($data->gameId) || empty($data->amountOnTable)) {
            $this->response(array(                
                "message" => "Field(s) Are Empty",                
            ), REST_Controller::HTTP_BAD_REQUEST);
        } elseif ($this->session->userdata('user_'.$data->userId)) {                                        
            if (!$this->user_model->user_exist($data->userId)) {
                $this->response(array(                
                    "message" => "User Does't Exist",                
                ), REST_Controller::HTTP_BAD_REQUEST);
            } elseif (!$this->user_model->is_room_created($data->gameId)) {
                $this->response(array(                
                    "message" => "A Room Has Not Been Created For This game_id", 
                ), REST_Controller::HTTP_BAD_REQUEST);        
            } elseif($this->user_model->is_game_over($data->userId, $data->gameId)){
                $this->response(array(                
                    "message" => "Game Over. Duplicate Requests are not allowed.", 
                ), REST_Controller::HTTP_BAD_REQUEST);        
            } else {
                $winnerDetails = $this->user_model->user_joined_room($data->userId, $data->gameId);

                if (count($winnerDetails) > 0) {
                    if ($this->user_model->update_winner($winnerDetails, $data->amountOnTable) && $this->user_model->update_losers($winnerDetails[0]->user_id, $winnerDetails[0]->id)) {
                        $this->response(array(      
                            "mesage" => "Game Winner Updated",                         
                        ), REST_Controller::HTTP_OK);
                    } else {
                        $this->response(array(                
                            "message" => "Game Winner Update Failed",                
                        ), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    }                
                } else {
                    $this->response(array(                
                        "message" => "User Has Not Joined This Room",                
                    ), REST_Controller::HTTP_BAD_REQUEST);                          
                }
            }
        } else {
            $this->response(array(                
                "message" => "Unauthorized Access. Please Login.", 
            ), REST_Controller::HTTP_UNAUTHORIZED);
        }
    }
    
}

   
?>