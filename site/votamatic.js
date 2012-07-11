/**
* Votamatic
*
* @license GNU General Public License, version 3
* @copyright 2011-2012 Christian Abegg, Université de Genève
* @author Christian Abegg <christian.abegg@gmail.com>
*/

var _sid = -1;
var cpt = 0;
var lastSid = -1;
var lastEtat = -1;
var vote = false;

$(document).ready(function() {
	setTimer(true);
}
);

function setTimer(immediat) {
	if(action == "ouvrir" && sid > 0) {
		ouvrir();
	}
	else if(uid > 0 && refreshId == -1) {
		//timer tt les 5s sondage.php?uid=uid
		//si retour > 0 alors afficher le sondage sid=code de retour
		refreshId = setInterval(function() {
			$.get('sondage.php', {uid: uid}, checkSID, "text");
		}, 5000);
		if(immediat)
			$.get('sondage.php', {uid: uid}, checkSID, "text");

	} else if (immediat) {
		$.get('sondage.php', {sid: sid}, checkSID, "text");
	}
}

function removeTimer() {
	clearInterval(refreshId);
	refreshId = -1;
}

function checkSID(data) {
	cpt++;
	obj = jQuery.parseJSON(data);
	if(obj.s_id > 0) {
		removeTimer();
		if(obj.u_id > 0 && obj.u_id == userid && obj.etat == 3 && uid == -1 && obj.duree > 0) {
			refreshId = setInterval(function() {
				$(location).attr('href',"sondage.php?sid="+obj.s_id+"&action=fermer");
				}, (obj.duree+1)*1000);
		}
		//$("#message").html("");
		//$.get('sondage.php', {sid: data}, showResult, "text");
		if(action=="ouvrir") {
			$(location).attr('href',"afficher.php?sid="+obj.s_id);
			return;
		}
		showSondage();
	} else {
		$("#sondage").html("");
		$("#titre").html("Votamatic");
		afficherMessage();
		if(action.length == 0)
			setTimer();
	}
}

function showSondage() {
  //$("#fullresponse").html("Full response: " +res);
  //obj = jQuery.parseJSON(res);
  afficheGestion();
  
  if(vote) {
  	$("#sondage").html("");
  	afficherMessage();
  	setTimer();
  	vote = false;
  } else if(obj.etat == 3) {
    // Le sondage est ouvert, on affiche le formulaire
    //s_id = -1;
    $("#sondage").html("");
    if(obj.status == 0) {
    	$("#message").html("");
    	afficherSondage();
    	if(action.length > 1) {
    		action = "";
    		$(location).attr('href',"afficher.php?sid="+obj.s_id);
    	}
    } else {
  		if(!(lastSid == obj.s_id && lastEtat == obj.etat))
  			afficherMessage();
  		setTimer();
  	}
  } else if (obj.etat == 4) {
  	// Le sondage a été évalué, on affiche les résultats
  	if(!(lastSid == obj.s_id && lastEtat == obj.etat)) {
  		lastSid = obj.s_id;
  		lastEtat = obj.etat;
  		$("#sondage").html("");
  		$("#message").html("");
  		afficherResultats();
  	}
  	setTimer();
  } else {
  	setTimer();
  }
}

function afficherMessage() {
	
	if(obj.status == 1) {
		var s = "<div class=\"ui-bar ui-bar-e\">";
		s += "<h3 style=\"margin-top:1px;\">"+obj.message+"</h3>";
		if(obj.details_msg.length > 0)
			s += "<p style=\"font-size:85%;\">"+obj.details_msg+"</p>";
		s += "</div>";
		$("#message").html(s);
	}
	if(obj.status == 2) {
		var s = "<div class=\"ui-bar ui-bar-e\">";
		s += "<h3 style=\"margin-top:1px;\">"+obj.message+"</h3>";
		if(obj.details_msg.length > 0)
			s += "<p style=\"font-size:85%;\">"+obj.details_msg+"</p>";
		s += "</div>";
		$("#message").html(s);
	}
	if(obj.status == 3) {
		var s = "<div class=\"ui-bar ui-bar-b\">";
		s += "<h3 style=\"margin-top:1px;\">"+obj.message+"</h3>";
		if(obj.details_msg.length > 0)
			s += "<p style=\"font-size:85%;\">"+obj.details_msg+"</p>";
		s += "</div>";
		$("#message").html(s);
		if(obj.texte.length>0) {
			$("#titre").html(obj.texte).trigger('create');
		}
	}
}

function afficherSondage() {
  //Titre
  $("#titre").html(obj.texte).trigger('create');
  
  //Balise ouverture formulaire
  $formulaire = "<form id=\"sondagef\" action=\"javascript:\" >";
  
  // Boucle des questions
  for($i = 0; $i<obj.questions.length; $i++) {
  	
  	// Questions K prime
  	if (obj.questions[$i].type == 3) {
  		$formulaire += '<div data-role="fieldcontain">\n';
  		$formulaire += "<p><strong>"+obj.questions[$i].texte+"</strong></p>";
  		for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
  			//$formulaire += '<div data-role="fieldcontain">\n';
	  		$formulaire += '<fieldset data-role="controlgroup" data-type="horizontal"><legend><strong>'+obj.questions[$i].reponses[$j].texte+'</strong></legend>\n';
	  		$formulaire += '<input name="kp-'+$i+'-'+$j+'" id="kp-'+$i+'-'+$j+'v" value="vrai" type="radio" /><label for="kp-'+$i+'-'+$j+'v">Vrai</label>\n';
	  		$formulaire += '<input name="kp-'+$i+'-'+$j+'" id="kp-'+$i+'-'+$j+'f" value="faux" type="radio" /><label for="kp-'+$i+'-'+$j+'f">Faux</label>\n';
	  		$formulaire += '</fieldset>\n';
  		}
  		$formulaire += '</div>\n';

	// Questions à réponse unique / réponses multiples 
  	} else {
  		$formulaire += '<div data-role="fieldcontain">\n';
  		$formulaire += '<fieldset data-role="controlgroup"><legend><strong>'+obj.questions[$i].texte+'</strong></legend>\n  ';
  
	  	for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
	  		if (obj.questions[$i].type == 2) {
	  			$formulaire += "<input type=\"radio\" id=\"ra-"+$i+"-"+$j+"\" name=\"ra-"+$i+"\" value=\""+$j+"\" /><label for=\"ra-"+$i+"-"+$j+"\">"+obj.questions[$i].reponses[$j].texte+"</label>\n";
	  		} else if (obj.questions[$i].type == 1) {
	  			$formulaire += "<input type=\"checkbox\" id=\"cb-"+$i+"-"+$j+"\" name=\"cb-"+$i+"-"+$j+"\" value=\""+$j+"\" /><label for=\"cb-"+$i+"-"+$j+"\">"+obj.questions[$i].reponses[$j].texte+"</label>\n";
	  		} 
	  	}
	  	$formulaire += '</fieldset></div>\n';
	}	  	
	
	
  }//Boucle questions
  
  $formulaire += "<input type=\"submit\" value=\"Voter\"/>\n";
  $formulaire += "</form>";
  
  $("#sondage").append($formulaire).trigger('create');
  
    $('#sondage').submit(function() {
        resetSondage();
        
        var res = $.toJSON($("form").updateSondage());
        
        if(!checkVote()) {
          		alert("Afin de valider le vote, veuillez répondre à chaque question");
          		return false;
        }
        //_sid = obj.s_id;
        lastSid = obj.s_id;
        lastEtat = obj.etat;
        vote = true;
        $.post("sondage.php", "action=voter&sid="+obj.s_id+"&sondage="+res, function(data) { checkSID(data); });
        
        return false;
    });
	
}

/**
 * Génération code HTML pour l'affichage des résultats
 */
function afficherResultats() {
	$("#titre").html(obj.texte);
	//$("#sondage").html("Résultats");
	$formulaire = "<h3>Résultats du sondage<br><i>";
	$formulaire += obj.nb_participants
	if(obj.nb_participants > 1) {
		$formulaire += " participants</i></h3>";
	} else {
		$formulaire += " participant</i></h3>";
	}
	for($i = 0; $i<obj.questions.length; $i++) {
  	$formulaire += "<font style=\"font-size: 120%;\"><strong>"+obj.questions[$i].texte+"</strong></font><br/>";

    if (obj.questions[$i].type == 3) {
    	for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
			$formulaire += "<div id=\"textbox\"><p class=\"alignleft\">"
	  		$formulaire += obj.questions[$i].reponses[$j].texte+"</p>\n";
	  		pourcent = Math.round(obj.questions[$i].reponses[$j].nb_voix/obj.nb_participants*1000)/10;
			if (pourcent>50) 
		  		$formulaire += "<p class=\"alignright\"><font style=\"color:#060;\">"+pourcent.toFixed(1)+" %</font></p></div>";
			else if (pourcent < 50)
		  		$formulaire += "<p class=\"alignright\"><font style=\"color:#c00;\">"+(100-pourcent).toFixed(1)+" %</font></p></div>";
		  	else
		  		$formulaire += "<p class=\"alignright\">"+(100-pourcent).toFixed(1)+" %</p></div>";
	 		
	  		$formulaire += "<div style=\"clear: both;\">";
	  		pourcent = pourcent.toFixed(0);
	  		$formulaire += "<div class=\"graph\">";
            $formulaire += "<div class=\"graph-kp-vrai\" style=\"width: "+(pourcent)+"%;\"></div>";
            $formulaire += "<div class=\"graph-kp-faux\" style=\"width: "+((100-pourcent))+"%;\"></div>";
            $formulaire += "</div>";
            $formulaire += "<br/>";
	  		
	  	}
    } else {
    	pourcent = 0;
    	// Déterminer la réponse la plus populaire et l'utiliser
    	// son pourcentage comme maximum
    	for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
    		tmp = Math.round(obj.questions[$i].reponses[$j].nb_voix/obj.nb_participants*10000)/100;
    		if (tmp > pourcent) {
    			pourcent = tmp;
    		} 
    	}
    	coeff = 100.0/pourcent;
	for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
		$formulaire += "<div id=\"textbox\"><p class=\"alignleft\">"
		$formulaire += obj.questions[$i].reponses[$j].texte+"</p>";
		pourcent = Math.round(obj.questions[$i].reponses[$j].nb_voix/obj.nb_participants*10000)/100;
		$formulaire += "<p class=\"alignright\">"+pourcent.toFixed(1)+" %</p></div>";
		$formulaire += "<br>";
		$formulaire += "<div class=\"graphs\">";
		$formulaire += "<div class=\"graphs-qcm\" style=\"width: "+(pourcent*coeff)+"%;\"></div>";
        $formulaire += "</div>";
		$formulaire += "<br/>";
	}
   }
  }
  
  // Affichage des balises du formulaire créées ci-dessus
  // Un rafraichissement de la page est requis pour que jQueryMobile 
  // puisse appliquer les CSS sur les éléments nouvellement ajouté
  $("#sondage").append($formulaire).trigger('create');
  
}

/*
 * Affichage du menu permettant l'ouverture et la fermeture du sondage
 * Ce menu est uniquement disponible pour la personnne qui a créé le sondage
 */
function afficheGestion() {
	if(obj.u_id > 0 && obj.u_id == userid && obj.etat <= 3 && uid == -1) {
		//alert("obj.u_id="+obj.u_id+",userid="+userid);
		$g  = '<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="b">';
		$g += '<li data-role="list-divider">Gestion du sondage</li>';
		if (obj.etat == 3) {
			$g += '<li><a rel=external href=\"sondage.php?sid='+obj.s_id+'&action=fermer\">Fermer le sondage</a></li></ul>';
		}
		if (obj.etat < 3)
			$g += '<li><a rel=external href=\"afficher.php?sid='+obj.s_id+'&action=ouvrir\">Ouvrir le sondage</a></li></ul>';
		$("#gestion").html($g).trigger('create');
	}
}

function ouvrir() {
	$.get('sondage.php', {sid: sid, action: 'ouvrir'}, checkSID, "text");
}

/*
function afficheRes(data) {
	//après vote

	$("#sondage").html("");
	$("#vote").html("");
	$("#fullresponse").html("");
	$("#sf").html("");
	//$("#result").html(data);
	obj = jQuery.parseJSON(data);
	afficherMessage();
	setTimer();
	
}*/

/**
 * Lecture des choix effectués
 */
$.fn.updateSondage = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
		// split name, voir si kp, ra ou cb
		// si ra, -> prendre la 2e valeur du tableau
		// si cb, -> prendre la 2e valeur du tableau
		// si kp, -> prendre la 2e valeur pour la question et la 3e pour réponse
		var t = this.name.split("-");
		
		if(this.value == "faux")
			obj.questions[t[1]].reponses[t[2]].nb_voix = -1;
		else if(this.value == "vrai")
			obj.questions[t[1]].reponses[t[2]].nb_voix = 1;
		else			
    		obj.questions[t[1]].reponses[this.value].nb_voix = 1;
    });
    return obj;
};

/**
 * Vérification que le vote est correct, soit au moins une réponse par question
 */
function checkVote() {
 for($i = 0; $i<obj.questions.length; $i++) {
  	obj.questions[$i].texte = "";
  	var okq = false;
  	if (obj.questions[$i].type == 1) {
  		for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
  			if(obj.questions[$i].reponses[$j].nb_voix == 1) {
  				okq = true;
  			}
  		}
  	}
  	if (obj.questions[$i].type == 2) {
  	    nb_rep = 0;
  		for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
  			nb_rep += obj.questions[$i].reponses[$j].nb_voix;
  		}
  		if(nb_rep == 1)
  			okq = true;
  	}
  	if (obj.questions[$i].type == 3) {
  	    nb_rep = 0;
  		for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
  			nb_rep += Math.abs(obj.questions[$i].reponses[$j].nb_voix);
  		}
  		if(nb_rep == obj.questions[$i].reponses.length)
  			okq = true;
  	}

  	if (!okq) {
  		return false;
  	}
  }
  return true;
}

function resetSondage() {
  obj.texte = "";
  obj.nb_participants = 0;
  for($i = 0; $i<obj.questions.length; $i++) {
  	obj.questions[$i].texte = "";
  	for($j = 0; $j<obj.questions[$i].reponses.length; $j++) {
  		obj.questions[$i].reponses[$j].nb_voix = 0;
  		obj.questions[$i].reponses[$j].texte = "";
  	}
  }
};
