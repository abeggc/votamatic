<?php

/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

if(isset($_GET['sid']) && is_numeric($_GET['sid']) && $_GET['sid'] >= 0) {
 	$s_id = $_GET['sid'];
} else {
	print "erreur";
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
		<link rel="stylesheet" href="jquery.mobile-1.0.1.min.css" />
		<script type="text/javascript" src="jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="jquery.json-2.3.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.0.1.min.js"></script>
		<script type="text/javascript" src="edition.js"></script>
		<script type="text/javascript">
		 //<![CDATA[ 
		 
		 var obj; // Objet représentant le sondage
		<?php echo "var sid = " .$s_id.";"; ?>
		String.prototype.trim = function () {
    		return this.replace(/^\s*/, "").replace(/\s*$/, "");
		}
		

//]]> 

</script>

	</head>
	<body class="ui-mobile-viewport">
		<div data-url="edition.php" data-role="page">
			<div data-role="header">
				<h1 id="titre">Votamatic</h1>
				<a href="gestion.php" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-left jqm-home">Accueil</a>
			</div>
			<div role="main" data-role="content">

			<?php
				if ($s_id > 0) {
					echo "			<h1>Modification d'un sondage</h1>";
				} else {
					echo "			<h1>Création d'un sondage</h1>";
				}
			?>

			<div id="sondage"></div>

			<div id="fullresponse"></div>
				  <br/>
	  <div id="vote"></div>
	  <br/>
	  <div id="sf"></div>
	  <br/>
	  <div id="result"></div>
	  </div>
	  </div>
	</body>
</html>
