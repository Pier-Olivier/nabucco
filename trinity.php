<?php

namespace nabucco;

class Trinity extends Tank  implements Iterator {

/* seul les formats 
 * (unsigned) (small)(tiny)int, decimal, date, time, datetime, timestamp, text, varchar sont gérés
 */
protected $_listeAttributs = array();

protected $_listeSizeAttributs = array();
protected $_listeFormatAttributs = array();
protected $_listeEmptynessAttributs = array();

//rempli en cas de table2objet_manager->selectConverted()
protected $_listeConvertedAttributs = array();

public function __construct($table, array $donnees, array $listeChampsAConvertir=array()){
    parent::__construct();
    parent::set_table($table);

    parent::set_listeNomAttributs();
    
    $this->set_listeAttributs();

    $this->set_PropertiesAttributs();
    $this->hydrate($donnees);

    if (!empty($listeChampsAConvertir)){//rempli en cas de table2objet_manager->selectConverted()

        $n=1;
        foreach ($listeChampsAConvertir as $champConverti=>$champAConvertir ){
            //capture les valeurs d'origine
            $this->set_convertedAttribut($champAConvertir, $this->get($champAConvertir));
            //set de la nouvelle valeur de l'attribut
            $this->setAttributNoVerif($champAConvertir, $donnees[$champConverti.'_alias']);
            $n++;
        }
   }
}

protected function set_listeAttributs(){//on déclare les attributs et les protège
    foreach (parent::listeNomAttributs() as $attribut){
        $this->_listeAttributs[$attribut]=NULL;
    }
}

public function listeAttributs(){
    return $this->_listeAttributs;
}

public function listeFormatAttributs(){
    return $this->_listeFormatAttributs;
}

public function listeEmptynessAttributs(){
    return $this->_listeEmptynessAttributs;
}

public function hydrate(array $donnees){
    $n=0;
    foreach (parent::listeNomAttributs() as $attribut){
        $this->set($attribut, $donnees[$n]);
        $n++;
    }
}

public function listeSizeAttributs(){
  return $this->_listeSizeAttributs;
}

///----------------------Iterator
    public function valid(){
        return array_key_exists(key($this->_listeAttributs), $this->_listeAttributs);
    }

    public function next(){
        next($this->_listeAttributs);
    }

    public function rewind(){
        reset($this->_listeAttributs);
        return $this;
    }

    public function key(){
        return key($this->_listeAttributs);
    }

    public function current(){
        if ($this->valid())
            return current($this->_listeAttributs);
        else
            return NULL;
    }

///-------------------SETTER et GETTER GENERIQUE
public function __call($attribut, $liste_valeur) {
       
    if (array_key_exists ($attribut,$this->_listeAttributs)) {
            
        if (count ($liste_valeur) == 0) {
            return $this->getAttribut($attribut);
        }
        else if (count ($liste_valeur) == 1) {
            return $this->getAttribut($attribut, $liste_valeur[0]);
        }
        else {
            throw new ErrorException ('<span class="alerte"> GETTER ('.$attribut.') de '.__CLASS__.'('.$this->_table.') ne comporte que 0 ou 1 attribut</span>');
        }
    }
    else {
        throw new ErrorException ('<span class="alerte">'.__CLASS__.'('.$this->_table.') ne comporte pas l\'attribut : '.$attribut.'</span>');
    }

}
 
public function __get($key) {
    throw new ErrorException ('<span class="alerte">On en peut acceder aux attributs : de '.__CLASS__.'('.$this->_table.') ainsi.</span>');
}
    
public function __set($attribut, $valeur) {

    if (array_key_exists ($attribut,$this->_listeAttributs))
        $this->setAttribut($attribut, $valeur);
        
    else {
        throw new ErrorException ('<span class="alerte">attribut : '.$attribut.' de '.__CLASS__.'('.$this->_table.') est invalide</span>');
    }
}
///----------------------------------------

public function set($attribut, $valeur){

    $formatExiste=TRUE;
   
    if ($this->_listeFormatAttributs[$attribut] == 'text'){
        if($valeur == NULL) $valeur ='';
    }
    else if ($this->_listeFormatAttributs[$attribut]=='varchar') {
        if($valeur == NULL) $valeur ='';
        if (!is_string($valeur) ){
            echo '<span class="alerte">!!!! pb avec format string de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>';
            EXIT;
        }
        else if ( mb_strlen($valeur,'UTF-8') > $this->_listeSizeAttributs[$attribut] ){
            echo '<span class="alerte">!!!! pb avec size ('.$this->_listeSizeAttributs[$attribut].') string de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>';
            EXIT;
        }
    }
    else if ($this->_listeFormatAttributs[$attribut]=='decimal' || $this->_listeFormatAttributs[$attribut]=='int' || $this->_listeFormatAttributs[$attribut]=='tinyint' || $this->_listeFormatAttributs[$attribut]=='smallint'){
        if($valeur == NULL) $valeur = 0;
        if (!is_numeric($valeur)){
            echo '<span class="alerte">!!!! pb avec format numeric de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>';
            EXIT;
        }
        else if ($this->_listeFormatAttributs[$attribut]=='tinyint' && ($valeur>127 || $valeur<-128)){
            echo '<span class="alerte">!!!! pb avec valeur tinyint de '.$attribut.' de '.__CLASS__.'('.$this->_table.') : 127 < -128 !!!!</span>';
            EXIT;
        }
        else if ($this->_listeFormatAttributs[$attribut]=='smallint' && ($valeur>32767 || $valeur<-32768)){
            echo '<span class="alerte">!!!! pb avec valeur smallint de '.$attribut.' de '.__CLASS__.'('.$this->_table.') : 32767 < -32768 !!!!</span>';
            EXIT;
        }
    }
    else if ($this->_listeFormatAttributs[$attribut]=='decimal unsigned' || $this->_listeFormatAttributs[$attribut]=='int unsigned' || $this->_listeFormatAttributs[$attribut]=='tinyint unsigned' || $this->_listeFormatAttributs[$attribut]=='smallint unsigned'){
        if($valeur == NULL) $valeur = 0;
        if (!is_numeric($valeur)){
            echo '<span class="alerte">!!!! pb avec format numeric de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>';
            EXIT;
        }
        else if ($valeur<0){
            echo '<span class="alerte">!!!! pb avec valeur de '.$attribut.' de '.__CLASS__.'('.$this->_table.') > 0 !!!!</span>';
            EXIT;
        }
        else if ($this->_listeFormatAttributs[$attribut]=='tinyint unsigned' && $valeur>255){
            echo '<span class="alerte">!!!! pb avec valeur tinyint unsigned de '.$attribut.' de '.__CLASS__.'('.$this->_table.') < 255 !!!!</span>';
            EXIT;
        }
        else if ($this->_listeFormatAttributs[$attribut]=='smallint unsigned' && $valeur>65535){
            echo '<span class="alerte">!!!! pb avec valeur smallint de '.$attribut.' de '.__CLASS__.'('.$this->_table.') < 65535!!!!</span>';
            EXIT;
        }
    }
    else if ($this->_listeFormatAttributs[$attribut]=='time'){
        if($valeur == NULL) $valeur = '00:00:00';
        if (!$this->verifier_format_time($valeur)){
            echo '<span class="alerte">!!!! pb avec format time de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 00:00:00 !!!!</span>';
            EXIT;
        }
    }

    else if ($this->_listeFormatAttributs[$attribut]=='timestamp'){
        
        if($valeur == NULL) $valeur = '0000-00-00 00:00:00';
        else {
            $valeur = explode(' ',$valeur);
            $valeur_date = $valeur[0];
            $valeur_heure = $valeur[1];

            if(! ($this->verifier_format_date($valeur_date) && $this->verifier_format_time($valeur_heure)) ) {
                echo '<span class="alerte">!!!! pb avec format timestamp de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 0000-00-00 00:00:00 !!!!</span>';
                EXIT;
            }
        }
    }
    else if ($this->_listeFormatAttributs[$attribut]=='date'){
        if($valeur == NULL) $valeur ='0000-00-00';
        
        if (!$this->verifier_format_date($valeur)){
            echo '<span class="alerte">!!!! pb avec format date de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 0000-00-00 !!!!</span>';
            EXIT;
        }
        
    } 
    else if ($this->_listeFormatAttributs[$attribut]=='datetime') {
        if($valeur == NULL) $valeur = '0000-00-00 00:00:00';
        else {
            $valeur_copie = explode(' ',$valeur);
            $valeur_date = $valeur_copie[0];
            $valeur_heure = $valeur_copie[1];

            if(! ($this->verifier_format_date($valeur_date) && $this->verifier_format_time($valeur_heure)) ) {
                echo '<span class="alerte">!!!! pb avec format datetime de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 0000-00-00 00:00:00 !!!!</span>';
                EXIT;
            }
        }
        
    } 
    else if($this->_listeFormatAttributs[$attribut] == 'enum') {
        
    }
    else $formatExiste=FALSE;

    if($formatExiste){
        $this->_listeAttributs[$attribut] = $valeur;
        return TRUE;
    }
    else{
        echo '<span class="alerte">!!!! le format de '.$attribut.' de '.__CLASS__.'('.$this->_table.') non defini dans set_attribut !!!!</span>';
        EXIT;
    }

}

public function id(){//retourne la valeur de l'id de l'objet
 return (int) $this->_listeAttributs[$this->_listeNomAttributs[0]];    
}

public function get($attribut, $format=''){
 switch ($format) {
   case "e": echo $this->_listeAttributs[$attribut]; break;
   case "R": echo $this->_listeAttributs[$attribut].'<br />'; break;
   case "S": echo '<span class="'.$attribut.'">'.$this->_listeAttributs[$attribut].'</span>'; break;
   case "T": echo '<td>'.$this->_listeAttributs[$attribut].'</td>'; break;
   case "size": return $this->_listeSizeAttributs[$attribut]; break;
   default: return $this->_listeAttributs[$attribut];
 }
}


//utilisé quand table2objet_manager->selectConverted()
public function convertedAttribut($attribut=''){
    if ($attribut=='') return $this->_listeConvertedAttributs;
    else return $this->_listeConvertedAttributs[$attribut];
}

public function set_convertedAttribut($attribut,$valeur){
    $this->_listeConvertedAttributs[$attribut]=$valeur;
}

public function setAttributNoVerif($attribut, $valeur){
    $this->_listeAttributs[$attribut] = $valeur;
}
// FIN utilisé quand table2objet_manager->set_jointure()


//capture les valeurs dans SQL et intialise les protected array
protected function set_PropertiesAttributs(){
    //on importe les infos table
    $q = $this->_bdd->prepare('DESCRIBE '.$this->_table);
    $q->execute();
    $listeBrute = $q->fetchAll(\PDO::FETCH_ASSOC);
    $i=0;
    foreach($listeBrute as $listeBrute_propriete) {

        $format = explode('(',$listeBrute_propriete['Type']);
        $this->_listeFormatAttributs[$this->listeNomAttributs()[$i]] = $format[0];

        if($listeBrute_propriete['Type']!='date' && $listeBrute_propriete['Type']!='time' && 
           $listeBrute_propriete['Type']!='text' && $listeBrute_propriete['Type']!='datetime' && 
           $listeBrute_propriete['Type']!='timestamp' ){
                
            $sizeTempo[] = $format[1];  
        }
        else {
            $sizeTempo[] = NULL;
        }
    $i++;
    }
    $n = 0;
    
    foreach ($sizeTempo as $sizeBrute){
        $formatedSize = explode(')', $sizeBrute);

        if(isset($formatedSize[1])) {
            if($formatedSize[1] == ' unsigned'){
                $this->_listeFormatAttributs[$this->listeNomAttributs()[$n]].=' unsigned';
            }
        }
        $this->_listeSizeAttributs[$this->listeNomAttributs()[$n]]=$formatedSize[0];
        $n++;
    }
   
    //On récupère les informations concernant "la nullité" de l'attribut
    $i=0;
    foreach($listeBrute as $listeBrute_propriete){
            
            if($listeBrute_propriete['Null']=='NO') $valeur_booleenne = TRUE;
            else $valeur_booleenne = FALSE;
    
            $this->_listeEmptynessAttributs[$this->listeNomAttributs()[$i]] = $valeur_booleenne;
            $i++;
        }
}


//methodes static
public function verifier_format_date($dateVerif){

 if ($dateVerif=='0000-00-00') return TRUE;
 
 if (strlen($dateVerif)!=10) return FALSE;

 if ($dateVerif[4]!='-') return FALSE;
 if ($dateVerif[7]!='-') return FALSE;

 $mois = $dateVerif[5].$dateVerif[6];
 $jour = $dateVerif[8].$dateVerif[9];
 $annee = $dateVerif[0].$dateVerif[1].$dateVerif[2].$dateVerif[3];
 if (!checkdate($mois, $jour, $annee)) return FALSE;

 return TRUE;
}

public function verifier_format_time($heureVerif){

 if ($heureVerif=='00:00:00' || $heureVerif=='00:00') return TRUE;
 
 if (strlen($heureVerif)!=8) return FALSE;

 if ($heureVerif[2]!=':') return FALSE;
 if ($heureVerif[5]!=':') return FALSE;

 $heure = $heureVerif[0].$heureVerif[1];
 $min = $heureVerif[3].$heureVerif[4];
 $sec = $heureVerif[6].$heureVerif[7];

 if ($heure < 0 || $heure > 23 || !is_numeric($heure)) return FALSE;
 if ($min < 0 || $min > 59 || !is_numeric($min)) return FALSE;
 if ($sec < 0 || $sec > 59 || !is_numeric($sec)) return FALSE;

 return TRUE;
}
}
?>