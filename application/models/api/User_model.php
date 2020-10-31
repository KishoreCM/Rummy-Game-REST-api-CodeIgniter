<?php

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_users() {
        $this->db->select("id, name, email, total_score, balance, no_of_matches_played, win_count, lose_count, updated_at");
        $this->db->from("users");
        $query = $this->db->get();        
        return $query->result();
    }

    public function insert_user($data = array()) {
        return $this->db->insert("users", $data);
    }

    public function is_exist($email) {                
        $query = $this->db->get_where('users', array('email' => $email));
        if (count($query->result()) > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function get_user($id) {
        $this->db->select("id, name, email, total_score, balance, no_of_matches_played, win_count, lose_count, updated_at");
        $query = $this->db->get_where('users', array('id' => $id));
        return $query->result();
    }

    public function enter_game_details($data) {        
        $game_details = array(
            "id" => $data->gameId,
            "user_id" => $data->userId,
            "card_values" => json_encode($data->cardValues),
            "score" => array_sum($data->cardValues)
        );
        return $this->db->insert("game", $game_details);        
    }

    public function user_exist($userId) {
        $query = $this->db->get_where('users', array('id' => $userId));
        if (count($query->result()) > 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function is_joined($userId, $gameId) {
        $query = $this->db->get_where('game', array('user_id' => $userId, 'id' => $gameId));        
        // print_r($this->db->last_query());
        if (count($query->result()) > 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function is_room_created($gameId) {
        $query = $this->db->get_where('game', array('id' => $gameId));        
        // print_r($this->db->last_query());
        if (count($query->result()) > 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function getJoinedRoomUsers($gameId) {   
        $this->db->select('game.user_id, users.name, users.email, game.id, game.card_values, game.score, game.bet_amount');
        $this->db->from('users');
        $this->db->join('game', 'users.id = game.user_id');
        $this->db->where('game.id', $gameId);        

        $query = $this->db->get();        
        // print_r($this->db->last_query());
        // print_r($query->result());

        $roomUsersList = array();
        foreach($query->result() as $user) {
            $user->card_values = json_decode($user->card_values);
            $roomUser = array(
                "userId" => $user->user_id,
                "name" => $user->name,
                "email" => $user->email,
                "gameDetails" => array(
                    "gameId" => $user->id,
                    "cardValues" => $user->card_values,
                    "score" => $user->score,
                    "betAmount" => $user->bet_amount
                )
            );
            array_push($roomUsersList, $roomUser);
        }

        // return $query->result();
        return $roomUsersList;
    }

    public function get_balance_details($userId, $gameId, $betAmount) {
        $this->db->select('game.user_id, game.id, users.balance, game.bet_amount');
        $this->db->from('users');
        $this->db->join('game', 'users.id = game.user_id');
        $this->db->where(array('user_id' => $userId, 'game.id' => $gameId));        

        $query = $this->db->get();
        // print_r($this->db->last_query());
        // print_r($query->result());        
        return $query->result();
    }

    public function update_bet_amount($userId, $gameId, $betAmount, $balance) {
        $updateBetAmount = array("bet_amount" => $betAmount);
        $this->db->where(array('id' => $gameId, 'user_id' => $userId));
        if ($this->db->update("game", $updateBetAmount)) {
            $updateBalance = array("balance" => $balance - $betAmount);
            $this->db->where('id', $userId);
            return $this->db->update("users", $updateBalance);
        }
    }

    public function update_balance($userId, $userBalance, $amount) {
        $this->db->where(array('id' => $userId));
        return $this->db->update("users", array('balance' =>$userBalance + $amount));
    }

    public function user_joined_room($userId, $gameId) {
        $this->db->select('game.user_id, game.id, users.total_score, users.balance, users.no_of_matches_played, win_count, lose_count, game.score, game.winner');
        $this->db->from('users');
        $this->db->join('game', 'users.id = game.user_id');
        $this->db->where(array('user_id' => $userId, 'game.id' => $gameId));        

        $query = $this->db->get();
        // print_r($this->db->last_query());
        // print_r($query->result());        
        return $query->result();
    }

    public function update_winner($winnerDetails, $amountOnTable) {
        $updateGameResult = array('winner' => TRUE);
        $this->db->where(array('id' => $winnerDetails[0]->id, 'user_id' => $winnerDetails[0]->user_id));
        if ($this->db->update("game", $updateGameResult)) {
            $updateUserData = array(
                'total_score' => $winnerDetails[0]->score,
                'balance' => $winnerDetails[0]->balance + $amountOnTable,
                'no_of_matches_played' => $winnerDetails[0]->no_of_matches_played + 1,
                'win_count' => $winnerDetails[0]->win_count + 1
            );
            $this->db->where('id', $winnerDetails[0]->user_id);
            return $this->db->update("users", $updateUserData);
        }
    }

    public function update_losers($userId, $gameId) {
        $this->db->select('game.user_id, game.id, users.total_score, users.balance, users.no_of_matches_played, win_count, lose_count, game.score, game.winner');
        $this->db->from('users');
        $this->db->join('game', 'users.id = game.user_id');
        $this->db->where(array('user_id !=' => $userId, 'game.id' => $gameId));        

        $query = $this->db->get();

        // print_r($this->db->last_query());
        // print_r($query->result());                

        foreach($query->result() as $user) {            
                $updateUserData = array(                                        
                    'no_of_matches_played' => $user->no_of_matches_played + 1,
                    'lose_count' => $user->lose_count + 1
                );
                $this->db->where('id', $user->user_id);
                $this->db->update("users", $updateUserData);        
        }

        return TRUE;
    }
}

?>  