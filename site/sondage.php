<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

 require("libs/mysql.php");
 require("libs/sondage.class.php");
 require("session.php");
 $bd = connexionBD();
 
 checkSession();
 
 /*
  * vérification de l'action
  *  - ouvrir
  *  - fermer
  *  - raz
  *  - del
  *  - voter (POST)
  *  - edition (POST)
  * 
  * sinon
  *  - retourner le sondage avec message adéquat
  */
 
 if(isset($_POST['action'])) {
 	//Action reçue par POST -> edition ou voter
 	$action = $_POST['action'];
 	$sid = -1;
 	if(isset($_POST['sid']) && is_numeric($_POST['sid']) && $_POST['sid'] >= 0) {
 		$sid = $_POST['sid'];
 	}
 	try {
 		if(get_magic_quotes_gpc()) {
 			$sondage = stripslashes($_POST['sondage']);
 		} else {
 			$sondage = $_POST['sondage'];
 		}
 		
	 	if($sid == -1) {
	 		//Id non défini -> erreur
	 		$s = new Sondage();
	 		throw new Exception("Identifiant du sondage non défini");
	 	}
	 	$s = new Sondage($sid, $sondage);
	 	
 		if($sid > 0 && isset($_SESSION["userid"]) && $s->u_id == $_SESSION["userid"]) {
			$isProprietaire = true;
 		}
 		
 		if($action == "voter") {
 			//vote
 			$s->saveVote();
 			//$s->clearSondageData();
 			print json_encode($s);
 			return;
 		
	 	} else if($action == "edition") {
	 		//Edition du sondage
	 		if(!$isProprietaire && $sid > 0) {
	 			throw new Exception("Ce sondage n'est pas le vôtre");
	 		}
 			$s->saveSondageInDB();
 		 	print json_encode($s);
 			return;
 		} else {
 			throw new Exception("Commande non valide");
 		}
 	} catch (Exception $e) {
 		//$s->clearSondageData();
 		$s->questions = null;
 		$s->status = SONDAGE_STATUS_ERREUR;	
 		$s->message = 'Erreur : '.  $e->getMessage();
 		print json_encode($s);
 		return;
    }
    
 } else if(isset($_GET['action'])) {
 	//Action reçue par GET -> ouvrir, fermer, raz, del
 	$action = $_GET['action'];
 	$sid = -1;
 	if(isset($_GET['sid']) && is_numeric($_GET['sid']) && $_GET['sid'] > 0) {
 		$sid = $_GET['sid'];
 	}
 	if($sid == -1) {
 		//Id non défini -> erreur
 		$s = new Sondage();
 		$s->status = SONDAGE_STATUS_ERREUR;
 		$s->message = "Identifiant du sondage invalide";
 		print json_encode($s);
 		return;
 	}
 	$s = new Sondage($sid);
 	$isProprietaire = false;
 	try {
 		if($sid > 0) {
 			$s->getSondageFromDB(1);
 			if(isset($_SESSION["userid"]) && $s->u_id == $_SESSION["userid"]) {
 				$isProprietaire = true;
 			}
 		}
 		if(!$isProprietaire) {
 			throw new Exception("Ce sondage n'est pas le vôtre");
 		}
 		if($action == "ouvrir") {
 			//ouverture du sondage
			$s->ouvertureSondage();
			print json_encode($s);
			//header('Location: afficher.php?sid='.$_GET['sid']); 
			return;
 		
	 	} else if($action == "fermer") {
	 		//fermeture du sondage
 			$s->fermetureSondage();
 			header('Location: afficher.php?sid='.$_GET['sid']);
 			return;
 		} else if($action == "raz") {
	 		//remise à zéro
 			$s->RAZ();
 			header('Location: gestion.php'); 
 			return;
 		} else if($action == "del") {
	 		//remise à zéro
 			$s->suppression();
 			header('Location: gestion.php'); 
 			return;
 		} else {
 			//suppressionTablesOrphelines();
 			throw new Exception("Commande non valide");
 		}
 	} catch (Exception $e) {
 		$s->clearSondageData();
 		$s->s_id = -1;
 		$s->status = SONDAGE_STATUS_ERREUR;	
 		$s->message = 'Erreur : '.  $e->getMessage();
 		print json_encode($s);
 		return;
    }
 } else {
	 /*
	  * Affichage du sondage, returner uniquement le sondage au format JSON
	  */
 	//Aucune action, on retourne le sondage
 	$sid = -1;
 	$s = new Sondage();
 	try {
 		if(isset($_GET['uid']) && is_numeric($_GET['uid']) && $_GET['uid'] > 0) {
 			$s = getSondageFromUID($_GET['uid']);
 			print json_encode($s);
 			return;
 		}
 		if(isset($_GET['sid']) && is_numeric($_GET['sid']) && $_GET['sid'] >= 0) {
 			$sid = $_GET['sid'];

 		} 
 		if($sid == -1)
 			throw new Exception("Identifiant sondage invalide");
 			
 		$s = new Sondage($sid);
 		$s->getSondageFromDB(1);
 		if(isset($_GET['format']) && $_GET['format'] == "csv") {
 			header("Content-type: text/csv;charset=utf-8");
 			header("Content-Disposition: attachment; filename=\"".$s->u_id."-".$s->s_id.".csv\"");
 			print $s->getSondageCSV();
 		} else {
 			print json_encode($s);
 		}

 		return;
 		
 	} catch (Exception $e) {
 		$s->clearSondageData();
 		$s->status = SONDAGE_STATUS_ERREUR;	
 		$s->message = ''.  $e->getMessage();
 		$s->s_id = 0;
 		print json_encode($s);
 		return;
    }
 }
 
/*
 * Mode attente ouverture de sondage
 * Le client indique l'identifiant du créateur du sondage,
 * dès que cette personne ouvre un sondage, l'identifiant
 * de ce dernier est retourné. Ainsi le navigateur sait quel
 * sondage demander. 
 */
 function getSondageFromUID($uid) {
 	global $bd;
 	global $sql_table_sondages;
 	// Retourne un sondage de l'utilisateur uid dès qu'il est ouvert
	$stmt = $bd->prepare("SELECT s_id " .
			"FROM $sql_table_sondages " .
			"WHERE u_id=? AND (etat=3 OR " .
			"UNIX_TIMESTAMP()-UNIX_TIMESTAMP(date_evaluation) < 30*60) " .
			"ORDER BY etat ASC, date_evaluation DESC LIMIT 1");
	
	if($stmt == null) {
		throw new Exception("Erreur dans la base de données, table $sql_table_sondages"); 
	}
			
	$stmt->bind_param('i', $uid);

	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($_sid);
	if($stmt->num_rows == 1) {
		$stmt->fetch();
	} else {
		$_sid = -1;
	}
	$stmt->close();
	
	if($_sid == -1) {
		$s = new Sondage();
		$s->clearSondageData();
		$s->texte = getNomProf($uid);
		$s->status = SONDAGE_STATUS_INFO;
		$s->message = "Pas de sondage en cours";
		$s->details_msg = "Cette page est automatiquement actualisée";
	} else {
		$s = new Sondage($_sid);
		$s->getSondageFromDB(1);
	}
	
	return $s;
 }
 
 function getNomProf($uid) {
 	global $bd;
 	global $sql_table_utilisateurs;
 	// Retourne un nom et prénom de l'utilisateur uid 
	$stmt = $bd->prepare("SELECT nom, prenom " .
			"FROM $sql_table_utilisateurs " .
			"WHERE u_id=? LIMIT 1");
	
	if($stmt == null) {
		throw new Exception("Erreur dans la base de données, table $sql_table_utilisateurs"); 
	}
			
	$stmt->bind_param('i', $uid);

	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($_nom, $_prenom);
	if($stmt->num_rows == 1) {
		$stmt->fetch();
		$nom = $_prenom . " " . $_nom;
	} else {
		$nom = "";
	}
	$stmt->close();
	
	return $nom;
 }
 
 
 function suppressionTablesOrphelines() {
	global $bd;
 	global $sql_table_sondages;
 	global $sql_prefix;
 	
 	$sql = "SHOW TABLE STATUS WHERE Name LIKE \"".$sql_prefix."sondage\_%\";";
	$r = $bd->query($sql);
	$sondagesOuverts = array();
	if($r){
		while ($row = $r->fetch_object()) {
			$sondagesOuverts[] = $row->Name;
    	}
    	$r->close();
	}
	//print_r ($sondagesOuverts);
	unset($sondagesOuverts[0]);
	//print_r ($sondagesOuverts);
 }
?>
