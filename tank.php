<?php
namespace nabucco;

abstract class  Tank extends \AbstractBdd {

protected $_table;
protected $_listeNomAttributs = array();

public function __construct (){
    parent::__construct();
}

public function set_table($table){
    if (is_string($table)){
        $this->_table = '`'.$table.'`';
    }
    else{
        echo '<span class="alerte">!!!! Tank->set_table() table = string !!!!</span>';
        exit;
    }
}

public function table(){
    return $this->_table;
}

final protected function set_listeNomAttributs(){
    $q = $this->_bdd->prepare('DESCRIBE '.$this->_table);
    $q->execute();
    $this->_listeNomAttributs = $q->fetchAll(\PDO::FETCH_COLUMN,0);
}

public function listeNomAttributs($rang='array'){
 if ($rang=='array') return $this->_listeNomAttributs;
 else {
 $rang = (int) $rang;
  return $this->_listeNomAttributs[$rang];
 }
}


}
?>