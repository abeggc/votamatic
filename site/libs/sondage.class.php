<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

/*
 * Classe représentant un sondage
 */
 
 require("question.class.php");
 
 define("SONDAGE_ETAT_CREATION", 1);
 define("SONDAGE_ETAT_PRET"    , 2);
 define("SONDAGE_ETAT_ENCOURS" , 3);
 define("SONDAGE_ETAT_FINI"    , 4);
 
 define("SONDAGE_STATUS_OK"      , 0);
 define("SONDAGE_STATUS_ERREUR"  , 1);
 define("SONDAGE_STATUS_WARNING" , 2);
 define("SONDAGE_STATUS_INFO"    , 3);
 
 define("SONDAGE_VISIBILITE_PUBLIC", 0);
 define("SONDAGE_VISIBILITE_PRIVEE", 1);
 
 define("SONDAGE_AUTH_SECRET", "vOtAmAtIc");

class Sondage {

	var $s_id;             // Identifiant 
	var $u_id;             // Id du propriétaire
	var $texte;            // Descriptif
	var $etat;             // Etat
	var $visibilite;       // Visibilité
	var $status;		   // Code de status
	var $message;		   // Message d'info/erreur
	var $details_msg;	   // Détails supplémentaires  
	var $duree;            // Durée de l'évaluation
	var $date_creation;    // Date de création
	var $date_evaluation;  // Date de l'évaluation
	var $nb_participants;  // Nb. de participants
	var $auth;             // Chaine de validation
	var $questions;        // Liste des questions

	/**
	 * Constructeur
	 */
    function __construct($id=-1, $json=null) {
    	$this->questions = array();
    	$this->status = SONDAGE_STATUS_OK;
    	$this->message = "";
    	$this->details_msg = "";
    	
    	if($id > 0) {
    		// Sondage déjà existant
    		$this->s_id = $id;
    	} else {
    		// Nouveau sondage, non initialisé
    		$this->s_id = 0;
    		$this->visibilite = SONDAGE_VISIBILITE_PUBLIC;
    		$this->etat = SONDAGE_ETAT_PRET;
    		$this->duree = 0;
    		if($id == 0) {
	    		for($i=0; $i<3; $i++) {
	    			$q = new Question(null);
	    			for($j=0; $j<4; $j++) {
	    				$q->reponses[] = new Reponse(null);
	    			}
	    			$this->questions[] = $q;
	    		}
    		}
    	}
    	
    	if($json != null) {
    		$this->initFromJSON($json);
    	}
    }
    
    /**
     *  Création d'un objet sondage à partir du json reçu
     */
    function initFromJSON($json) {
    	//print $json."<br>";
    	$json = str_replace("__and__", "&", $json);
    	$json = str_replace("__plus__", "+", $json);
    	//print $json."<br>";
		$obj = json_decode($json);
		
		// Copie des valeurs des champs
		$this->s_id = $obj->s_id;
		$this->u_id = $obj->u_id;
		$this->etat = $obj->etat;
		$this->visibilite = $obj->visibilite;
		$this->duree = $obj->duree;
		$this->texte = trim($obj->texte);
		$this->auth = $obj->auth;
		//print "sizeof:".sizeof($obj->questions);
		
		// Copie des questions
		for($i = 0; $i < sizeof($obj->questions); $i++) {
			$q = $obj->questions[$i];
			if($q == null)
				next;
			$new_q = new Question(null, $q->q_id, $q->type, $q->texte);
			
			// Copie des réponses
			for($j = 0; $j < sizeof($obj->questions[$i]->reponses); $j++) {
				$r = $obj->questions[$i]->reponses[$j];
				if($r == null)
					continue;
				$new_q->reponses[] = new Reponse(null, $r->r_id, $r->texte, 
													$r->nb_voix);
			}
			$this->questions[] = $new_q;
		}
    }
    	
    /**
     * Lecture du sondage depuis la base de données
     * à partir de son identifiant
     */
    function getSondageFromDB($all = 0) {
    	if($this->s_id == 0) {
    		return null;
    	} 
    	
    	$this->questions = null;
    	
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	
    	$stmt = $bd->prepare("SELECT s.s_id, s.u_id, s.texte, s.etat, " .
    			"s.visibilite, s.duree, s.date_creation, s.date_evaluation, " .
    			"s.nb_participants " .
    			"FROM $sql_table_sondages AS s " .
    			"WHERE s.s_id = ?");
    	
    	if($stmt == null) {
			throw new Exception("Erreur dans la base de données, " .
					"table $sql_table_sondages"); 
		}

		$stmt->bind_param('i', $this->s_id);

		$stmt->execute();
		$stmt->store_result();
		$s_id = -1;
		$stmt->bind_result($s_id, $this->u_id, $this->texte, $this->etat, 
							$this->visibilite, $this->duree, 
							$this->date_creation, $this->date_evaluation, 
							$this->nb_participants);
		$stmt->fetch();
		//print $stmt->num_rows." ".$this->texte." ".$this->s_id;
		if($stmt->num_rows == 1) {			
			if($s_id > $this->s_id) {
				$ok = true;
			}
			$stmt->free_result();
		} else {
			$stmt->close();
			throw new Exception("Ce sondage n'existe pas"); 
		}
		$stmt->close();
		
		// Vérification du propriétaire
		if((!isset($_SESSION["userid"]) && 
			$this->visibilite == SONDAGE_VISIBILITE_PRIVEE &&
			$this->etat != SONDAGE_ETAT_ENCOURS) 
			||
			(isset($_SESSION["userid"]) && 
			$this->u_id != $_SESSION["userid"] && 
			$this->visibilite == SONDAGE_VISIBILITE_PRIVEE &&
			$this->etat != SONDAGE_ETAT_ENCOURS)) 
		{
			// Pas le proprio et le sondage n'est pas public -> erreur
			$this->clearSondageData();
			throw new Exception("Les résultats du sondage ne sont pas publics");
		}
		
		if($this->etat == SONDAGE_ETAT_CREATION || $this->etat == SONDAGE_ETAT_PRET) {
			if(!isset($_SESSION["userid"])) {
				$this->clearSondageData();
				throw new Exception("Sondage invalide");
			}
			
			if(isset($_SESSION["userid"]) && $this->u_id != $_SESSION["userid"]) {
				$this->clearSondageData();
				throw new Exception("Sondage invalide");
			}
		}
		
		// Vérification si déjà voté
		if($this->etat == SONDAGE_ETAT_ENCOURS) {//} && $this->u_id != $_SESSION["userid"]) {
			if($this->dejaVote()) {
				$pub = $this->visibilite;
				//$this->clearSondageData();
				$this->questions = null;
				$this->status = SONDAGE_STATUS_WARNING;
				$this->message = "Votre vote a déjà été comptabilisé pour le sondage en cours";
				if($pub == SONDAGE_VISIBILITE_PUBLIC) {
	    			$this->details_msg = "Dès le sondage clos, les résultats seront automatiquement affichés.";
	    		}
	    		return;
			}
		}
		
		// Si l'on veut que le minimum, on s'arrête ici
		if($all == 0) {
			return;
		}
		
		// Obtention des questions, puis des réponses
		global $sql_table_questions;
		$stmt = $bd->prepare("SELECT q.q_id, q.type, q.texte " .
    			"FROM $sql_table_questions AS q " .
    			"WHERE q.s_id = ?");
    	
		if($stmt == null) {
			throw new Exception("Erreur dans la base de données, " .
					"table $sql_table_questions"); 
		}
    			
    	$stmt->bind_param('i', $this->s_id);

		$stmt->execute();
		$stmt->store_result();
		$_q_id = -1;
		$_type = -1;
		$_texte = "";
		$stmt->bind_result($_q_id, $_type, $_texte);
		while($stmt->fetch()) {
			$this->questions[] = new Question($this, $_q_id, $_type, $_texte);
		}

		$stmt->close();
		
		// Obtention des réponses possibles pour chaque question
		for($i = 0; $i < sizeof($this->questions); $i++) {
			$this->questions[$i]->getReponseFromDB();
		}
		
		// Calcul de la chaîne d'authentification
		$this->auth = $this->getSondageAuth();
    }
    
    function dejaVote() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	global $sql_prefix;
    	
    		    	
    	$table_participants = $sql_prefix."sondage_".$this->s_id."_users";
    	$user = VOTAMATIC_USERID;
    	
    	$sql = "SELECT unique_user FROM $table_participants " .
    			"WHERE unique_user = '$user' LIMIT 1;";
    		
    	try {
    		$r = $bd->query($sql);
	    	if($r->num_rows > 0) {
	    		return true;	
			} else {
				return false;
				throw new Exception("Ce sondage n'existe pas"); 
			}
	   	} catch (Exception $e) {
	   		return false;
	   	}	

    }
    
    function getSondage() {
    	$str  = "Sujet: $this->texte\n";
    	$str .= "Nb de participants: $this->nb_participants\n";
    	$str .= "\nListe des questions:\n\n";
    	foreach($this->questions as $q) {
    		$str .= $q->getQuestion()."\n\n";
    	}
    	return $str;
    }
    
    /**
     * Retourne le sondage au format CSV
     */
    function getSondageCSV() {
    	$str  = "\"Titre\",\"" . str_replace("\"", "\"\"",$this->texte) . "\"\n";
    	$str .= "\"Nb de participants\"," . $this->nb_participants . "\n";
    	$str .= "\"Date d'évaluation\",\"" . $this->date_evaluation . "\"\n\n";
    	
    	$q_cpt = 1;
    	foreach($this->questions as $q) {
    		$str .= "\"Question $q_cpt\",\"".str_replace("\"", "\"\"",$q->texte)."\"";
    		if($q->type == QUESTION_TYPE_KPRIME)
    			$str .= ",\"Nb de vrai\",\"Nb de faux\"\n";
    		else
    			$str .= ",\"Nb de voix\"\n";	

    		$str .= "\"Type\",\"".$q->getTypetxt()."\"\n";
    		$r_cpt = 1;
    		foreach($q->reponses as $r) {
    			$str .= "\"Réponse $r_cpt\",\"".str_replace("\"", "\"\"",$r->texte)."\"";
    			if($q->type == QUESTION_TYPE_KPRIME)
    				$str .= "," .$r->nb_voix . ",". ($this->nb_participants-$r->nb_voix)."\n";
    			else
    				$str .= "," .$r->nb_voix . "\n";
    			$r_cpt++;
    		}
    		$q_cpt++;
    		$str .= "\n";
    	}
    	
    	return $str;
    }
    
    /**
     * Enregistrement d'un vote dans la base de données
     */
    function saveVote() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	global $sql_table_questions;    	
    	global $sql_table_reponses;
    	global $sql_prefix;
    	
    	// Est-ce que le sondage existe et est-il ouvert ? Si non -> return
    	try {
    		$status = $this->checkStatus();
	    	if($status != SONDAGE_ETAT_ENCOURS) {
	    		$this->clearSondageData();
	    		$this->status = SONDAGE_STATUS_ERREUR;
	    		$this->message = "Sondage non valable.";
	    		return 1;
	    	}
	    	
	    	// Est-ce que la personne a déjà voté pour ce sondage ? 
	    	// Si oui -> return
	    	
	    	// Est-ce que les questions et réponses sont bien celle du sondage ?
	    	// Si non -> return
	    	$status = $this->checkSondage();
	    	if($status > 0) {
	    		$this->clearSondageData();
	    		$this->status = SONDAGE_STATUS_ERREUR;
	    		$this->message = "Données sondage invalides";
	    		$this->details_msg = "Votre vote n'a pas pu être enregistré. Veuillez recharger la page.";
	    		return 1;
	    	}
	    	
	    	$this->auth = null;
	    	
	    	// Maintenant on est sûr que la personne n'a pas encore voté, 
	    	// que le sondage est ouvert et que les données n'ont pas été
	    	// modifiées intentionnelement par le votant. 
	    	// On peut donc enregistrer le vote à l'aide d'une transaction.
	    	
	    	// Transaction
	    	$bd->query("START TRANSACTION;");
	    	
	    	$table_participants = $sql_prefix."sondage_".$this->s_id."_users";
	    	$user = VOTAMATIC_USERID;
	    	
	   		// Ajout du votant dans la table temporaire afin qu'il ne puisse 
	   		// revoter
	    	$sql = "INSERT INTO $table_participants (unique_user) " .
	    			"VALUES ('$user');";
	    	$bd->query($sql);
	    	
	    	if ($bd->errno > 0) {
	    		//print "erreur enregistrement participant";
	    		$this->status = SONDAGE_STATUS_ERREUR;
				$this->message = "Votre vote a déjà été comptabilisé pour le sondage en cours";
				$this->questions = null;
				if($this->visibilite == SONDAGE_VISIBILITE_PUBLIC) {
	    			$this->details_msg = "Dès le sondage clos, les résultats seront automatiquement affichés.";
	    		}
	    		$bd->query("ROLLBACK;");
	    		return 1;
	    	}
	    	
	    	// Incrémentation des voix pour chaque réponse
	    	$table_votes = $sql_prefix."sondage_".$this->s_id."_data";
	    	foreach($this->questions as $q) {
	    		foreach($q->reponses as $r) {
	    			if ($r->nb_voix == 1) {
	    				$sql = "INSERT INTO $table_votes (r_id) " .
	    						"VALUES (".$r->r_id.");";
	    				$bd->query($sql);
	    				if ($bd->errno > 0) {
	    					$this->etat = SONDAGE_STATUS_ERREUR;
					    	$this->message = "Erreur enregistrement voix";
					    	$this->questions = null;
	    					$bd->query("ROLLBACK;");
	    					return 1;
	    				}
	    			}
	    		}
	    	}
	    	
	    	// Fin  
	    	$bd->query("COMMIT;");
	    	
	    	if ($bd->errno > 0) {
				$this->status = SONDAGE_STATUS_ERREUR;
				$this->message = "Erreur enregistrement voix";
				$this->questions = null;
				$bd->query("ROLLBACK;");
				return 1;
	    	}
	    	
	    	$this->status = SONDAGE_STATUS_INFO;
	    	$this->message = "Votre vote a été enregistré avec succès";
	    	if($this->visibilite == SONDAGE_VISIBILITE_PUBLIC) {
	    		$this->details_msg = "Dès le sondage clos, les résultats seront automatiquement affichés.";
	    	}
	    	$this->questions = null;
	    	return 0;
 	
    	} catch (Exception $e) {
    		$this->status = SONDAGE_STATUS_ERREUR;
 			$this->message = 'Erreur : '.  $e->getMessage();
 			return 1;
 		}
    	
    }
    
    /**
     * Vérifie le status d'un sondage
     * Si le sondage est ouvert et que le temps est écoulé, le sondage est fermé
     */
    function checkStatus() {
    	global $bd;
    	global $sql_table_sondages; 	
    	
    	$stmt = $bd->prepare("SELECT s.s_id, s.u_id, s.texte, s.etat, " .
    		"s.visibilite, s.duree, s.date_creation, s.date_evaluation, " .
    		"s.nb_participants, " .
    		"UNIX_TIMESTAMP()-UNIX_TIMESTAMP(date_evaluation) AS temps " .
    		"FROM $sql_table_sondages AS s " .
    		"WHERE s.s_id = ?");
    		
    	if($stmt == null) {
			throw new Exception("Erreur dans la base de données, " .
					"table $sql_table_sondages"); 
		}

		$stmt->bind_param('i', $this->s_id);

		$stmt->execute();
		$stmt->store_result();
		$s_id = -1;
		$stmt->bind_result($_s_id, $_u_id, $_texte, $_etat, 
							$_visibilite, $_duree, $_date_creation,
							$_date_evaluation, $_nb_participants, $_temps);
		$stmt->fetch();
		
		if($_s_id != $this->s_id) {
			throw new Exception("Sondage invalide");
		}
		if($_etat != SONDAGE_ETAT_ENCOURS) {
			throw new Exception("Ce sondage n'est pas ouvert actuellement");
		}
		if($_temps > $_duree && $_duree > 0) {
			// Fermeture du sondage si le temps défini est écoulé
			$this->fermetureSondage();
			$this->questions = null;
			throw new Exception("Temps évaluation sondage écoulé");
		}
		
		$this->u_id = $_u_id;
		$this->etat = $_etat;
		$this->duree = $_duree;
		
		return $_etat;	
		
    }
    
    /**
     * Supprime toutes les infos non pertinentes.
     * Utiliser dans le cas où il faut retourner un code d'erreur au client
     */
    function clearSondageData() {
    	//$this->s_id;
		//$this->u_id = -1;
		$this->texte = "";
		$this->auth = "";
		$this->etat = -1;
		$this->visibilite = -1;
		$this->status = 0;
		$this->message = "";
		$this->duree = -1;
		$this->date_creation = -1;
		$this->date_evaluation = -1;
		$this->nb_participants = -1;
		$this->auth = -1;
		$this->questions = null;
    }
    
    /**
     * Vérifie l'exactitude des informations retournées par le client.
     * Notamment que les id des réponses n'ont pas été modifiés 
     * intentionnelement et qu'il y a au moins 1 choix par question.
     */
    function checkSondage() {
    	global $bd;
    	global $sql_table_sondages;

    	// Vérification de la chaîne d'authentification
    	if(strcmp($this->auth, $this->getSondageAuth()) != 0) 
    		return 1;
    		
    	// Vérification du nombre de réponses à chaque question
    	foreach($this->questions as $q) {
    		if($q->checkReponses() != 0)
    			return 1;
    	}
    	
		return 0;	
    }
    
    /**
     * Retourne la chaîne d'authentification du sondage
     * (utilisée pour vérifier contre la modification volontaire
     * des données par les votants)
     */
    function getSondageAuth() {
    	$str  = "s" . $this->s_id;
    	$str .= "u" . $this->u_id;
    	foreach($this->questions as $q) {
    		$str .= ",q" . $q->q_id . ":" . $q->type;
	    	foreach($q->reponses as $r) {
	    		$str .= "r" . $r->r_id;
	    	}
    	}
    	
    	// Calcul de l'authentification 
    	// (hash md5 avec un secret connu uniquement du serveur)
    	return md5($str . SONDAGE_AUTH_SECRET);
    }
    
    /**
     * Remise à zéro d'un sondage
     * Enlève toutes les voix ainsi que le nb de participants.
     */
    function RAZ() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	global $sql_table_reponses;
    	
    	// Récupération de toutes les informations
    	$this->getSondageFromDB(1);
    	
    	// Mise à zéro du nombre de participants
    	$sql = "UPDATE $sql_table_sondages SET nb_participants=0, " .
    			"etat=".SONDAGE_ETAT_PRET. ", " .
    			"date_evaluation='0000-00-00 00:00:00' " .
    			"WHERE s_id=".$this->s_id.";";
    	$bd->query($sql);

		// Mise à zéro de toutes les réponses de toutes les questions
    	foreach($this->questions as $_q) {
			$sql = "UPDATE $sql_table_reponses SET nb_voix=0 " .
					"WHERE q_id=".$_q->q_id.";";
			$bd->query($sql);
    	}
    }
    
    /**
     * Suppression du sondage de la base de données
     */
    function suppression() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	global $sql_table_reponses;
    	
    	// Récupération de toutes les informations
    	$this->getSondageFromDB(1);
    	
    	// Vérification qu'il s'agit bien du propriétaire du sondage
    	if($this->u_id != $_SESSION["userid"])
    		return -1;
    		
    	// Requête de suppression
    	// MySQL gère la suppression des questions et réponses en cascade 
    	// grâce aux clefs étrangères
    	$sql = "DELETE FROM $sql_table_sondages " .
    			"WHERE s_id=".$this->s_id." AND u_id=".$_SESSION["userid"].";";
    	$bd->query($sql);
    }    
    
    
    /**
     * Ouverture d'un sondage
     * Création des tables temporaires
     */
    function ouvertureSondage() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	global $sql_prefix;
    	
    	// Vérification si l'utilisateur a déjà un sondage ouvert
  		$sql = "SELECT s_id FROM $sql_table_sondages " .
  				"WHERE u_id = " . $_SESSION['userid'] . " " .
  				"AND etat = " . SONDAGE_ETAT_ENCOURS . ";";
  				
    	$r = $bd->query($sql);
	    if($r->num_rows > 0) {
	    	throw new Exception("Vous ne pouvez pas ouvrir plus d'un sondage à la fois"); 
		}
		
		// Vérification que le sondage est bien dans l'état prêt
		$sql = "SELECT etat FROM $sql_table_sondages " .
			   "WHERE u_id = " . $_SESSION['userid'] . " " .
			   "AND s_id = " . $this->s_id . ";";
		$r = $bd->query($sql);
		if($r){
			while ($row = $r->fetch_object()) {
				$etat = $row->etat;
    		}
    		$r->close();
		}

		if(!($etat == SONDAGE_ETAT_PRET || $etat == SONDAGE_ETAT_CREATION)) {
			//throw new Exception("Ce sondage n'est pas en mesure d'être évalué actuellement");
			return 1;
		}
		
		
  		// Mise à zéro sondage
  		$this->RAZ();
    	
    	// Création de la table pour les votants
    	$table_participants = $sql_prefix."sondage_".$this->s_id."_users";
    	$sql = "CREATE TABLE IF NOT EXISTS $table_participants ( " .
  				"unique_user varchar(255) NOT NULL, " .
  				"UNIQUE KEY unique_user (unique_user) " .
				") DEFAULT CHARSET=utf8;";
		$bd->query($sql);
    	// Création de la table pour les voix
    	$table_votes = $sql_prefix."sondage_".$this->s_id."_data";
    	$sql = "CREATE TABLE IF NOT EXISTS `$table_votes` ( " .
  				"`r_id` int(10) unsigned NOT NULL, " . 
  				"KEY `r_id` (`r_id`) ) DEFAULT CHARSET=utf8;";
  		$bd->query($sql);
  		
    	// Changement de l'état du sondage & date d'évaluation
    	$sql = "UPDATE $sql_table_sondages SET etat=".SONDAGE_ETAT_ENCOURS. 
				", date_evaluation=NOW() " .
				"WHERE s_id=".$this->s_id.";";
    	$bd->query($sql);
    	
    	$this->etat = SONDAGE_ETAT_ENCOURS;
    	
    	return 0;
    }
    
    /**
     * Fermeture du sondage
     * Comptabilisation des votes
     */
    function fermetureSondage() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	global $sql_table_questions;    	
    	global $sql_table_reponses;
    	global $sql_prefix;
    	
    	$stmt = $bd->prepare("SELECT s.s_id, s.u_id, s.etat " .
    		"FROM $sql_table_sondages AS s " .
    		"WHERE s.s_id = ?");
    		
    	if($stmt == null) {
			throw new Exception("Erreur dans la base de données, " .
					"table $sql_table_sondages"); 
		}

		$stmt->bind_param('i', $this->s_id);

		$stmt->execute();
		$stmt->store_result();
		$s_id = -1;
		$stmt->bind_result($_s_id, $_u_id, $_etat);
		$stmt->fetch();
		
		if($_s_id != $this->s_id) {
			throw new Exception("Sondage invalide");
		}
		if($_etat == SONDAGE_ETAT_FINI) {
			return 0;
		}
    	
    	$bd->query("START TRANSACTION;");
    	
    	// Désactivation du sondage
    	$sql = "UPDATE $sql_table_sondages " .
    			"SET etat=".SONDAGE_ETAT_FINI . " " .
				"WHERE s_id = ".$this->s_id. " " .
				"AND etat=".SONDAGE_ETAT_ENCOURS.";";
    	$bd->query($sql);
    	
    	// Aggrégation des résultats    
    	$table_votes = $sql_prefix."sondage_".$this->s_id."_data";	
    	$sql = "SELECT r_id, COUNT(r_id) AS voix FROM $table_votes " .
    			"GROUP BY r_id;";
    	$reps = array();
    	$result = $bd->query($sql);
    	if($bd->errno) {
    		throw new Exception("Impossible de fermer le sondage " .
    				"(erreur ".$bd->errno.")");
    	}
    	while ($row = $result->fetch_object()) {
    		$reps[] = array($row->r_id, $row->voix);
    	}
    	//var_dump($reps);
    	foreach($reps as $rep) {
    		$sql = "UPDATE $sql_table_reponses SET nb_voix=".$rep[1] . " " .
    				"WHERE r_id=".$rep[0].";";
    		$bd->query($sql);
    		//print $sql."<br>\n";
    	}
    	
    	// Enregistrement nombre de participants
    	$table_participants = $sql_prefix."sondage_".$this->s_id."_users";
    	$sql = "SELECT COUNT(*) AS nb_votants FROM $table_participants";
    	$results = $bd->query($sql);
    	if($row = $results->fetch_object()) {
    		$votants = $row->nb_votants;
    	} 

    	$sql = "UPDATE $sql_table_sondages SET nb_participants=$votants, " .
    			"date_evaluation=NOW() " .
				"WHERE s_id=".$this->s_id.";";
    	/*$sql = "UPDATE $sql_table_sondages SET nb_participants=$votants, " .
    			"duree=UNIX_TIMESTAMP()-UNIX_TIMESTAMP(date_evaluation) " .
    			"WHERE s_id=".$this->s_id.";";*/
    	$bd->query($sql);
    	
    	$bd->query("COMMIT;");
    	
    	// Suppression tables temporaires
    	$sql = "DROP TABLE $table_votes;";
		$bd->query($sql);
    	$sql = "DROP TABLE $table_participants;";
		$bd->query($sql);
		
    	return 0;
    }
    
    /**
     * Enregistrement d'un sondage dans la base de données
     * Si le sondage existe déjà, il est mis à jour
     */
    function saveSondageInDB() {
    	// Connexion à la base de données et extraction des informations
    	global $bd;
    	global $sql_table_sondages;
    	
    	// S'agit-il d'une mise à jour ?
    	if($this->s_id > 0) 
    		$maj = true;
    	else
    		$maj = false;
    		
    	$_userid = $_SESSION["userid"];
    	
    	// Sauvegarde de l'objet sondage
    	if(!$maj) {
    		// Nouveau sondage
    		$stmt = $bd->prepare("INSERT INTO $sql_table_sondages " .
    			"SET texte=?, etat=?, visibilite=?, duree=?, " .
    			"u_id=?, s_id=NULL, date_creation=CURRENT_TIMESTAMP, " .
    			"date_evaluation='0000-00-00 00:00:00'");
    			
    		if($stmt == null) {
				throw new Exception("Erreur dans la base de données, " .
						"table $sql_table_sondages"); 
			}

			$stmt->bind_param('sisii', $this->texte, $this->etat, 
								$this->visibilite, $this->duree, $_userid);

			$stmt->execute();
			if($bd->errno > 0) {
				throw new Exception("Erreur ".$bd->errno.", ".$bd->error);
			}
			$this->s_id = $bd->insert_id;
    	} else {
    		// Mise à jour d'un sondage
    		$stmt = $bd->prepare("UPDATE $sql_table_sondages " .
    			"SET texte=?, etat=?, visibilite=?, duree=? " .
    			"WHERE s_id=? AND u_id=?");
    			
    		if($stmt == null) {
				throw new Exception("Erreur dans la base de données, " .
						"table $sql_table_sondages"); 
			}

			$stmt->bind_param('sisiii', $this->texte, $this->etat, 
								$this->visibilite, $this->duree, $this->s_id, 
								$_userid);

			$stmt->execute();
    	}
    	
    	$stmt->close();
    	
    	// Mise à jour des questions
    	foreach($this->questions as $q) {
    		$q->saveQuestionInDB($this->s_id);
    	}
    	
		$this->status = SONDAGE_STATUS_INFO;    	
    	$this->message = "Sondage enregistré avec succès";

    	return 0;
    }

}
?>