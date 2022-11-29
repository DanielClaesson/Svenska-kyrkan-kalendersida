# Webbsida med kommande kalenderaktiviteter för valfi församling/pastorat inom Svenska kyrkan

Skapa en kalender som hämtar kommande aktiviteter för en församling/ett pastorat inom Svenska kyrkan. All information hämtas automatiskt från kalender på svenskakyrkan.se via Svenska kyrkans API.

## Använd kalendern på egen server
Har du tillgång till en PHP-server så kan du ladda upp filerna där, det är allt som behövs för att komma igång med en egen kalender.

### Ställ in kalendern för att visa rätt aktiviteter
Du behöver en API-nyckel för att hämta kalenderaktivteter. Det kan du skaffa på api.svenskakyrkan.se. Klistra in API-nyckeln på markerat ställe i filen index.php. Där fyller du även i ID-numret för den församling/pastorat/organisation inom Svenska kyrkan som du vill lista kalenderhändelser för.

Du kan slutligen ändra rubriken på webbsidan genom att fylla i valfri text vid "$webbsida_rubrik".

## Hitta organisations-ID
Du kanske inte har koll på ID-numret för organisationen? Ett sätt som jag har använt för att hitta vårt ID var att gå in på Botkyrka församlings hemsida (på svenskakyrkan.se) och klicka på kalender-knappen nedanför den stora sökrutan uppe till höger. Sedan tittar jag i adressfältet i webbläsaren och kopierar siffrorna som står efter "&orgId=".

## Lägg in kalendern i Playipp
Många församlingar har digitala skärmar med mjukvara från Playipp. Kalendern kan enkelt läggas in på dessa skärmar.

1. Gå till som vanligt först Publicera och välj den skärm du vill använda. Klicka på den modul/sektion av skärmen där du vill ha kalendern och välj sedan Lägg till.
2. Under Ny media & fil väljer du Webblänk. Klistra in kalenderadressen (inkl. ert ID-nummer) i det översta textfältet. Ändra därefter gärna från Ladda om länken vid varje visning till Ange tidsintervall för omladdning av länken och låt det stå 60 minuter i rutan intill. Tryck sedan på Nästa steg.  
3. Ge nu kalendern ett namn och välj hur länge den ska visas. Välj också en mapp där den ska sparas (i Playipp). Tryck sedan en gång till på Nästa steg.
4. Till sist väljer du en schemaläggning för när kalendern ska visas. Avsluta med att trycka på Publicera.
