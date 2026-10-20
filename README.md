# StinkyGuide

Spēlētāju veidota spēļu atklāšanas, atsauksmju un ceļvežu vietne, kas izstrādāta ar **Laravel 13**, **Vue 3**, **Vite** un **SQLite**.

StinkyGuide palīdz atrast spēles atbilstoši interesēm, sekot līdzi savam spēļu sarakstam un dalīties ar noderīgiem ceļvežiem un godīgām atsauksmēm. Vietnei ir tumšs, purva iedvesmots dizains ar vieglu humora devu.

## Iestatīšana un palaišana

Nepieciešams: PHP 8.3+ ar PDO SQLite paplašinājumu, Composer un Node.js 22.13+.

```powershell
composer install
npm install
php artisan key:generate
# .env failā iestatiet ADMIN_INITIAL_PASSWORD — unikālu paroli, kas ir vismaz 16 rakstzīmes gara.
php artisan migrate --seed
npm run dev
```

Atveriet [http://127.0.0.1:8000](http://127.0.0.1:8000). Komanda `npm run dev` vienlaikus palaiž Laravel serveri un Vite izstrādes serveri. Lai sagatavotu resursus produkcijas videi, palaidiet `npm run build`; lietotni izvietojiet ar sev piemērotu PHP/Laravel hostinga risinājumu.

## Datu bāze un satura imports

Laravel lietotne izmanto SQLite datu bāzi `database/database.sqlite`. Migrācijas izveido tabulas kontiem, spēlēm, ieteikumiem, spēlētāju spēļu sarakstiem, ceļvežiem, noderīguma balsojumiem, saglabātajiem ceļvežiem, komentāriem, atsauksmēm, sesijām, kešatmiņai un darbu rindai.

Pirmā `php artisan db:seed` palaišana importē esošās spēles, ceļvežus, noderīguma balsis, autoru vārdus, atsauksmes un to datumus no `data/stinkyguide.sqlite`, pēc tam papildina katalogu līdz 97 spēlēm. Katalogā ir šāvēji, lomu spēles, stratēģijas, sacīkšu, sporta, simulācijas un kooperatīvās spēles, kā arī citas kategorijas. Ja vecā datu bāze nav pieejama, tiek izmantots sākotnēji iekļautais spēļu paraugs.

Seederi var palaist atkārtoti — tas neveidos dublētu paraugsaturu. Palaidiet to pēc lietotnes atjaunināšanas, lai pievienotu jaunās kataloga spēles. Sākotnējā datu bāze netiek mainīta; pēc importa jauni konti un kopienas saturs tiek glabāts `database/database.sqlite`.

Lai saglabātu spēlētāju kontus un ierakstus, veidojiet `database/database.sqlite` rezerves kopijas. Datu bāzes un vides konfigurācijas faili ir izslēgti no Git.

## Administratora konts un datplūsmas pārraudzība

Pirms `php artisan db:seed` palaišanas `.env` failā iestatiet `ADMIN_EMAIL` (pēc noklusējuma `admin@admin.lv`) un unikālu `ADMIN_INITIAL_PASSWORD`, kas ir vismaz 16 rakstzīmes gara. Jaunā instalācijā seederis izveidos administratora kontu. Ja šāda e-pasta adrese jau ir reģistrēta, konts tiks paaugstināts par administratoru tikai tad, ja ir norādīta droša sākotnējā parole; esošā parole netiks mainīta. Glabājiet paroli `.env` failā vai izvietošanas noslēpumu glabātuvē — neiekļaujiet to Git.

Administratora navigācijā pieejama sadaļa **Admin**. Tajā var pārskatīt jaunākos ceļvežus, atsauksmes un komentārus, dzēst neatbilstošu kopienas saturu un skatīt pieprasījumu skaitu, aktīvos pieslēgtos kontus, biežāk izmantotos maršrutu paraugus un atbilžu statusus.

Datplūsmas pārraudzība saglabā tikai maršruta paraugu, HTTP metodi, statusu, ja lietotājs ir pieslēdzies — tā ID, kā arī laika zīmogus. Tā nesaglabā IP adreses, pilnus URL vai vaicājuma parametrus un pieprasījumu saturu. Laravel ikdienas plānotājs automātiski dzēš datplūsmas ierakstus, kas vecāki par 90 dienām. Produkcijas vidē nodrošiniet `php artisan schedule:run` izpildi katru minūti vai palaidiet `php artisan traffic:prune` manuāli.

## Ko var darīt spēlētāji

- Izveidot kontu, pieslēgties, mainīt spēlētāja vārdu un profila aprakstu, kā arī izrakstīties. Laravel droši jaukšo paroles un pārvalda sesiju sīkdatnes.
- Meklēt spēles, ceļvežus un atsauksmes; pārlūkot spēļu katalogu ar Steam attēliem un reālu spēlētāju vērtējumiem.
- Izvēlēties līdz piecām interesēm — piemēram, šāvējiem, sacīkšu, sporta, simulācijas, lomu, mājīgām, stratēģijas, piedzīvojumu vai neatkarīgajām spēlēm — un saņemt ieteikumus, kas sakārtoti pēc atbilstošajām birkām. Var pārlūkot visus ieteikumus vai meklēt visā katalogā.
- Pievienot spēles vēlmju sarakstam, atzīmēt tās kā spēlējamas, pabeigtas vai pamestas un pārvaldīt tās sadaļā **Manas spēles**.
- Publicēt un filtrēt spēļu atsauksmes, norādīt nospēlēto laiku un lasīt atsauksmes spēļu informācijas skatā. Publiskajā atsauksmju sarakstā tiek rādītas tikai reģistrētu spēlētāju iesniegtās atsauksmes; sākotnējie paraugi netiek uzdoti par īstu spēlētāju viedokļiem.
- Publicēt ceļvežus par jebkuru spēli — arī tādu, kas nav katalogā — un izvēlēties kategoriju, piemēram, pamācību, aprīkojuma ieteikumus, bosu taktiku, kolekcionējamos priekšmetus, sasniegumus vai padomus. Var arī komentēt, saglabāt ceļvežus vēlākam laikam un balsot par to noderīgumu.

Lai palaistu Laravel funkcionalitātes testus, izmantojiet `php artisan test`.

Spēļu vāku attēli tiek ielādēti no publiskā Steam attēlu CDN, tāpēc to skatīšanai nepieciešams interneta savienojums.
