# Webbsida med kommande kalenderaktiviteter för valfi församling/pastorat inom Svenska kyrkan

Skapa en kalender som hämtar kommande aktiviteter för en församling/ett pastorat inom Svenska kyrkan. All information hämtas automatiskt från kalender på svenskakyrkan.se via Svenska kyrkans API.

## Använd kalendern på egen server
Har du tillgång till en PHP-server så kan du ladda upp filerna där, det är allt som behövs för att komma igång med en egen kalender.

### Ställa in kalendern för att visa rätt aktiviteter

#### API-nyckel
Du behöver en API-nyckel för att hämta kalenderaktivteter. Det kan du skaffa på api.svenskakyrkan.se. 

API-nyckeln kan du ange på två sätt.

Antingen genom att du placerar en fil som heter ``api`` bredvid filen index.php. I filen klistrar du in din api-nyckel. Enklast är att du gör en textfil, klistrar in nyckeln, sparar och sedan tar bort filändelsen (.txt).

Det andra sättet är att skicka med api-nyckeln som parametern ``api=`` till länken till sidan, se exempel längre ned.

### Organisations-ID
Du kanske inte har koll på ID-numret för organisationen? Ett sätt som jag har använt för att hitta vårt ID var att gå in på Botkyrka församlings hemsida (på svenskakyrkan.se) och klicka på kalender-knappen nedanför den stora sökrutan uppe till höger. Sedan tittar jag i adressfältet i webbläsaren och kopierar siffrorna som står efter "&orgId=".

OrganisationsID:t skickar du med som parametern ``orgID=`` till länken, se exemplen längre ned.

### Plats-ID
Aktiviteterna kan också filtreras baserat på vilken lokal de äger rum i, detta åstadkoms genom att skicka med platsens ID som återfinns i platsadministrationen.

**Exempel på IDn:**
> Härnösands domkyrka = 5dab016f-18f3-4973-92d8-69779653a1ef
> Säbrå kyrka = 26a876d9-3cf6-4a8b-8e11-7c78eaaef4d9

ID:t skickar du med i länken med parametern ``locationID=``

När du filterar aktiviteten baserat på en plats så är det onödigt att platsen skrivs ut efter varje aktivitetstitel. Platsen läggs istället in i "headern" längst till höger. Namnet hämtas från platsadministrationen..

### Rubrik och \<title>
Rubriken som visas är som standard Svenska kyrkan, men detta kan du själv ställa om med parametern ``header=``, dit du t.ex. kan fylla i ert enhets namn , ex. ``Svenska kyrkan Härnösand``.

Själva titeln i webbläsaren visar ``Kalender för [Svenska kyrkan]`` som standard, denna kan du också styra via parametern ``orgName=``.

### Färg
Som standard används den mörkröda färgen från Svenska kyrkans grafiska profil, men du kan ändra via parametern ``color=`` till andra färger enligt listan nedan:

    'kyrkröd' => '#cd0014',
    'kyrkblå' => '#006fb9',
    'kyrkgrön' => '#6b9531',
    'kyrklila' => '#522583',
    'kyrkgul' => '#f59c00'

## Exempel på länkar

### Visa kalendern för alla aktiviteter i Härnösands pastorat
``https://kalender.minserver.se/?orgID=20271``

### Visa kalendern för aktiviteter i Härnösands domkyrka

``https://kalender.minserver.se/?orgID=20271&locationID=5dab016f-18f3-4973-92d8-69779653a1ef``

### Visa kalendern för aktiviteter i Härnösands pastorat samt rubriken Svenska kyrkan Härnösand och titeln i webbläsaren "Härnösands pastorat"

``https://kalender.minserver.se/?orgID=20271&header=Svenska kyrkan Härnösand&orgName=Härnösands pastorat``

Observera att webbläsaren antagligen lägger till ``%20`` istället för mellanslag, men du kan behöva göra det själv.

### Visa kalendern för aktiviteter i Säbrå kyrka med kyrklila färger

``https://kalender.minserver.se/?orgID=20271&locationID=26a876d9-3cf6-4a8b-8e11-7c78eaaef4d9&color=kyrklila``


## Lägg in kalendern i Playipp
Många församlingar har digitala skärmar med mjukvara från Playipp. Kalendern kan enkelt läggas in på dessa skärmar.

1. Gå till som vanligt först Publicera och välj den skärm du vill använda. Klicka på den modul/sektion av skärmen där du vill ha kalendern och välj sedan Lägg till.
2. Under Ny media & fil väljer du Webblänk. Klistra in kalenderadressen (inkl. ert ID-nummer) i det översta textfältet. Ändra därefter gärna från Ladda om länken vid varje visning till Ange tidsintervall för omladdning av länken och låt det stå 60 minuter i rutan intill. Tryck sedan på Nästa steg.  
3. Ge nu kalendern ett namn och välj hur länge den ska visas. Välj också en mapp där den ska sparas (i Playipp). Tryck sedan en gång till på Nästa steg.
4. Till sist väljer du en schemaläggning för när kalendern ska visas. Avsluta med att trycka på Publicera.
