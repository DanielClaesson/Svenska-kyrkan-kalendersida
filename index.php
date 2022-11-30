<?php
	/* ÄNDRA VÄRDENA NEDANFÖR DENNA RAD */
	$api_nyckel = 'LÄGG IN DIN API-NYCKEL HÄR'; //Skapa ett konto på https://api.svenskakyrkan.se/ och generera en API-nyckel som du klistar in på denna rad
	$organisations_id = '20271'; //Fyll i enhets-ID för den församling/det pastorat som du vill hämta kalenderhändelser för
	$organisation_namn = 'Svenska kyrkan Härnösand'; //Skriv in namnet på församlingen/pastoratet som kalenden gäller för
	$webbsida_rubrik = 'Svenska kyrkan Härnösand'; //Rubriken längst upp på kalendersidan

	$max_handelser = '25'; //Max antal händelser att visa i kalendern
	/* ÄNDRA INGET EFTER DENNA RAD */
	
	
	//Om det finns ett organisations-ID i URL-en
	if (isset ($_GET['organisationsid']) && $_GET['organisationsid'] !== '') {
		$organisations_id = $_GET['organisationsid'];
		//Ta bort allt förutom siffror och kommatecken från ID:et
		$organisations_id =preg_replace("/[^0-9,]/", "", $organisations_id);
	}
	
	//Börja med att starta en session
	session_start();

	//Headers
	header('Content-type: text/html; charset=utf-8');
	
	//Tidszon
	date_default_timezone_set('Europe/Stockholm');
	
	//Aktuellt datum och tid
	$aktuellt_datum = date('Ymd');
	$aktuell_tid = date('H.i');
	
	$datum_imorgon = date("Ymd", strtotime("tomorrow"));
	
	$hitta_passerad_tid = strtotime('-2 hour');
	$passerad_tid = date("H.i", $hitta_passerad_tid);
	
	//Grundvariabler
	$kalender = '';
	$antal_hittade = '0';
	$kalender_resultat = '';
	$medverkande = '';
	
	$datumArray = array();
	
	$antal_tillagda = '0';
	
	
	//Länk till kalenderdatan
	$kalender_api_lank = file_get_contents('https://api.svenskakyrkan.se/calendarsearch/v4/SearchByParent?apikey='.$api_nyckel.'&orgid='.$organisations_id.'&$orderby=StartTime');
	
	//Om API-länken inte fungerar
	if($kalender_api_lank === FALSE) {
		$kalender = 'Ett fel uppstod och kalendern kunde inte laddas. Vi beklagar detta.';
	}
	else {
		//Gör om JSON till en array
		$svk_kalender_array = json_decode ($kalender_api_lank, true);
		
		//Antal aktiviteter i kalendern
		$antal_aktiviteter  = count($svk_kalender_array['value']);

		//Ta bort 1 från antalet, första raden är ju 0
		$antal_aktiviteter--;
		
		//Om det finns några aktiviteter att loopa
		if ($antal_aktiviteter > '0') {
		
			//Loopa igenom alla aktiviteter
			for ($ladda_aktivitet = 0; $ladda_aktivitet <= $antal_aktiviteter; $ladda_aktivitet++) {
				$startdatum = $svk_kalender_array['value'][$ladda_aktivitet]['StartTime'];
				$starttid = $svk_kalender_array['value'][$ladda_aktivitet]['EventTime'];
				$slutdatum = $svk_kalender_array['value'][$ladda_aktivitet]['StopTime'];
				$titel = $svk_kalender_array['value'][$ladda_aktivitet]['Title'];
				$beskrivning = $svk_kalender_array['value'][$ladda_aktivitet]['Description'];
				$plats = $svk_kalender_array['value'][$ladda_aktivitet]['PlaceDescription'];
				$raderad = $svk_kalender_array['value'][$ladda_aktivitet]['Deleted'];
				
				//Om det inte redan har lagts till max antal aktiviteter i kalendern och denna aktivitet INTE är raderad
				if ($antal_tillagda < $max_handelser && empty($raderad)) {
					
					//Bara datum i start- och sluttiderna
					$startdatum = substr($startdatum, 0, strpos($startdatum, 'T'));
					$slutdatum = substr($slutdatum, 0, strpos($slutdatum, 'T'));
					//Enbart siffror i datumen
					$startdatum = str_replace(array('-'), array(''), $startdatum);
					$slutdatum = str_replace(array('-'), array(''), $slutdatum);
					//Byt kolon mot punkt i starttiden
					$starttid = str_replace(array(':', ' '), array('.', ''), $starttid);
					
					//Om det finns en sluttid
					if (strpos($starttid, '-') !== false) {
						$starttid_ratt = strtok($starttid, '-');
						$sluttid = str_replace(array($starttid_ratt.'-'), array(''), $starttid);
						$sluta_visas = $sluttid;
						$sluttid = '-'.$sluttid;
						$starttid = $starttid_ratt;
					}
					else {
						$sluttid = '';
						$sluta_visas = date($starttid, strtotime('+2 hours'));
					}
					
					//Om aktiviteten inte är passerad
					if ($slutdatum > $aktuellt_datum || ($slutdatum == $aktuellt_datum && $sluta_visas >= $aktuell_tid)) {
						
						//Ta bort rum (eller annat efter ett kommatecken) i plats
						if (strpos($plats, ',') !== false) {
							$plats = substr($plats, 0, strpos($plats, ','));
						}
						
						//Lägg till ett kommatecken om det finns en plats
						if (!empty($plats) && $plats !== '') {
							$plats = ', '.$plats;
						}
						
						//Om det finns en beskrivning
						if (!empty($beskrivning)) {
							//Ingen HTML i beskrivningen
							$beskrivning = str_ireplace(array('<B>', '</B>', '<BR /><BR />', '<BR />', '<BR>', '. . ', '.. '), array('', '', '', '. ', '. ', '. ', '. '), $beskrivning);
							$beskrivning = preg_replace('#<a.*?>.*?</a>#i', '', $beskrivning);
						}
						else {
							$beskrivning = '';
						}
						//Lägg till infotext och ev. beskrivning
						$beskrivning = '<span class="infotext"> '.$medverkande.' '.$beskrivning.'</span>';
						
						//Bättre format på datumet
						$startdatum_visning = date('l j M',strtotime($startdatum));
						
						//Om det är dagens datum
						if ($startdatum == $aktuellt_datum) {
							$startdatum_visning = 'I dag '.$startdatum_visning;
						}
						//Om det är morgondagens datum
						elseif ($startdatum == $datum_imorgon) {
							$startdatum_visning = 'I morgon '.$startdatum_visning;
						}
						
						//Översätt veckodagar till svenska
						$startdatum_visning = str_ireplace(array('Monday','Tuesday','Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), array('Måndag','Tisdag','Onsdag','Torsdag','Fredag','Lördag','Söndag'),$startdatum_visning);
									
						//Översätt månader till svenska
						$startdatum_visning = str_ireplace(array('Jan','Feb','Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'), array('januari','februari','mars','april','maj','juni','juli', 'augusti', 'september', 'oktober', 'november', 'december'),$startdatum_visning);
						
						//Om det är den första händelsen detta datum
						if (!in_Array($startdatum, $datumArray)) {
							
							//Lägg till en datumrubrik
							$kalender .= '<div class="datum"><h2>'.$startdatum_visning.'</h2></div>'."\n";
							
							//Lägg in datumet i arrayen
							$datumArray[] = $startdatum;
							
						}
						//Lägg till händelsen i kalendern
						$kalender .= '<div class="handelse"><p><span class="fet">'.$starttid.$sluttid.' '.$titel.'</span>'.$plats.$beskrivning.'</p></div>'."\n";
						
						$antal_tillagda++;
					}
				}
			}
		}
		//Om det inte finns några aktiviteter
		else {
			$kalender = 'Ett fel uppstod och kalendern kunde inte laddas. Vi beklagar detta.';
		}
	}
?>
<html>

<head>
	
	<meta charset="utf-8">
	
	<title>Kalender för <?php echo $organisation_namn; ?></title>
	
	<link rel="stylesheet" type="text/css" href="/style.css" media="all" />
	
	<!-- fix för mobiler -->
    <meta name="viewport" content="width=device-width; initial-scale=1; maximum-scale=1">
	
</head>

<body>
	
	<div id="header">
		<h1><?php echo $webbsida_rubrik; ?></h1>
	</div>
	
	<div id="wrapper">
		
		<?php echo $kalender; ?>
		
	</div>
	
	<div id="gradient"></div>
	
</body>

</html>
