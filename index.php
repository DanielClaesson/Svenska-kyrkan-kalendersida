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

        $organisations_id = '20271'; //Default organisations-ID
        $organisation_namn = 'Svenska kyrkan'; //Default organisation
        $webbsida_rubrik = 'Svenska kyrkan'; //Default rubrik
        $max_handelser = '50'; //Max antal händelser att visa i kalendern
	$skrolla = '0'; //Skroll är inaktiverat per default
	$skroll_status = ''; //Tom som standard

        $location_id = '';
        $location_name = '';
        
        $calendarSubGroup = ''; //Händelsetyp ID

        $sluttider = '1';

        // Färger hämtade från Svenska kyrkans grafiska profil
        // https://www.svenskakyrkan.se/grafiskprofil
        $colors = [
                'mörkkyrkröd' => '#91160f',
                'mörkkyrklila' => '#522583',
                'mörkkyrkgrön' => '#4a6a21',
                'mörkkyrkgul' => '#f59c00',
                'mörkkyrkblå' => '#005884',

                'kyrkröd' => '#d51317',
                'kyrklila' => '#a71680',
                'kyrkgrön' => '#6b9531',
                'kyrkgul' => '#fdc300',
                'kyrkblå' => '#006fb9',

                'ljuskyrkröd' => '#ed6d91',
                'ljuskyrklila' => '#bb71ac',
                'ljuskyrkgrön' => '#afca0b',
                'ljuskyrkgul' => '#ffdf43',
                'ljuskyrkblå' => '#63c3d1',

                'ljusastkyrkröd' => '#f5b3bd',
                'ljusastkyrklila' => '#d1bcdc',
                'ljusastkyrkgrön' => '#dddf4b',
                'ljusastkyrkgul' => '#ffe37e',
                'ljusastkyrkblå' => '#bee2e9'
                
        ];
        $color = $colors['kyrkröd'];

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

        //Möjlighet att filtrera aktiviteter per locationID
        if (isset($_GET['locationID']) && $_GET['locationID'] !== '') {
                $location_id = $_GET['locationID'];
        }

        //Filtrering via namn på Händelsetyp
        // 101 = Gudstjänst & mässa
        // 102 = ?
        // 103 = Kropp & själ
        // 104 = Barnverksamhet
        // 105 = Musik & kör
        // 106 = ?
        // 107 = ?
        // 108 = Studier & samtal
        // 110 = Ungdomsverksamhet
        // 111 = Drop-in
        if (isset($_GET['csg']) && $_GET['csg'] !== '') {
                $calendarSubGroup = explode(',', $_GET['csg']); //Hantera flera Händelsetyper för or-filtrering i api:t
        }
		

        //Möjlighet att sätta Title via parameter ?orgName=Svenska kyrkan Härnösand
        if (isset($_GET['orgName']) && $_GET['orgName'] !== '') {
                $organisation_namn = $_GET['orgName'];
        }

        //Möjlighet att sätta kalenderrubriken via parameter ?header=Svenska kyrkan Härnösand
        if (isset($_GET['header']) && $_GET['header'] !== '') {
                $webbsida_rubrik = $_GET['header'];
        }

        if (isset($_GET['color']) && $_GET['color'] !== '')
        {
                try {
                        $color = $colors[$_GET['color']];
                } catch (Exception $e) {
                        $color = $colors['kyrkröd'];
                }
        }
		
        //Möjlighet att sätta eget antal händelser att ladda
        if (isset($_GET['antal']) && is_numeric($_GET['antal']) && $_GET['antal'] > '0' && $_GET['antal'] < '100') {
            $max_handelser = $_GET['antal'];
        }
		
        //Möjlighet att aktivera skroll på webbsidan
	if (isset($_GET['skrolla'])) {
            $skrolla = '1';
			$skroll_status = ' class="skrolla"';
        }
		
        //Möjlighet att ta bort alla sluttider
        if (isset($_GET['sluttider']) && $_GET['sluttider'] == 'nej') {
            $sluttider = '0';
        }
		
		//echo '<!-- Sluttider status: '.$sluttider.' -->';
		
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
        $url = 'https://api.svenskakyrkan.se/calendarsearch/v4/SearchByParent?apikey='.$api_nyckel.'&orgId='.$organisations_id.'&$orderby=StartTime';
        if ($location_id !== '') { //Låt API:t filtrera location_id om vi filtrerar på ett sådant.
                // %20 = space
                // %27 = '
                $url .= '&$filter=Place/Id%20eq%20%27'.$location_id.'%27';

                if ($calendarSubGroup != '') { //Vi filtrerar på både plats och händelsetyp.
                        $url .= '%20and%20CalendarSubGroups/any(n:n/Id%20eq%20';

                        for($i = 0; $i < count($calendarSubGroup); $i++) {
                                if($i > 0) {
                                        $url .= '%20or%20n/Id%20eq%20';
                                }
                                $url .= $calendarSubGroup[$i];
                        }
                        $url .= ')';
                }
        }
        elseif ($location_id == '' && $calendarSubGroup != ''){ //Vi filtrerar inte på plats, men på händelsetyp.
                $url .= '&$filter=CalendarSubGroups/any(n:n/Id%20eq%20';

                for($i = 0; $i < count($calendarSubGroup); $i++) {
                        if($i > 0) {
                                $url .= '%20or%20n/Id%20eq%20';
                        }
                        $url .= $calendarSubGroup[$i];
                }
                $url .= ')';
        }

        //echo $url;

        $kalender_api_lank = file_get_contents($url);

        //Om API-länken inte fungerar
        if($kalender_api_lank === FALSE) {
                $kalender = 'Ett API-anrop fungerade inte, vi beklagar.';
        }
        else {
                //Gör om JSON till en array
                $svk_kalender_array = json_decode ($kalender_api_lank, true);

                //echo '<pre>'; print_r($svk_kalender_array); echo '</pre>';

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

                                if ($location_id !== '') {
                                        $location_name = $plats;
                                }

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

										
                                        //Om det finns en sluttid och sluttider ska visas
                                        if (strpos($starttid, '-') !== false) {
                                                $starttid = str_replace(array(' '), array(''), $starttid);
												$starttid_ratt = strtok($starttid, '-');
                                                $sluttid = str_replace(array($starttid_ratt.'-'), array(''), $starttid);
                                                $sluta_visas = $sluttid;
                                                $sluttid = '-'.$sluttid;
                                                $starttid = $starttid_ratt;
												//Om sluttider inte ska visas
												if ($sluttider == '0') {
													$sluttid = '';
												}
												//echo '<!-- Sluttid: '.$sluttid.' -->';
                                        }
                                        else {
                                                $sluttid = '';
                                                $sluta_visas = date($starttid, strtotime('+2 hours'));
												//echo '<!-- Ingen sluttid gäller, starttiden är '.$starttid.' -->';
                                        }

                                        //Om aktiviteten inte är passerad
                                        if ($slutdatum > $aktuellt_datum || ($slutdatum == $aktuellt_datum && $sluta_visas >= $aktuell_tid)) {

                                                //Ta bort rum (eller annat efter ett kommatecken) i plats
                                                if (strpos($plats, ',') !== false) {
                                                        $plats = substr($plats, 0, strpos($plats, ','));
                                                }

                                                //Lägg till ett kommatecken om det finns en plats och att vi inte filtrerar på platsID
                                                if (($location_id == '') && (!empty($plats) && $plats !== '')) {
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
                                                $line = '<div class="handelse"><p><span class="fet">'.$starttid.$sluttid.' '.$titel.'</span>';
                                                if ($location_id == '') {
                                                        $line .= $plats;
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
                        $kalender = 'Ett fel uppstod och kalendern kunde inte laddas. Vi beklagar detta.';
                }
        }
?>
<html>
	<head>

			<meta charset="utf-8">

			<title>Kalender för <?php echo $organisation_namn; ?></title>

			<link rel="stylesheet" type="text/css" href="style.css" media="all" />

			<!-- fix för mobiler -->
		<meta name="viewport" content="width=device-width; initial-scale=1; maximum-scale=1">
                <style>
					#header {
							<?php 
							echo("background: ".$color.";");
							?>
					}

					.datum {
							<?php 
							echo("color: ".$color.";");
							?>
					}
                </style>
	</head>
	<body<?php echo $skroll_status; ?>>

			<div id="header">
					<?php 
                                        
					echo('<h1>'.$webbsida_rubrik.'</h1>');
					if ($location_id !== '') {
							echo('<h1 class="location">'.$location_name.'</h1>');
					}

					?>
			</div>

			<div id="wrapper"<?php echo $skroll_status; ?>>

				<?php echo $kalender; ?>

			</div>

			<?php  if ($skrolla == '0') { echo '<div id="gradient"></div>'; } ?>

	</body>
</html>