<?php
/**
 * Created by PhpStorm.
 * User: Sveneld
 * Date: 03.10.14
 * Time: 21:07
 */

class Object {

    private $id;
    private $data = array();

    public function __construct($id, $data){

        $this->setId($id);
        $this->setData($data);
    }

    public function setId($id){
        $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function setData($data){
        $this->data = $data;
    }

    public function getData(){
        return $this->data;
    }


} 