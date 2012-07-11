<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

require("reponse.class.php");

define("QUESTION_TYPE_CHECKBOX", 1);
define("QUESTION_TYPE_RADIO"   , 2);
define("QUESTION_TYPE_KPRIME"  , 3);

class Question {

	var $q_id;
	//var $s_id;
	var $type;
	var $texte;
	var $reponses;
	#var $sondage;

    function __construct($sondage, $id=-1, $type=QUESTION_TYPE_CHECKBOX, $texte="") {
    	#$this->sondage = $sondage;
    	$this->reponses = array();
    	if($id > 0) {
    		$this->q_id = $id;
    	} else {
    		$this->q_id = 0; 
    	}
    	$this->type = $type;
    	$this->texte = trim($texte);
    }
    
    function getQuestion() {
    	$str  = "  Q: $this->texte\n\n";
    	foreach($this->reponses as $r) {
    		$str .= "  R: ".$r->getReponse()."\n";
    	}
    	return $str;
    }
    
    function getTypetxt() {
    	if($this->type == QUESTION_TYPE_CHECKBOX)
    		return "Réponses multiples";
    	if($this->type == QUESTION_TYPE_RADIO)
    		return "Réponse unique";
    	if($this->type == QUESTION_TYPE_KPRIME)
    		return "K prime";
    		
    	return "aucun";
    }
    
    /*
    function getQuestionForm() {
    	$str  = "<h4>$this->texte</h4>\n";
    	foreach($this->reponses as $r) {
    		$str .= "<input type=\"checkbox\" name=\"r\" value=\"".$r->getReponse()."\">&nbsp;".$r->getReponse()."<br/>\n";
    	}
    	return $str;
    }*/
    
    /**
     * Vérification que le nombre de réponses à la question est correct.
     * Dans le cas d'une question de type RADIO, une seule réponse est possible
     * Dans le cas d'une question de type CHECKBOX, il ne peut y avoir plus
     * de voix que de réponses proprosées. 
     * Dans le cas d'une question de type K PRIME, 1 est Vrai et -1 est Faux
     * De plus, pour chaque réponse possible, il n'y a qu'une voix par votant
     */
    function checkReponses() {
    	$nb_rep = 0;
    	
    	// Vérification du nombre de voix pour chaque réponse 
    	foreach($this->reponses as $r) {
    		// Une seule voix par votant
    		if(abs($r->nb_voix) > 1)
    			return -1;
    			
    		if($r->nb_voix == -1 && $this->type != QUESTION_TYPE_KPRIME)
    			return -1;
    			
    		$nb_rep += abs($r->nb_voix);
    	}

    	// Aucune réponse donnée
    	if($nb_rep <= 0)
    		return -1;
		
    	// Plus d'une réponse à une question RADIO
    	if($nb_rep > 1 && $this->type == QUESTION_TYPE_RADIO)
    		return -1;

    	// Plus de voix que de réponses possibles
    	if($nb_rep > sizeof($this->reponses))
    		return -1;
    		
    	// Pour une question K', il doit y avoir autant de voix que de réponses
    	if($nb_rep != sizeof($this->reponses) 
    		&& $this->type == QUESTION_TYPE_KPRIME)
    		return -1;
    	
    	// Tout est en ordre
    	return 0;
    }
    
    function getReponseFromDB() {
    	if($this->q_id == 0) {
    		return null;
    	} 
    	
    	// Connexion à la base de données et extraction des informations
    	global $bd;
		global $sql_table_reponses;
		$stmt = $bd->prepare("SELECT r.r_id, r.q_id, r.texte, r.nb_voix " .
    			"FROM $sql_table_reponses AS r " .
    			"WHERE r.q_id = ?");
    	
		if($stmt == null) {
			throw new Exception("Erreur dans la base de données, table $sql_table_reponses"); 
		}
    			
    	$stmt->bind_param('i', $this->q_id);

		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($_r_id, $_q_id, $_texte, $_nb_votants);
		while($stmt->fetch()) {
			$this->reponses[] = new Reponse($this, $_r_id, $_texte, $_nb_votants);
		}
		
		$stmt->close();
    }
    
    /**
     * Enregistrement de la question dans la base de données
     * Si la question existe déjà, elle est mise à jour
     */
    function saveQuestionInDB($s_id) {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_questions;
    	
    	// S'agit-il d'une mise à jour ?
    	if($this->q_id > 0) 
    		$maj = true;
    	else
    		$maj = false;
    		
    	$_userid = 1;
    	
    	// Sauvegarde de l'objet question
    	if(!$maj) {
    		if ($this == null || $this->texte == null || strlen($this->texte) == 0) {
    			return -1;
    		}
    		// Nouvelle question
    		$stmt = $bd->prepare("INSERT INTO $sql_table_questions " .
    			"SET texte=?, type=?, s_id=?");
    			
    		if($stmt == null) {
				throw new Exception("Erreur dans la base de données, table $sql_table_questions"); 
			}

			$stmt->bind_param('sii', $this->texte, $this->type, $s_id);
			$stmt->execute();
			$this->q_id = $bd->insert_id;
    	} else {
    		
    		if (strlen($this->texte) == 0) {
    			// Suppression d'une question
    			$stmt = $bd->prepare("DELETE FROM $sql_table_questions " .
    				"WHERE s_id=? AND q_id=?");
    			
    			if($stmt == null) {
					throw new Exception("Erreur dans la base de données, table $sql_table_questions"); 
				}
				$stmt->bind_param('ii', $s_id, $this->q_id);
				$stmt->execute();
				$stmt->close();
    			return 0;
    		}

    		// Mise à jour d'une question
    		$stmt = $bd->prepare("UPDATE $sql_table_questions " .
    			"SET texte=?, type=? WHERE s_id=? AND q_id=?");
    			
    		if($stmt == null) {
				throw new Exception("Erreur dans la base de données, table $sql_table_questions"); 
			}

			$stmt->bind_param('siii', $this->texte, $this->type, $s_id, $this->q_id);

			$stmt->execute();
    	}
    	
    	$stmt->close();
    	
    	// Mise à jour des reponses
    	foreach($this->reponses as $r) {
    		$r->saveReponseInDB($this->q_id);
    	}
    	
    	
    	return 0;
    }
    
}
?>