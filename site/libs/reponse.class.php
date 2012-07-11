<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

class Reponse {
	
	var $r_id;
	//var $q_id;
	var $texte;
	var $nb_voix;
	#var $question;

    function __construct($question, $id = -1, $texte="", $nb_votants=0) {
    	#$this->question = $question;
    	if($id > 0) {
    		$this->r_id = $id;
    	} else {
    		$this->r_id = 0; 
    	}
    	$this->texte = trim($texte);
    	$this->nb_voix = $nb_votants;
    }
    
    function getReponse() {
    	$str = "$this->texte";//, votants: $this->nb_voix";
    	return $str;
    }
    
    /**
     * Enregistrement de la réponse dans la base de données
     * Si la réponse existe déjà, elle est mise à jour
     */
    function saveReponseInDB($q_id) {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_reponses;
    	
    	// S'agit-il d'une mise à jour ?
    	if($this->r_id > 0) 
    		$maj = true;
    	else
    		$maj = false;
    		
    	$_userid = 1;
    	
    	// Sauvegarde de l'objet réponse
    	if(!$maj) {
    		if ($this == null || $this->texte == null || strlen($this->texte) == 0) {
    			return -1;
    		}
    		// Nouvelle question
    		$stmt = $bd->prepare("INSERT INTO $sql_table_reponses " .
    			"SET texte=?, q_id=?");
    			
    		if($stmt == null) {
				throw new Exception("Erreur dans la base de données, table $sql_table_reponses"); 
			}

			$stmt->bind_param('si', $this->texte, $q_id);
			$stmt->execute();
			$this->r_id = $bd->insert_id;
    	} else {
    		if (strlen($this->texte) == 0) {
    			// Suppression d'une réponse
    			$stmt = $bd->prepare("DELETE FROM $sql_table_reponses " .
    				"WHERE q_id=? AND r_id=?");
    			
    			if($stmt == null) {
					throw new Exception("Erreur dans la base de données, table $sql_table_reponses"); 
				}
				$stmt->bind_param('ii', $q_id, $this->r_id);
				$stmt->execute();
				$stmt->close();
    			return 0;
    		}
    		
    		// Mise à jour d'une réponse
    		$stmt = $bd->prepare("UPDATE $sql_table_reponses " .
    			"SET texte=? WHERE q_id=? AND r_id=?");
    			
    		if($stmt == null) {
				throw new Exception("Erreur dans la base de données, table $sql_table_reponses"); 
			}

			$stmt->bind_param('sii', $this->texte, $q_id, $this->r_id);

			$stmt->execute();
    	}
    	
    	$stmt->close();
    	
    	return 0;
    }
    
}
?>