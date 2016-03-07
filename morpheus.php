<?php

namespace nabucco;

class Morpheus extends Tank {

    protected $_listeNomChamps = array();

    //utilisé pour les jointures
    protected $_listeTablesJointes = array();
    protected $_listeTablesOrigines = array();

    protected $_listeChampsOrigine = array();
    protected $_listeChampsJoin = array();

    protected $_listeTablesConversion = array();
    protected $_listeChampsAConvertir = array();
    protected $_listeChampsConverted = array();

    protected $_jointure;
    protected $_alias2jointure;

    public function __construct($table, $start_transaction=FALSE){
        parent::__construct();

        parent::set_table($table);
        parent::set_listeNomAttributs();
        $this->set_listeNomChamps();

        if ($start_transaction) $this->start_transaction ();
    }

    //entour les nomAttribut de ` ` pour les requêtes SQL
    final protected function set_listeNomChamps(){
       foreach(parent::listeNomAttributs() as $nomAttributs){
           $this->_listeNomChamps[]='`'.$nomAttributs.'`';
       }
   }

   public function listeNomChamps($rang='array'){
    if ($rang=='array') return $this->_listeNomChamps;
    else {
        $rang = (int) $rang;
        return $this->_listeNomChamps[$rang];
    }
   }
    
    // les transactions de SQL
    public function start_transaction(){
        $this->_bdd->beginTransaction();
    }

    public function commit(){
        $this->_bdd->commit();
    }
    
    public function rollback(){
        $this->_bdd->rollBack();
    }

    //prépater la jointure avec d'autres tables
    public function add_jointure($champOrigine,$tableJointe,$champJoin='',$rangTable=0){
        // JOIN $tableJointe ON $champOrigine = $tableJointe.$champJoin
        //$rangTable permet de faire une jointure en cascade A->B->C

        $this->_listeChampsOrigine[]=$champOrigine;
        $this->_listeTablesJointes[]=$tableJointe;

        if ($rangTable!=0) $this->_listeTablesOrigines[]='table'.$rangTable;
        else $this->_listeTablesOrigines[]=$this->_table;

        if ($champJoin=='') $this->_listeChampsJoin[]='id_'.$tableJointe;
        else $this->_listeChampsJoin[]=$champJoin;
    }

    protected function set_jointure(){
       $this->_jointure = '';
       $n=0;//CREATION du SQL de jointure 
       $t=1;

       foreach ($this->_listeTablesJointes as $tableJointe){
            $this->_jointure .= ' JOIN '.$tableJointe.' AS table'.$t.' ON '.$this->_listeTablesOrigines[$n].'.'.$this->_listeChampsOrigine[$n].' = table'.$t.'.'.$this->_listeChampsJoin[$n] ;
            $n++;
            $t++;
        }
    }

    protected function set_alias2jointure(){

        if (count($this->_listeChampsConverted)){//si on add_champConverted()
            $this->_alias2jointure = ' , ';
            $n=0;
            foreach ($this->_listeChampsConverted as $champsConverted){
                $this->_alias2jointure .= ' table'.$this->_listeTablesConversion[$n].'.'.$champsConverted.' AS '.$n.'_alias, ';
                $n++;
            }
            $this->_alias2jointure = substr($this->_alias2jointure, 0, -2);
       }
       else{
            $this->_alias2jointure='';
       }
    }

//permet de définir les champs qui vont être convertis dans selectConverted
    public function add_champConverted($champConverted,$champsAConvertir='',$rangTable=NULL) {

        //$champConverted est la valeur qui va être renvoyé par $Objet->get('$champsAConvertir)
        
        $this->_listeChampsConverted[] = $champConverted;
        if ($champsAConvertir!=''){
            $this->_listeChampsAConvertir[]=$champsAConvertir;
        }
        else{
            $r = count($this->_listeChampsOrigine)-1;
            $this->_listeChampsAConvertir[] = $this->_listeChampsOrigine[$r];
        }
        
        if ($rangTable==NULL) $rangTable = count ($this->_listeTablesJointes);
        $this->_listeTablesConversion[]=$rangTable;
    }

//SELECT depuis la table et retourne un OBJET dont on peut converti certains champs grace à add_jointure() et add_champConverted()
    public function select($id, $champs='' ){
        // !!! ne pas mettre de quote (ex :`champ`) cette methode les gère automatiquement
        $jointure='';
        $ligneAlias='';

        $this->set_jointure();
        
	$verification = FALSE;
	$verification2 = FALSE;
	 
	if ($champs==''){
            $champs=$this->_listeNomChamps[0];
            if ( is_int($id) ) $verification = TRUE;
            if ( $id>=0 ) $verification2 = TRUE; 
	}
	else{//si on utilise un autre champs que id alors on ne vérifie pas le format
            $verification = TRUE;
            $verification2 = TRUE;
            $champs='`'.$champs.'`';
	}

	if( $verification ){
            if( $verification2 ){

                $this->set_alias2jointure();
                $q = $this->_bdd->query( "SELECT ".implode(",",$this->_listeNomChamps )." ".$this->_alias2jointure." FROM ".$this->_table.' '.$this->_jointure." WHERE ".$champs." = '".$id."'");
		$donnees = $q->fetch();

                if(!empty($donnees)){
                    $table = str_replace('`','',$this->_table);//tank->set_table($table) met des ` il faut donc les retirer sinon elles sont doublé Trinity->constructeur
                    if (count($this->_listeChampsConverted)) return new Trinity ($table, $donnees,$this->_listeChampsAConvertir);
                    else return new Trinity ($table, $donnees);
                }
                else{
                    echo '!!! '.__CLASS__.'('.$this->_table.')::select : $id inexistant!!!';
                    return NULL;
                }
            }
			//else if ($id==0){return new Trinity (array());}
            else {
                echo '!!! '.__CLASS__.'('.$this->_table.')::select : $id ne peut pas être négatif!!!';

                echo '!!! methodz select : $id ne peut pas être négatif!!!';
                return NULL;
            }
	}
	else{
            echo '!!! '.__CLASS__.'('.$this->_table.')::select : $id est un entier positif!!!';
            return NULL;
        }
    }

//SELECT depuis la table et retourne un array d'OBJETS, on peut changer l'ordre du retour. 
    public function selectObjets($where = '', $order=''){
        $liste = array();
        $jointure='';
        $ligneAlias='';
        
        if ($order=='') $order = $this->_listeNomChamps[0];

        $this->set_jointure();
        $this->set_alias2jointure();


        $q = $this->_bdd->query('SELECT '.implode(",",$this->_listeNomChamps)." ".$this->_alias2jointure." FROM ".$this->_table.$this->_jointure.' '.$where.' ORDER BY '.$order);

        while ($donnees = $q->fetch()){
            $table = str_replace('`','',$this->_table);//tank->set_table($table) met des ` il faut donc les retirer sinon elles sont doublé Trinity->constructeur
            if (count($this->_listeChampsConverted)) $liste[] = new Trinity ($table, $donnees, $this->_listeChampsAConvertir);
            else $liste[] = new Trinity ($table, $donnees);
        }

        return $liste;
    }

    public function insertObjet(Trinity $objet,$autoIncrement=TRUE,$returnMax=TRUE){
    //$whereIdMax permet d'avoir le dernier id en fonction de critère si besoin (exemple facture/avoir)
        $listeAttributs = array();

        if ($autoIncrement){//pour laisser SQL gérer AUTOINCREMENT
            $n=0;
            foreach ($this->_listeNomChamps as $champ){
                if ($n>0){
                    $listeAttributs[]= parent::listeNomAttributs($n);
                    $listeChamps[]=$champ;
                }
                $n++;
            }
        }
        else{
            $listeAttributs = parent::listeNomAttributs();
            $listeChamps=$this->_listeNomChamps;
        }

        //constition de la requette SQL
        $requette='';
        $n=0;
        foreach ($listeChamps as $champ){
            $requette .= $champ.' = :'.$listeAttributs[$n].', ' ;
            $n++;
        }
        $requette = substr($requette, 0, -2);//suppression de dernière virgule

        $q = $this->_bdd->prepare('INSERT INTO '.$this->_table.' SET '.$requette);

        foreach ($listeAttributs as $attribut){
            $q->bindValue(':'.$attribut, $objet->get($attribut));
        }

        $q->execute();

        if ($returnMax){
            if (empty($whereIdMax))
                return $this->_bdd->lastInsertId();
            else
                return $this->maxId($whereIdMax);
        }

    }

    //retourne le plus grand ID (utilisé par ->insertObjet)
    public function maxId($where='', $indice=0){

            $q = $this->_bdd->query('SELECT MAX('.$this->_listeNomChamps[$indice].') AS idMax FROM '.$this->_table.' '.$where);
            $donnees = $q->fetch();
            if ($indice)
                return $donnees['idMax'];
            else
                return (int) $donnees['idMax'];

    }

    //retourne la plus grande valeur de la table (utilisé par ->insertObjet)
    public function selectMax($where='', $indice=0){
        $q = $this->_bdd->query('SELECT MAX('.$this->_listeNomChamps[$indice].') AS retourMax FROM '.$this->_table.' '.$where);
        $donnees = $q->fetch();
        return $donnees['retourMax'];
    }

    //efface une entrée de table en F° d'un id, la variable $champs permet de delete des fkid
    public function deleteParId($id, $champs=0){
        if (is_int ($id) && $id > 0 && is_int ($champs) && $champs >= 0) return $this->_bdd->exec('DELETE FROM '.$this->_table.' WHERE '.$this->_listeNomChamps[$champs].' = '.$id);
        else echo '!!! supprimeParId : $id et $champ sont des entier, $id superieur a 0!!!';
    }

    //efface une entrée de table en F° d'un objet
    public function deleteParObjet($Objet){
        return $this->_bdd->exec('DELETE FROM '.$this->_table.' WHERE '.$this->_listeNomChamps[0].' = '.$Objet->get(parent::listeNomAttributs('0')));
    }

/*met à jour un champ de la table (utilisé par updatePost2Table)
 * $attributRang permet de changer le champ sur le 1er WHERE du UPDATE(utile pour la MAJ de plusieurs entrées avec un fk, relation OneToMany)
 * $indiceChampsAnd='' permet d'ajouter une condition après le WHERE de l'UPDATE
 */
    public function update($id, $attributNom, $valeur,$attributRang=0, $indiceChampsAnd=0, $valeurAnd='', $comparateurWHERE='=', $comparateurAND='='){
    //$comparateurWHERE et $comparateurAND permettent de changer les conditions du WHERE et AND (exemple champ>:champs)
    // !!! ne pas mettre de quote (ex :`champ`) cette methode les gère automatiquement
        
        if (is_int($attributRang)){
            if ($attributRang==0) $attribut2selection = parent::listeNomAttributs('0');
            else if ($attributRang>0) $attribut2selection = parent::listeNomAttributs($attributRang);
            else{
                echo  'update (...$attributRang est un int >=0';
                EXIT;
            } 
        }
        
        else if (is_string($attributRang)) $attribut2selection = $attributRang;
        else {
            echo  'update (...$attributRang est un int >=0 ou un string';
            EXIT;
        }
/* PROBLEME SUR UPDATE DANS RELATIOON ONE TO MANY
 * 
 */
        //permet de vérifier si l'id existe et si valeur est au format
//        $Objet = $this->select( (int) $id, $attribut2selection );

        //permet de vérifier si l'id existe et si valeur est au format
        if ($attributRang==0)
            $Objet = $this->select( (int) $id, $attribut2selection );
        else
            $Objet = $this->select($id, $attribut2selection );

        $Objet->set($attributNom, $valeur);

        $and='';
        if ($indiceChampsAnd!=0) $and=' AND '.$this->_listeNomChamps[$indiceChampsAnd].' '.$comparateurAND.' :attribut_and';
        //j'ajoute _where et _and à la requêtte pour les cas où il y a l'utilisation du même champs dans SET, WHERE ou AND.

        $q = $this->_bdd->prepare('UPDATE '.$this->_table.' SET `'.$attributNom.'` = :'.$attributNom.' WHERE `'.$attribut2selection.'` '.$comparateurWHERE.' :attribut_where'.$and);
        $q->bindValue( ':'.$attributNom, $valeur );
        $q->bindValue( ':attribut_where', $Objet->get($attribut2selection));
        if ($indiceChampsAnd!=0) $q->bindValue( ':attribut_and',$valeurAnd);                 
        $q->execute();
        return TRUE;
    }

/*UPDATE tous et seulement les champs de la table qui ont été modifiés par le POST correspondant
 *$indice,$indiceChampsAnd sont utilisés pour ajouter une condition après le WHERE de l'UPDATE de public function update()
 *l'id de l'objet n'étant pas suffisant pour mettre à jour, souvent quand les inputs sont générés dynamiquement avec en  plus des indices.
 * exemple j'ai un tableau qui liste des objets et je veux pouvoir modifier l'attribut du 3eme objet de cette liste.
 */
    public function updatePost2Table($Objet,$indice='',$attributRang=0){
        foreach ($Objet->listeNomAttributs() as $attribut){                     
            if ($Objet->get($attribut)!= $_POST[$attribut.$indice]){
                if ($indice=='')$this->update($Objet->get($Objet->listeNomAttributs('0')), $attribut, $_POST[$attribut]);
                else $this->update($Objet->get($Objet->listeNomAttributs('0')), $attribut, $_POST[$attribut.$indice], $attributRang);
            }                        
        }
    }

//compter les objets en fonction de critère avec possibilité de joindre une table
	public function countEntree($where='',$join='',$order_by=''){
		$total = 0;
		if ($order_by!='') $order_by = 'ORDER BY '.$order_by;
	
		$q = $this->_bdd->query("SELECT COUNT(*) AS total FROM ".$this->_table." ".$join." ".$where." ".$order_by );
		$donnees = $q->fetch();
		return $donnees['total'];
	}

//convertir automatiquement les POST qui ont été converti par add_champConverted
        public function convertirPOST(Trinity $Objet){
            
            $listeChampsAconvertir = array();

            $n=0;//identifie les POST à convertir
            foreach ($this->_listeChampsOrigine as $attribut){
                foreach ($_POST as $cle =>$post){
                    if ($attribut==$cle) $listeChampsAconvertir[$n]=$attribut;
                }
                $n++;
            }

            foreach ($listeChampsAconvertir as $cle=>$post){//conversion

                if ($_POST[$post]!=$Objet->get($post))// si on change la saisie on convertie
                    $_POST[$post] = $this->convertir($this->_listeChampsJoin[$cle],$this->_listeTablesJointes[$cle],$this->_listeChampsConverted[$cle],$_POST[$post],$this->_bdd);
                else //sinon on utilise la valeur stockée dans l'objet
                    $_POST[$post] = $Objet->convertedAttribut($post);
                
                //protection quand le mustMatch n'a pas le temps d'agir et pas de conversion
                if ($_POST[$post] == NULL) $_POST[$post]=FALSE;
            } 
        }
        
//convertir un id de table A en valeur de champs de table B (methode pratique mais pas optimisé)
	public function convertir ($champs, $table, $idchamps, $valeur, $base){
		$req = $base->query("SELECT $champs FROM $table WHERE $idchamps='$valeur'");
		$rslt = $req->fetch();
		return $rslt[$champs];
	}
}
?>