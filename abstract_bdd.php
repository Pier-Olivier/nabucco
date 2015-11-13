<?php

abstract class AbstractBdd {
    
    protected $_bdd;
 
    public function __construct (){
         include 'sion/nabucco/c0nnexi0n.php';
         $this->_bdd = $baze;
    }
}
?>