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
	if(isset($_GET['sid']) && is_numeric($_GET['sid']) && $_GET['sid'] > 0) {
 		$s_id = $_GET['sid'];
	} else {
		$s_id = 0;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="author" content="Christian Abegg" />
		<meta name="description" content="sondage" />
		<meta name="keywords" content="sondage, ntic, unige" />
		<meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1">
		
		<title>Votamatic</title>
		<link rel="stylesheet"  href="jquery.mobile-1.0.1.min.css" />
		<script type="text/javascript" src="jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="jquery.json-2.3.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.0.1.min.js"></script>
		<script type="text/javascript" src="votamatic.js"></script>
		<style type="text/css">
			.alignleft  { width: 75%; float: left; margin: 0px; font-weight:normal; }
			.alignright { float: right; margin: 0px; font-weight:bold; }
			.graph {height:16px;margin:1px 0;border:1px solid #000;overflow:hidden;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}
			.graphs {clear: both;height:16px;margin:1px 0;border:1px solid #000;overflow:hidden;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}
			.graphs-qcm {float:left;height:16px;border-right:1px solid #fff;background:#48b}
			.graph-kp-vrai {float:left;height:16px;border-right:1px solid #fff;background:#060}
			.graph-kp-faux {float:right;height:16px;margin-right:-1px;background:#c00}
		</style>
		
		<script type="text/javascript">
		 //<![CDATA[ 
		 	var obj; // Objet représentant le sondage
		    <?php 
		 		if(isset($_GET['action']) && $_GET['action'] = "ouvrir")
		 			echo "var action = \"ouvrir\";\n";
		 		else
		 			echo "var action = \"\";\n";
		 		?>
		 	var refreshId = -1;
		 	<?php 
		 		if(isset($_SESSION["userid"])) {
		 			print "var userid = ".$_SESSION["userid"].";// Identifiant utilisateur"; 
		 		} else {
		 			print "var userid = 0;// Identifiant utilisateur"; 
		 		}
		 	?>
		 	
		 	<?php 
		 		if(isset($_GET['uid']) && is_numeric($_GET['uid']) && $_GET['uid'] > 0)
		 			echo "var uid = ".$_GET["uid"].";";
		 		else
		 			echo "var uid = -1;";
		 		?>
		 	
		 	<?php echo "var sid = " .$s_id.";"; ?>

		 //]]> 

		</script>
	</head>
	<body><!-- class="ui-mobile-viewport">-->
		<div data-url="afficher.php" data-role="page">
			<div data-role="header">
				<h1 id="titre">Votamatic</h1>
				<a href="index.php" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-left jqm-home">Accueil</a>
			</div>
			
			<div role="main" data-role="content">

				<div id="gestion"></div>
				<div id="message"></div>
		
				<div id="sondage"></div>
			  	<div id="fullresponse"></div>
			  	<div id="vote"></div>
			  	<div id="sf"></div>
			  	<div id="result"></div>
			</div>
    	  	</div>
	</body>
</html>