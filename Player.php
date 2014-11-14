<?php
/**
 * Created by PhpStorm.
 * User: Sveneld
 * Date: 03.10.14
 * Time: 21:07
 */

class Player extends Object {

    private $tanks = array();

    public function addTanks($tanks){
        foreach ($tanks as $tankId => $tank){
            $this->tanks[] = new Tank($tankId, $tank);
        }
    }

    public function getTanks(){
        return $this->tanks;
    }

} 