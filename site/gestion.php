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
 //require("formulaire.php");
 //date_default_timezone_set('UTC');
 require("session.php");
 $bd = connexionBD();
  checkSession();
date_default_timezone_set('UTC');
 
if(!(isset($_SESSION["userid"]) && $_SESSION["userid"] > 0)) {
	header('Location: index.php'); 
 	return;
}
 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="author" content="Christian Abegg" />
		<meta name="description" content="sondage" />
		<meta name="keywords" content="sondage, ntic, unige" />
		<title>Votamatic</title>
		<link rel="stylesheet"  href="jquery.mobile-1.0.1.min.css" />
		<script type="text/javascript" src="jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="jquery.json-2.3.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.0.1.min.js"></script>
		<script type="text/javascript">
		 //<![CDATA[ 
		 

function confDel(sid) {
	if(confirm("Etes-vous sûr de vouloir supprimer ce sondage ?")) {
		$(location).attr('href',"sondage.php?sid="+sid+"&action=del");
	}
}
function confRaz(sid) {
	if(confirm("Etes-vous sûr de vouloir remettre ce sondage à zéro ?")) {
		$(location).attr('href',"sondage.php?sid="+sid+"&action=raz");
	}
}
function affLien(sid) {
	var lien = "<?php 
	 	$lien_sondage = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
	 	$lien_sondage = str_replace("gestion.php", "afficher.php", $lien_sondage);
	 	$lien_sondage .= "?sid=";
	 	echo $lien_sondage;
	?>"+sid;
	prompt("Voici le lien direct vers le sondage (pour insertion dans les slides par ex.) :",lien);
}

//]]> </script>
	</head>
	<body>
			<div data-url="gestion.php" data-role="page">
			<div data-role="header">
				<h1 id="titre">Gestion des sondages</h1>
				<a href="index.php" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-left jqm-home">Accueil</a>
			</div>
			
			<div role="main" data-role="content">

<?php
 
 
	 //echo "<h2>Gestion</h2>\n";
	 echo "<a href=\"edition.php?sid=0\" rel=\"external\" data-role=\"button\" data-inline=\"true\">Créer un nouveau sondage</a>\n";
	 echo "<h2>Vos sondages</h2>\n";
	 
	 // Obtention des sondages créés par la personne
	 $my_sondages = array();
	 global $bd;
	 global $sql_table_sondages;
	 
	 $mois = array("Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"); 

	 
	 $stmt = $bd->prepare("SELECT s.s_id, s.u_id, s.texte, s.etat, " .
	 		"s.visibilite, s.duree, s.date_creation, UNIX_TIMESTAMP(s.date_evaluation), " .
	    	"s.nb_participants " .
	    	"FROM $sql_table_sondages AS s " .
	    	"WHERE s.u_id=? ORDER BY s.etat ASC, s.date_evaluation DESC");
	    	
	if($stmt == null) {
		throw new Exception("Erreur dans la base de données, table $sql_table_sondages"); 
	}
			
	$stmt->bind_param('i', $_SESSION['userid']);

	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($_s_id, $_u_id, $_texte, $_etat, 
						$_visibilite, $_duree, $_date_creation,
						$_date_evaluation, $_nb_participants);
						
	if($stmt->num_rows == 0) {
		$stmt->close();
		echo "<div>Vous n'avez pas encore créé de sondages</div>";
		return;
	}

	$tbl = "";
	$cur_etat = -1;
	$sondages = array();
	$nbOuverts = 0;
	$nbPrets = 0;
	$nbFinis = 0;
	
	while($stmt->fetch()) {
		$s = new Sondage(-1);
		$s->visibilite = $_visibilite;
		$s->etat = $_etat;
		if($_etat == SONDAGE_ETAT_ENCOURS)
			$nbOuverts++;
		if($_etat == SONDAGE_ETAT_CREATION || $_etat == SONDAGE_ETAT_PRET)
			$nbPrets++;
		if($_etat == SONDAGE_ETAT_FINI)
			$nbFinis++;
		$s->texte = $_texte;
		$s->date_evaluation = $_date_evaluation;
		$s->s_id = $_s_id;
		$sondages[] = $s;
	}
	$stmt->close();

	//ENCOURS
	if($nbOuverts > 0) {
		$tbl .= "<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"b\" data-collapsed=\"false\">";
		$tbl .= "<h2>Sondage en cours d'évaluation</h2>";
		$tbl .= "<div data-role=\"collapsible-set\" data-theme=\"c\" data-content-theme=\"c\">\n";
		foreach($sondages as $s) {
			if (!($s->etat == SONDAGE_ETAT_ENCOURS))
				continue;
			
			if($s->visibilite == 0)
				$_visibilite = "publics";
			else
				$_visibilite = "privés";
				
			$tbl .= "<div data-role=\"collapsible\" data-collapsed=\"false\">\n";
			$tbl .= "<h3>$s->texte</h3>\n";
			$tbl .= "<p>Sondage avec résultats <strong>$_visibilite</strong>";
			$tbl .= "</p>";
			$tbl .= "<div data-role=\"controlgroup\" data-type=\"horizontal\">";
			$tbl .= "<a href=\"afficher.php?sid=$s->s_id\" rel=\"external\" data-role=\"button\" data-icon=\"gear\">Afficher</a>\n";
			$tbl .= "</div>";
			$tbl .= "</div>\n";
		}
		$tbl .= "</div></div>\n";
	}
	
	//PRET
	if($nbPrets > 0) {
		if($nbOuverts == 0)
			$tbl .= "<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"b\" data-collapsed=\"false\">";
		else
			$tbl .= "<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"b\">";
		$tbl .= "<h2>Sondages prêts à l'évaluation</h2>";
		$tbl .= "<div data-role=\"collapsible-set\" data-theme=\"c\" data-content-theme=\"c\">\n";
		foreach($sondages as $s) {
			if (!($s->etat == SONDAGE_ETAT_CREATION || $s->etat == SONDAGE_ETAT_PRET))
				continue;
			
			if($s->visibilite == 0)
				$_visibilite = "publics";
			else
				$_visibilite = "privés";
				
			$tbl .= "<div data-role=\"collapsible\">\n";
			$tbl .= "<h3>$s->texte</h3>\n";
			$tbl .= "<p>Sondage avec résultats <strong>$_visibilite</strong>";
			$tbl .= "</p>";
			$tbl .= "<div data-role=\"controlgroup\" data-type=\"horizontal\">";
			if($nbOuverts == 0) {
				$tbl .= "<a href=\"afficher.php?sid=$s->s_id&action=ouvrir\" rel=\"external\" data-role=\"button\" data-icon=\"gear\">Evaluer</a>";
			}
			$tbl .= "<a href=\"javascript:affLien($s->s_id)\" rel=\"external\" data-role=\"button\" data-icon=\"star\">Lien</a>";
			$tbl .= "<a href=\"edition.php?sid=$s->s_id\" rel=\"external\" data-role=\"button\" data-icon=\"gear\">Edition</a>";
			$tbl .= "<a href=\"javascript:confDel($s->s_id)\" rel=\"external\" data-role=\"button\" data-icon=\"delete\">Supprimer</a>\n";
			$tbl .= "</div>";
			$tbl .= "</div>\n";
		}
		$tbl .= "</div></div>\n";
	}
	
	//FINI
	if($nbFinis > 0) {
		if($nbPrets == 0 && $nbOuverts == 0)
			$tbl .= "<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"b\" data-collapsed=\"false\">";
		else
			$tbl .= "<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"b\">";
		$tbl .= "<h2>Sondages déjà évalués</h2>";
		$tbl .= "<div data-role=\"collapsible-set\" data-theme=\"c\" data-content-theme=\"c\">\n";
		foreach($sondages as $s) {
			if (!($s->etat == SONDAGE_ETAT_FINI))
				continue;
			
			if($s->visibilite == 0)
				$_visibilite = "publics";
			else
				$_visibilite = "privés";
				
			$date  = date("j", $s->date_evaluation);
			$date .= " " . strtolower($mois[date("n", $s->date_evaluation)-1]);
			$date .= " " . date("Y", $s->date_evaluation);
				
			$tbl .= "<div data-role=\"collapsible\">\n";
			$tbl .= "<h3>$s->texte</h3>\n";
			$tbl .= "<p>Sondage avec résultats <strong>$_visibilite</strong>";
			$tbl .= ", évalué le <strong>$date</strong>";
			$tbl .= "</p>";
			$tbl .= "<div data-role=\"controlgroup\" data-type=\"horizontal\">";
			$tbl .= "<a href=\"afficher.php?sid=$s->s_id\" rel=\"external\" data-role=\"button\" data-icon=\"gear\">Afficher</a>";
			$tbl .= "<a href=\"javascript:affLien($s->s_id)\" rel=\"external\" data-role=\"button\" data-icon=\"star\">Lien</a>";
			$tbl .= "<a href=\"sondage.php?sid=$s->s_id&format=csv\" rel=\"external\" data-role=\"button\" data-icon=\"grid\">Export CSV</a>";
			$tbl .= "<a href=\"javascript:confRaz($s->s_id)\" rel=\"external\" data-role=\"button\" data-icon=\"refresh\">RAZ</a>";
			$tbl .= "<a href=\"javascript:confDel($s->s_id)\" rel=\"external\" data-role=\"button\" data-icon=\"delete\">Supprimer</a>\n";
			$tbl .= "</div>";
			$tbl .= "</div>\n";
		}
		$tbl .= "</div></div>\n";
	}
	
	//$tbl .= "</div>\n";
	
	echo $tbl;
 
?>
</div></div>
	</body>
</html>
