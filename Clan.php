<?php
/**
 * Created by PhpStorm.
 * User: Sveneld
 * Date: 03.10.14
 * Time: 21:07
 */

class Clan extends Object {

    private $players = array();

    public function addPlayers($players = array()){
        foreach ($players as $accountId => $player){
            $this->players[$accountId] = new Player($accountId, $player);
        }
    }

    public function getPlayers(){
        return $this->players;
    }

    public function removePlayer($accountId){
        unset($this->players[$accountId]);
    }

} 