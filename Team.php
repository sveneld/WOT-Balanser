<?php
/**
 * Created by PhpStorm.
 * User: Sveneld
 * Date: 03.10.14
 * Time: 23:23
 */


class Team extends Object {

    private $teamPlayers = array();

    public function addTeamPlayer($teamPlayerId, $teamPlayer){
        $this->teamPlayers[] = new TeamPlayer($teamPlayerId, $teamPlayer);
    }

    public function addTeamPlayers($teams){
        foreach ($teams as $teamPlayerId => $teamPlayer){
            $this->teamPlayers[] = new TeamPlayer($teamPlayerId, $teamPlayer);
        }
    }

    public function getTeamPlayers(){
        return $this->teamPlayers;
    }

    public function getTeamBalanceWeight(){
        $weight = 0;
        foreach ($this->teamPlayers as $teamPlayer){
            $weight += $teamPlayer->getData()->tank_balance_weight;
        }
        return $weight;
    }

    public function getTeamBalanceAverageWeight(){
        $weight = 0;
        foreach ($this->teamPlayers as $teamPlayer){
            $weight += $teamPlayer->getData()->tank_balance_weight;
        }
        return $weight/15;
    }

    public function getTeamBalanceAverageWeightSplit(){
        $weight = array();
        $weightSplit = 0;
        $count = 0;
        foreach ($this->teamPlayers as $teamPlayer){
            $count ++;
            $weightSplit += $teamPlayer->getData()->tank_balance_weight;
            if ($count % 3 == 0){
                $weight[] = $weightSplit;
                $weightSplit = 0;
            }
        }
        return $weight;
    }

}