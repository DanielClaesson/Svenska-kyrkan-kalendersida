<?php
        $api_nyckel = '';

        // Lägg api-nyckeln som text i en fil du döper till api och lägger jämte index.php
        // OBS VIKTIGT! Se till i webbserverns inställningar att filen inte exponeras på internet! (.htaccess m.m.)
        if (file_exists('api')) {
                $file = fopen('api', 'r') or die('Kunde inte öppna api-nyckelfil');
                $api_nyckel = trim(fgets($file));
                fclose($file);
        }
        else { //Finns ingen api-fil behöver du lägga api-nyckeln som get parameter i länken, ?api=abc123
                if (isset($_GET['api']) && $_GET['api'] !== '') {
                        $api_nyckel = $_GET['api'];
                }
                else {
                        die('Ingen api-nyckel hittad!');
                }
        }

        $organisations_id = '7681'; //Default organisations-ID
        $organisation_namn = 'Svenska kyrkan'; //Default organisation
        $webbsida_rubrik = 'Svenska kyrkan'; //Default rubrik
        $max_handelser = '50'; //Max antal händelser att visa i kalendern
		$skrolla = '0'; //Skroll är inaktiverat per default
		$skroll_status = ''; //Tom som standard

        $location_id = '';
        $location_name = '';
        
        $calendarSubGroup = ''; //Händelsetyp ID

        $sluttider = '1';

        // Färger hämtade från Svenska kyrkans grafiska profil (ny från 2024)
        // https://www.svenskakyrkan.se/grafiskprofil
        $colors = [
                'vinröd' => '#7D0037',
                'orange' => '#FF785A',
                'rosa' => '#FFC3AA',
                'beige' => '#FFEBE1',
                'guld' => '#BC8E4C',

                'mörklila' => '#412B72',
                'lila' => '#9B87FF',
                'ljuslila' => '#CDC3FF',

                'mörkgrön' => '#00554B',
                'grön' => '#28A88E',
                'ljusgrön' => '#BEE1C8',
                
        ];
        $color = $colors['vinröd'];

        //Om det finns ett organisations-ID i URL-en
        if (isset ($_GET['orgID']) && $_GET['orgID'] !== '') {
                $organisations_id = $_GET['orgID'];
                //Ta bort allt förutom siffror och kommatecken från ID:et
                $organisations_id = preg_replace("/[^0-9,]/", "", $organisations_id);
        }
        elseif (isset ($_GET['organisationsid']) && $_GET['organisationsid'] !== '') {
                $organisations_id = $_GET['organisationsid'];
                //Ta bort allt förutom siffror och kommatecken från ID:et
                $organisations_id =preg_replace("/[^0-9,]/", "", $organisations_id);
        }
		elseif (isset ($_GET['enhet']) && $_GET['enhet'] !== '') {
                $organisations_id = $_GET['enhet'];
                //Ta bort allt förutom siffror och kommatecken från ID:et
                $organisations_id =preg_replace("/[^0-9,]/", "", $organisations_id);
        }

        //Möjlighet att filtrera aktiviteter per locationID
        if (isset($_GET['locationID']) && $_GET['locationID'] !== '') {
                $location_id = $_GET['locationID'];
        }
		
        if (isset($_GET['csg']) && $_GET['csg'] !== '') {
                $calendarSubGroup = explode(',', $_GET['csg']); //Hantera flera Händelsetyper för or-filtrering i api:t
        }
		
        //Möjlighet att sätta Title via parameter (Exempel: ?orgName=Svenska kyrkan Härnösand)
        if (isset($_GET['orgName']) && $_GET['orgName'] !== '') {
                $organisation_namn = $_GET['orgName'];
        }

        //Möjlighet att sätta kalenderrubriken via parameter (Exempel: ?header=Svenska kyrkan Härnösand)
        if (isset($_GET['header']) && $_GET['header'] !== '') {
                $webbsida_rubrik = $_GET['header'];
        }
		
		//Möjlighet att välja grön eller lila bakgrundsfärg på rubriksektionen via parameter (Antingen ?color=lila eller ?color=grön)
        if (isset($_GET['color']) && ($_GET['color'] == 'lila' || $_GET['color'] == 'grön')) {
			//Om grön färg är vald
			if ($_GET['color'] == 'grön') {
				$color = $colors['mörkgrön'];
			}
			//Om lila färg är vald
			elseif($_GET['color'] == 'lila') {
				$color = $colors['mörklila'];
			}
			//Välj standardfärgen vinröd
			else {
				$color = $colors['vinröd'];
			}
		}
		
		
        //Möjlighet att sätta eget antal händelser att ladda, max 50 st (Exempel: ?antal=20)
        if (isset($_GET['antal']) && is_numeric($_GET['antal']) && $_GET['antal'] > '0' && $_GET['antal'] <= '50') {
            $max_handelser = $_GET['antal'];
        }
		
        //Möjlighet att aktivera skroll på webbsidan (Exempel: ?skrolla)
		if (isset($_GET['skrolla'])) {
            $skrolla = '1';
			$skroll_status = ' class="skrolla"';
        }
		
        //Möjlighet att ta bort alla sluttider (Exempel: ?sluttider=nej)
        if (isset($_GET['sluttider']) && $_GET['sluttider'] == 'nej') {
            $sluttider = '0';
        }
		
        //Starta en session
        session_start();

        //Headers
        header('Content-type: text/html; charset=utf-8');

        //Tidszon
        date_default_timezone_set('Europe/Stockholm');

        //Aktuellt datum och tid
        $aktuellt_datum = date('Ymd');
        $aktuellt_datum_iso = date('Y-m-d');
        $aktuell_tid = date('H.i');

        $datum_imorgon = date("Ymd", strtotime("tomorrow"));

        $hitta_passerad_tid = strtotime('-2 hour');
        $passerad_tid = date("H.i", $hitta_passerad_tid);

        //Grundvariabler
        $kalender = '';
        $antal_hittade = '0';
        $kalender_resultat = '';
        $medverkande = '';
		
		//Arrayer som kommer användas
        $datumArray = array();
		$platserArray = array();

        $antal_tillagda = '0';

        //Länk till kalenderdatan
		$url = 'https://svk-apim-prod.azure-api.net/calendar/v1/event/search?subscription-key='.$api_nyckel.'&from='.$aktuellt_datum_iso.'&owner_id='.$organisations_id.'&limit='.$max_handelser;
		
		//Låt API:t filtrera location_id om vi filtrerar på ett sådant
		if ($location_id !== '') {
			// %20 = space
			// %27 = '
			$url .= '&place_id='.$location_id;
        }

        $kalender_api_lank = file_get_contents($url);

        //Om API-länken inte fungerar
        if($kalender_api_lank === FALSE) {
                $kalender = '<p>Ett fel uppstod och kalenderhändelserna kunde inte laddas.</p>';
				$kalender .= $url;
        }
		//API-länken fungerar
        else {	
				//Gör om JSON till en array
                $svk_kalender_array = json_decode ($kalender_api_lank, true);
				
				
                //Antal aktiviteter i kalendern
				$antal_aktiviteter = count($svk_kalender_array['result']);

				/*echo 'Success här';
				die();*/
                //Om det finns några aktiviteter att loopa
                if ($antal_aktiviteter > '0') {
						
						//Ta bort 1 från antalet, första raden är ju 0
						$antal_aktiviteter--;
						
                        //Loopa igenom alla aktiviteter
                        for ($ladda_aktivitet = 0; $ladda_aktivitet <= $antal_aktiviteter; $ladda_aktivitet++) {
							$aktivitet_id = $svk_kalender_array['result'][$ladda_aktivitet]['id'];
							$startdatum = $svk_kalender_array['result'][$ladda_aktivitet]['startLocalTime']['date'];
							$starttid = $svk_kalender_array['result'][$ladda_aktivitet]['startLocalTime']['time'];
							$slutdatum = $svk_kalender_array['result'][$ladda_aktivitet]['endLocalTime']['date'];
							$sluttid = $svk_kalender_array['result'][$ladda_aktivitet]['endLocalTime']['time'];
							$titel = $svk_kalender_array['result'][$ladda_aktivitet]['title'];
							
							//Loopa igenom alla medverkande
							$medverkande = '';
							$alla_medverkande = $svk_kalender_array['result'][$ladda_aktivitet]['performers'];							
							for ($i = 0; $i <= '10'; $i++) {
								$aktuell_medverkande_namn = $alla_medverkande[$i]['name'];
								$aktuell_medverkande_titel = $alla_medverkande[$i]['title'];
								//Lägg till personen i listan över medverkande
								if (!empty($aktuell_medverkande_namn) && $aktuell_medverkande_namn !=='') {
									$medverkande .= $aktuell_medverkande_titel.': '.$aktuell_medverkande_namn.'. ';
								}
							}
							
							//Om det finns en beskrivning
							if(isset ($svk_kalender_array['result'][$ladda_aktivitet]['description'])) {
								$beskrivning = $svk_kalender_array['result'][$ladda_aktivitet]['description'];
							}
							//Ingen beskrivning
							else {
								$beskrivning = '';
							}
							
							//Om det finns en plats
							if(isset ($svk_kalender_array['result'][$ladda_aktivitet]['place']['id'])) {
								$plats_id = $svk_kalender_array['result'][$ladda_aktivitet]['place']['id'];
								$plats = '';
								
								//Om platsens namn finns i arrayen
								if (array_key_exists($plats, $platserArray)) {
									$plats = $platserArray[$plats];
								}
								//Leta upp platsens namn
								else {
									//Länk till plats-API:et
									$plats_api_lank = file_get_contents('https://api.svenskakyrkan.se/platser/v4/place?apikey=3c6f078a-55e8-4b38-9a7c-269e79862f3e&id='.$plats_id);

									//Om API-länken för platsen inte fungerar, visa inget platsnamn
									if($plats_api_lank === FALSE) {
										$plats = '';
									}
									//API-länken för platsen fungerar
									else {
										//Gör om JSON till en array
										$svk_plats_array = json_decode ($plats_api_lank, true);
										//print_r($svk_plats_array);
										//Hämta platsens namn
										$plats = $svk_plats_array['results']['0']['name'];
										
										//Lägg in platsen i arrayen
										$platserArray[$plats_id] = $plats;
									}
									
								}
								
							}
							//Ingen plats
							else {
								$plats = '';
							}

							if ($location_id !== '') {
									$location_name = $plats;
									
									if (isset($_GET['header']) && $_GET['header'] !== '') {
											$webbsida_rubrik = $_GET['header']. ' '.$plats;
									}
									else {
										$webbsida_rubrik = $plats;
									}
							}

							//Om det inte redan har lagts till max antal aktiviteter i kalendern
							if ($antal_tillagda < $max_handelser) {

								//Ändra format på datuom och tid
								$startdatum = str_replace(array('-'), array(''), $startdatum);
								$slutdatum = str_replace(array('-'), array(''), $slutdatum);
								$starttid = str_replace(array(':'), array('.'), $starttid);
								$sluttid = str_replace(array(':'), array('.'), $sluttid);
								$starttid = substr($starttid, 0, -3);
								$sluttid = substr($sluttid, 0, -3);

								//Om sluttider inte ska visas
								if ($sluttider == '0') {
									$aktivitetstid = $starttid;
								}
								//Lägg ihop start- och sluttid för aktiviteten
								else {
									$aktivitetstid = $starttid.'-'.$sluttid;
								}
								
								//Om aktiviteten inte är passerad
								if ($slutdatum > $aktuellt_datum || ($slutdatum == $aktuellt_datum && $sluttid > $aktuell_tid)) {

									//Ta bort rum (eller annat efter ett kommatecken) i plats
									if (strpos($plats, ',') !== false) {
											$plats = substr($plats, 0, strpos($plats, ','));
									}

									//Lägg till ett kommatecken om det finns en plats och vi inte filtrerar på platsID
									if (($location_id == '') && (!empty($plats) && $plats !== '')) {
										$plats = ', '.$plats;
									}

									//Om det finns en beskrivning
									if (!empty($beskrivning)) {
										//Ingen HTML i beskrivningen
											//$beskrivning = str_ireplace(array('<B>', '</B>', '<BR /><BR />', '<BR />', '<BR>', '. . ', '.. '), array('', '', '', '. ', '. ', '. ', '. '), $beskrivning);
											/*$beskrivning = preg_replace('#<a.*?>.*?</a>#i', '', $beskrivning);*/
									}
									//Ingen beskrivning
									else {
										$beskrivning = '';
									}
									//Lägg till infotext med alla medverkande och ev. beskrivning
									$beskrivning = '<span class="infotext"> '.$medverkande.' '.$beskrivning.'</span>';

									//Bättre format på datumet
									$startdatum_visning = date('l j M',strtotime($startdatum));

									//Översätt veckodagar till svenska
									$startdatum_visning = str_ireplace(array('Monday','Tuesday','Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), array('Måndag','Tisdag','Onsdag','Torsdag','Fredag','Lördag','Söndag'),$startdatum_visning);

									//Översätt månader till svenska
									$startdatum_visning = str_ireplace(array('Jan','Feb','Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'), array('januari','februari','mars','april','maj','juni','juli', 'augusti', 'september', 'oktober', 'november', 'december'),$startdatum_visning);
									
									//Om det är dagens datum
									if ($startdatum == $aktuellt_datum) {
											$startdatum_visning = 'I dag '.strtolower($startdatum_visning);
									}
									//Om det är morgondagens datum
									elseif ($startdatum == $datum_imorgon) {
											$startdatum_visning = 'I morgon '.strtolower($startdatum_visning);
									}
									
									//Om det är den första händelsen detta datum
									if (!in_Array($startdatum, $datumArray)) {

										//Lägg till en datumrubrik
										$kalender .= '<div class="datum"><h2>'.$startdatum_visning.'</h2></div>'."\n";

										//Lägg in datumet i arrayen
										$datumArray[] = $startdatum;

									}
									//Lägg till händelsen i kalendern
									$line = '<div class="handelse"><p><span class="fet">'.$aktivitetstid.' '.$titel.'</span>';
									if ($location_id == '') {
										$line .= '<span class="plats">'.$plats.'</span>';
									}
									$line .= $beskrivning.'</p></div>'."\n";
									$kalender .= $line;

									$antal_tillagda++;
								}
							}
                        }
                }
                //Om det inte finns några aktiviteter
                else {
                    $kalender = 'Ett fel uppstod och vi kunde inte få fram några kalenderhändelser.';
				}
        }
?>
<html>
	<head>
			<meta charset="utf-8">

			<title>Kalender för <?php echo $organisation_namn; ?></title>

			<!-- fix för mobiler -->
			<meta name="viewport" content="width=device-width; initial-scale=1; maximum-scale=1">
			
			<!-- CSS -->
			<link rel="stylesheet" type="text/css" href="<?php echo $url_webbsida; ?>style.css" media="all" />
			<style>
				#header {
					<?php echo("background: ".$color.";"); ?>
				}
			</style>
	</head>
	<body<?php echo $skroll_status; ?>>

		<div id="header">
			<?php echo('<h1>'.$webbsida_rubrik.'</h1>'); ?>
		</div>

		<div id="wrapper"<?php echo $skroll_status; ?>>

			<?php echo $kalender; ?>

		</div>

		<?php  if ($skrolla == '0') { echo '<div id="gradient"></div>'; } ?>

	</body>
</html>
