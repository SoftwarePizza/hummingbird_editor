<?php
declare(strict_types=1);
/**
 * Hummingbird Editor — progi rabatowe z kodami („kupuj więcej, płać mniej").
 *
 * Sklep ma zwykłe reguły koszyka z kodem (np. `500` = -5% od 500 zł, `1000` =
 * -10% od 1000 zł). Ta klasa niczego nie nalicza — czyta te reguły po kodach
 * wpisanych w panelu i odtwarza dla bieżącego koszyka to, co za chwilę zrobi
 * `CartRule::checkValidity()`: ile brakuje do progu, który próg jest już
 * osiągnięty, czy kod da się dołożyć (grupy klienta, inne kupony, przecenione
 * produkty). Na tej podstawie motyw pokazuje pasek postępu w koszyku, w
 * podglądzie/modalu i na karcie produktu, a przycisk „Aktywuj” dodaje kod za
 * klienta (controllers/front/tiers.php).
 *
 * Źródłem prawdy zostaje reguła koszyka: próg, procent, ważność, „bez
 * przecenionych” — wszystko z BO → Reguły koszyka. Panel edytora trzyma tylko
 * listę kodów i włączniki miejsc.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorDiscountTiers
{
    public const CONF_ENABLED       = 'HBE_TIERS_ENABLED';
    public const CONF_CODES         = 'HBE_TIERS_CODES';
    public const CONF_SHOW_CART     = 'HBE_TIERS_SHOW_CART';
    public const CONF_SHOW_PRODUCT  = 'HBE_TIERS_SHOW_PRODUCT';
    public const CONF_HOME_ENABLED  = 'HBE_TIERS_HOME_ENABLED';
    public const CONF_HOME_TITLE    = 'HBE_TIERS_HOME_TITLE';
    public const CONF_HOME_TEXT     = 'HBE_TIERS_HOME_TEXT';
    public const CONF_HOME_CTA_TEXT = 'HBE_TIERS_HOME_CTA_TEXT';
    public const CONF_HOME_CTA_URL  = 'HBE_TIERS_HOME_CTA_URL';

    /** Konteksty renderowania paska. */
    public const CTX_CART    = 'cart';
    public const CTX_PREVIEW = 'preview';
    public const CTX_PRODUCT = 'product';

    /**
     * Teksty w językach sklepu. Klucz 'en' jest zarazem kluczem tłumaczenia
     * modułu (domena Modules.Hummingbirdeditor.Shop) — da się nadpisać w BO →
     * Tłumaczenia; reszta to fallback, żeby sklep wielojęzyczny nie został z
     * angielskim zdaniem, dopóki nikt nic nie przetłumaczył.
     */
    public const LABELS = [
        'en' => [
            'title'         => 'Buy more, pay less',
            'tier'          => '%percent% off from %amount%',
            'tier_short'    => '%percent% from %amount%',
            'code'          => 'code %code%',
            'missing'       => 'Add %amount% more to get %percent% off',
            'in_cart'       => 'Your cart: %amount%',
            'reached'       => 'You qualify for %percent% off!',
            'savings'       => 'You save about %amount%',
            'applied'       => '%percent% discount active — you save %amount%',
            'applied_plain' => '%percent% discount active',
            'next'          => 'Add %amount% more and the discount rises to %percent%',
            'upgrade'       => 'You now qualify for %percent% off!',
            'cta_apply'     => 'Activate %percent% off',
            'cta_upgrade'   => 'Switch to %percent% off',
            'top'           => 'That is our highest discount — enjoy!',
            'exclude'       => 'The discount does not apply to products already on sale.',
            'blocked'       => 'Cannot be combined with the voucher %name% in your cart.',
            'only_special'  => 'Your cart holds only discounted products — the tier discount applies to regular-price products.',
            'one_click'     => 'Activate the code with one click in your cart.',
            'success'       => 'Discount %percent% activated (code %code%).',
            'home_title'    => 'Buy more, pay less',
            'home_text'     => 'The bigger the order, the lower the price. Activate the code with one click in your cart.',
        ],
        'pl' => [
            'title'         => 'Kupuj więcej, płać mniej',
            'tier'          => 'Rabat %percent% od %amount%',
            'tier_short'    => '%percent% od %amount%',
            'code'          => 'kod %code%',
            'missing'       => 'Dołóż jeszcze %amount%, a dostaniesz rabat %percent%',
            'in_cart'       => 'W koszyku masz %amount%',
            'reached'       => 'Masz rabat %percent%!',
            'savings'       => 'Oszczędzasz ok. %amount%',
            'applied'       => 'Rabat %percent% aktywny — oszczędzasz %amount%',
            'applied_plain' => 'Rabat %percent% aktywny',
            'next'          => 'Dołóż jeszcze %amount%, a rabat wzrośnie do %percent%',
            'upgrade'       => 'Masz już prawo do rabatu %percent%!',
            'cta_apply'     => 'Aktywuj rabat %percent%',
            'cta_upgrade'   => 'Zamień na rabat %percent%',
            'top'           => 'To nasz najwyższy rabat — miłych zakupów!',
            'exclude'       => 'Rabat nie obejmuje produktów już przecenionych.',
            'blocked'       => 'Nie łączy się z kuponem %name%, który jest w koszyku.',
            'only_special'  => 'W koszyku są tylko produkty przecenione — rabat progowy obejmuje produkty w cenie regularnej.',
            'one_click'     => 'Kod aktywujesz jednym kliknięciem w koszyku.',
            'success'       => 'Rabat %percent% aktywny (kod %code%).',
            'home_title'    => 'Kupuj więcej, płać mniej',
            'home_text'     => 'Im większe zamówienie, tym niższa cena. Kod aktywujesz jednym kliknięciem w koszyku.',
        ],
        'de' => [
            'title'         => 'Mehr kaufen, weniger zahlen',
            'tier'          => '%percent% Rabatt ab %amount%',
            'tier_short'    => '%percent% ab %amount%',
            'code'          => 'Code %code%',
            'missing'       => 'Noch %amount% und Sie erhalten %percent% Rabatt',
            'in_cart'       => 'Ihr Warenkorb: %amount%',
            'reached'       => 'Sie erhalten %percent% Rabatt!',
            'savings'       => 'Sie sparen ca. %amount%',
            'applied'       => '%percent% Rabatt aktiv — Sie sparen %amount%',
            'applied_plain' => '%percent% Rabatt aktiv',
            'next'          => 'Noch %amount% und der Rabatt steigt auf %percent%',
            'upgrade'       => 'Ihnen stehen jetzt %percent% Rabatt zu!',
            'cta_apply'     => '%percent% Rabatt aktivieren',
            'cta_upgrade'   => 'Auf %percent% Rabatt wechseln',
            'top'           => 'Das ist unser höchster Rabatt — viel Freude beim Einkaufen!',
            'exclude'       => 'Der Rabatt gilt nicht für bereits reduzierte Produkte.',
            'blocked'       => 'Nicht kombinierbar mit dem Gutschein %name% in Ihrem Warenkorb.',
            'only_special'  => 'Ihr Warenkorb enthält nur reduzierte Produkte — der Staffelrabatt gilt für Produkte zum regulären Preis.',
            'one_click'     => 'Den Code aktivieren Sie mit einem Klick im Warenkorb.',
            'success'       => 'Rabatt %percent% aktiviert (Code %code%).',
            'home_title'    => 'Mehr kaufen, weniger zahlen',
            'home_text'     => 'Je größer die Bestellung, desto niedriger der Preis. Den Code aktivieren Sie mit einem Klick im Warenkorb.',
        ],
        'es' => [
            'title'         => 'Compra más, paga menos',
            'tier'          => '%percent% de descuento desde %amount%',
            'tier_short'    => '%percent% desde %amount%',
            'code'          => 'código %code%',
            'missing'       => 'Añade %amount% más y obtén un %percent% de descuento',
            'in_cart'       => 'Tu cesta: %amount%',
            'reached'       => '¡Tienes un %percent% de descuento!',
            'savings'       => 'Ahorras aprox. %amount%',
            'applied'       => 'Descuento del %percent% activo — ahorras %amount%',
            'applied_plain' => 'Descuento del %percent% activo',
            'next'          => 'Añade %amount% más y el descuento sube al %percent%',
            'upgrade'       => '¡Ya tienes derecho a un %percent% de descuento!',
            'cta_apply'     => 'Activar %percent% de descuento',
            'cta_upgrade'   => 'Cambiar al %percent% de descuento',
            'top'           => 'Es nuestro descuento máximo — ¡disfruta la compra!',
            'exclude'       => 'El descuento no se aplica a productos ya rebajados.',
            'blocked'       => 'No se combina con el cupón %name% que tienes en la cesta.',
            'only_special'  => 'Tu cesta solo contiene productos rebajados — el descuento por tramos se aplica a productos a precio normal.',
            'one_click'     => 'Activa el código con un clic en la cesta.',
            'success'       => 'Descuento del %percent% activado (código %code%).',
            'home_title'    => 'Compra más, paga menos',
            'home_text'     => 'Cuanto mayor es el pedido, menor es el precio. Activa el código con un clic en la cesta.',
        ],
        'fr' => [
            'title'         => 'Achetez plus, payez moins',
            'tier'          => '%percent% de remise dès %amount%',
            'tier_short'    => '%percent% dès %amount%',
            'code'          => 'code %code%',
            'missing'       => 'Ajoutez encore %amount% pour obtenir %percent% de remise',
            'in_cart'       => 'Votre panier : %amount%',
            'reached'       => 'Vous avez droit à %percent% de remise !',
            'savings'       => 'Vous économisez env. %amount%',
            'applied'       => 'Remise de %percent% active — vous économisez %amount%',
            'applied_plain' => 'Remise de %percent% active',
            'next'          => 'Ajoutez encore %amount% et la remise passe à %percent%',
            'upgrade'       => 'Vous avez maintenant droit à %percent% de remise !',
            'cta_apply'     => 'Activer la remise de %percent%',
            'cta_upgrade'   => 'Passer à la remise de %percent%',
            'top'           => 'C’est notre remise maximale — bon shopping !',
            'exclude'       => 'La remise ne s’applique pas aux produits déjà soldés.',
            'blocked'       => 'Non cumulable avec le bon %name% présent dans votre panier.',
            'only_special'  => 'Votre panier ne contient que des produits soldés — la remise par paliers s’applique aux produits au prix normal.',
            'one_click'     => 'Activez le code en un clic dans le panier.',
            'success'       => 'Remise de %percent% activée (code %code%).',
            'home_title'    => 'Achetez plus, payez moins',
            'home_text'     => 'Plus la commande est grande, plus le prix baisse. Activez le code en un clic dans le panier.',
        ],
        'it' => [
            'title'         => 'Compra di più, paga di meno',
            'tier'          => '%percent% di sconto da %amount%',
            'tier_short'    => '%percent% da %amount%',
            'code'          => 'codice %code%',
            'missing'       => 'Aggiungi ancora %amount% e ottieni il %percent% di sconto',
            'in_cart'       => 'Il tuo carrello: %amount%',
            'reached'       => 'Hai diritto al %percent% di sconto!',
            'savings'       => 'Risparmi circa %amount%',
            'applied'       => 'Sconto del %percent% attivo — risparmi %amount%',
            'applied_plain' => 'Sconto del %percent% attivo',
            'next'          => 'Aggiungi ancora %amount% e lo sconto sale al %percent%',
            'upgrade'       => 'Ora hai diritto al %percent% di sconto!',
            'cta_apply'     => 'Attiva lo sconto del %percent%',
            'cta_upgrade'   => 'Passa allo sconto del %percent%',
            'top'           => 'È il nostro sconto massimo — buon shopping!',
            'exclude'       => 'Lo sconto non vale per i prodotti già scontati.',
            'blocked'       => 'Non cumulabile con il buono %name% presente nel carrello.',
            'only_special'  => 'Il carrello contiene solo prodotti scontati — lo sconto a soglie vale per i prodotti a prezzo pieno.',
            'one_click'     => 'Attiva il codice con un clic nel carrello.',
            'success'       => 'Sconto del %percent% attivato (codice %code%).',
            'home_title'    => 'Compra di più, paga di meno',
            'home_text'     => 'Più grande è l’ordine, più basso è il prezzo. Attiva il codice con un clic nel carrello.',
        ],
        'nl' => [
            'title'         => 'Meer kopen, minder betalen',
            'tier'          => '%percent% korting vanaf %amount%',
            'tier_short'    => '%percent% vanaf %amount%',
            'code'          => 'code %code%',
            'missing'       => 'Voeg nog %amount% toe en krijg %percent% korting',
            'in_cart'       => 'Je winkelwagen: %amount%',
            'reached'       => 'Je krijgt %percent% korting!',
            'savings'       => 'Je bespaart ca. %amount%',
            'applied'       => '%percent% korting actief — je bespaart %amount%',
            'applied_plain' => '%percent% korting actief',
            'next'          => 'Voeg nog %amount% toe en de korting stijgt naar %percent%',
            'upgrade'       => 'Je hebt nu recht op %percent% korting!',
            'cta_apply'     => '%percent% korting activeren',
            'cta_upgrade'   => 'Wisselen naar %percent% korting',
            'top'           => 'Dit is onze hoogste korting — veel winkelplezier!',
            'exclude'       => 'De korting geldt niet voor producten die al afgeprijsd zijn.',
            'blocked'       => 'Niet te combineren met de kortingsbon %name% in je winkelwagen.',
            'only_special'  => 'Je winkelwagen bevat alleen afgeprijsde producten — de staffelkorting geldt voor producten met de normale prijs.',
            'one_click'     => 'Activeer de code met één klik in je winkelwagen.',
            'success'       => 'Korting van %percent% geactiveerd (code %code%).',
            'home_title'    => 'Meer kopen, minder betalen',
            'home_text'     => 'Hoe groter de bestelling, hoe lager de prijs. Activeer de code met één klik in je winkelwagen.',
        ],
        'cs' => [
            'title'         => 'Nakupte více, plaťte méně',
            'tier'          => 'Sleva %percent% od %amount%',
            'tier_short'    => '%percent% od %amount%',
            'code'          => 'kód %code%',
            'missing'       => 'Přidejte ještě %amount% a získáte slevu %percent%',
            'in_cart'       => 'Váš košík: %amount%',
            'reached'       => 'Máte nárok na slevu %percent%!',
            'savings'       => 'Ušetříte cca %amount%',
            'applied'       => 'Sleva %percent% je aktivní — ušetříte %amount%',
            'applied_plain' => 'Sleva %percent% je aktivní',
            'next'          => 'Přidejte ještě %amount% a sleva vzroste na %percent%',
            'upgrade'       => 'Nyní máte nárok na slevu %percent%!',
            'cta_apply'     => 'Aktivovat slevu %percent%',
            'cta_upgrade'   => 'Přejít na slevu %percent%',
            'top'           => 'To je naše nejvyšší sleva — příjemné nakupování!',
            'exclude'       => 'Sleva se nevztahuje na již zlevněné produkty.',
            'blocked'       => 'Nelze kombinovat s kupónem %name%, který máte v košíku.',
            'only_special'  => 'V košíku jsou jen zlevněné produkty — množstevní sleva platí pro produkty za běžnou cenu.',
            'one_click'     => 'Kód aktivujete jedním kliknutím v košíku.',
            'success'       => 'Sleva %percent% aktivována (kód %code%).',
            'home_title'    => 'Nakupte více, plaťte méně',
            'home_text'     => 'Čím větší objednávka, tím nižší cena. Kód aktivujete jedním kliknutím v košíku.',
        ],
        'da' => [
            'title'         => 'Køb mere, betal mindre',
            'tier'          => '%percent% rabat fra %amount%',
            'tier_short'    => '%percent% fra %amount%',
            'code'          => 'kode %code%',
            'missing'       => 'Tilføj %amount% mere og få %percent% rabat',
            'in_cart'       => 'Din kurv: %amount%',
            'reached'       => 'Du får %percent% rabat!',
            'savings'       => 'Du sparer ca. %amount%',
            'applied'       => '%percent% rabat aktiv — du sparer %amount%',
            'applied_plain' => '%percent% rabat aktiv',
            'next'          => 'Tilføj %amount% mere, og rabatten stiger til %percent%',
            'upgrade'       => 'Du har nu ret til %percent% rabat!',
            'cta_apply'     => 'Aktivér %percent% rabat',
            'cta_upgrade'   => 'Skift til %percent% rabat',
            'top'           => 'Det er vores højeste rabat — god fornøjelse!',
            'exclude'       => 'Rabatten gælder ikke for varer, der allerede er nedsat.',
            'blocked'       => 'Kan ikke kombineres med rabatkoden %name% i din kurv.',
            'only_special'  => 'Din kurv indeholder kun nedsatte varer — trinrabatten gælder varer til normalpris.',
            'one_click'     => 'Aktivér koden med ét klik i kurven.',
            'success'       => '%percent% rabat aktiveret (kode %code%).',
            'home_title'    => 'Køb mere, betal mindre',
            'home_text'     => 'Jo større ordre, jo lavere pris. Aktivér koden med ét klik i kurven.',
        ],
        'hu' => [
            'title'         => 'Vásárolj többet, fizess kevesebbet',
            'tier'          => '%percent% kedvezmény %amount% felett',
            'tier_short'    => '%percent% %amount% felett',
            'code'          => 'kód: %code%',
            'missing'       => 'Tegyél még %amount% értékben a kosárba, és %percent% kedvezményt kapsz',
            'in_cart'       => 'Kosarad: %amount%',
            'reached'       => 'Jár neked a %percent% kedvezmény!',
            'savings'       => 'Kb. %amount% megtakarítás',
            'applied'       => '%percent% kedvezmény aktív — megtakarításod %amount%',
            'applied_plain' => '%percent% kedvezmény aktív',
            'next'          => 'Tegyél még %amount% értékben a kosárba, és a kedvezmény %percent%-ra nő',
            'upgrade'       => 'Már jár neked a %percent% kedvezmény!',
            'cta_apply'     => '%percent% kedvezmény aktiválása',
            'cta_upgrade'   => 'Váltás %percent% kedvezményre',
            'top'           => 'Ez a legmagasabb kedvezményünk — jó vásárlást!',
            'exclude'       => 'A kedvezmény nem vonatkozik a már leárazott termékekre.',
            'blocked'       => 'Nem vonható össze a kosaradban lévő %name% kuponnal.',
            'only_special'  => 'A kosaradban csak leárazott termékek vannak — a sávos kedvezmény a teljes árú termékekre vonatkozik.',
            'one_click'     => 'A kódot egy kattintással aktiválhatod a kosárban.',
            'success'       => '%percent% kedvezmény aktiválva (kód: %code%).',
            'home_title'    => 'Vásárolj többet, fizess kevesebbet',
            'home_text'     => 'Minél nagyobb a rendelés, annál alacsonyabb az ár. A kódot egy kattintással aktiválhatod a kosárban.',
        ],
        'lt' => [
            'title'         => 'Pirkite daugiau, mokėkite mažiau',
            'tier'          => '%percent% nuolaida nuo %amount%',
            'tier_short'    => '%percent% nuo %amount%',
            'code'          => 'kodas %code%',
            'missing'       => 'Pridėkite dar už %amount% ir gaukite %percent% nuolaidą',
            'in_cart'       => 'Jūsų krepšelis: %amount%',
            'reached'       => 'Jums priklauso %percent% nuolaida!',
            'savings'       => 'Sutaupote apie %amount%',
            'applied'       => '%percent% nuolaida aktyvi — sutaupote %amount%',
            'applied_plain' => '%percent% nuolaida aktyvi',
            'next'          => 'Pridėkite dar už %amount% ir nuolaida padidės iki %percent%',
            'upgrade'       => 'Jums jau priklauso %percent% nuolaida!',
            'cta_apply'     => 'Aktyvuoti %percent% nuolaidą',
            'cta_upgrade'   => 'Pakeisti į %percent% nuolaidą',
            'top'           => 'Tai didžiausia mūsų nuolaida — gero apsipirkimo!',
            'exclude'       => 'Nuolaida netaikoma jau nukainotoms prekėms.',
            'blocked'       => 'Nesuderinama su krepšelyje esančiu kuponu %name%.',
            'only_special'  => 'Krepšelyje yra tik nukainotos prekės — pakopinė nuolaida taikoma prekėms įprasta kaina.',
            'one_click'     => 'Kodą aktyvuosite vienu paspaudimu krepšelyje.',
            'success'       => '%percent% nuolaida aktyvuota (kodas %code%).',
            'home_title'    => 'Pirkite daugiau, mokėkite mažiau',
            'home_text'     => 'Kuo didesnis užsakymas, tuo mažesnė kaina. Kodą aktyvuosite vienu paspaudimu krepšelyje.',
        ],
        'ro' => [
            'title'         => 'Cumperi mai mult, plătești mai puțin',
            'tier'          => 'Reducere %percent% de la %amount%',
            'tier_short'    => '%percent% de la %amount%',
            'code'          => 'cod %code%',
            'missing'       => 'Mai adaugă %amount% și primești %percent% reducere',
            'in_cart'       => 'Coșul tău: %amount%',
            'reached'       => 'Ai dreptul la %percent% reducere!',
            'savings'       => 'Economisești aprox. %amount%',
            'applied'       => 'Reducere de %percent% activă — economisești %amount%',
            'applied_plain' => 'Reducere de %percent% activă',
            'next'          => 'Mai adaugă %amount% și reducerea crește la %percent%',
            'upgrade'       => 'Acum ai dreptul la %percent% reducere!',
            'cta_apply'     => 'Activează reducerea de %percent%',
            'cta_upgrade'   => 'Treci la reducerea de %percent%',
            'top'           => 'Este cea mai mare reducere a noastră — cumpărături plăcute!',
            'exclude'       => 'Reducerea nu se aplică produselor deja reduse.',
            'blocked'       => 'Nu se cumulează cu voucherul %name% din coșul tău.',
            'only_special'  => 'Coșul tău conține doar produse reduse — reducerea pe praguri se aplică produselor la preț întreg.',
            'one_click'     => 'Activezi codul cu un clic în coș.',
            'success'       => 'Reducere de %percent% activată (cod %code%).',
            'home_title'    => 'Cumperi mai mult, plătești mai puțin',
            'home_text'     => 'Cu cât comanda e mai mare, cu atât prețul e mai mic. Activezi codul cu un clic în coș.',
        ],
        'sv' => [
            'title'         => 'Köp mer, betala mindre',
            'tier'          => '%percent% rabatt från %amount%',
            'tier_short'    => '%percent% från %amount%',
            'code'          => 'kod %code%',
            'missing'       => 'Lägg till %amount% till och få %percent% rabatt',
            'in_cart'       => 'Din varukorg: %amount%',
            'reached'       => 'Du får %percent% rabatt!',
            'savings'       => 'Du sparar ca %amount%',
            'applied'       => '%percent% rabatt aktiv — du sparar %amount%',
            'applied_plain' => '%percent% rabatt aktiv',
            'next'          => 'Lägg till %amount% till så stiger rabatten till %percent%',
            'upgrade'       => 'Du har nu rätt till %percent% rabatt!',
            'cta_apply'     => 'Aktivera %percent% rabatt',
            'cta_upgrade'   => 'Byt till %percent% rabatt',
            'top'           => 'Det är vår högsta rabatt — trevlig shopping!',
            'exclude'       => 'Rabatten gäller inte redan nedsatta produkter.',
            'blocked'       => 'Kan inte kombineras med rabattkoden %name% i din varukorg.',
            'only_special'  => 'Din varukorg innehåller bara nedsatta produkter — trappstegsrabatten gäller produkter till ordinarie pris.',
            'one_click'     => 'Aktivera koden med ett klick i varukorgen.',
            'success'       => '%percent% rabatt aktiverad (kod %code%).',
            'home_title'    => 'Köp mer, betala mindre',
            'home_text'     => 'Ju större order, desto lägre pris. Aktivera koden med ett klick i varukorgen.',
        ],
        'lv' => [
            'title'         => 'Pērc vairāk, maksā mazāk',
            'tier'          => '%percent% atlaide no %amount%',
            'tier_short'    => '%percent% no %amount%',
            'code'          => 'kods %code%',
            'missing'       => 'Pievieno vēl par %amount% un saņem %percent% atlaidi',
            'in_cart'       => 'Tavs grozs: %amount%',
            'reached'       => 'Tev pienākas %percent% atlaide!',
            'savings'       => 'Ietaupi aptuveni %amount%',
            'applied'       => '%percent% atlaide aktīva — ietaupi %amount%',
            'applied_plain' => '%percent% atlaide aktīva',
            'next'          => 'Pievieno vēl par %amount%, un atlaide pieaugs līdz %percent%',
            'upgrade'       => 'Tev jau pienākas %percent% atlaide!',
            'cta_apply'     => 'Aktivizēt %percent% atlaidi',
            'cta_upgrade'   => 'Pāriet uz %percent% atlaidi',
            'top'           => 'Tā ir mūsu lielākā atlaide — patīkamu iepirkšanos!',
            'exclude'       => 'Atlaide neattiecas uz jau nocenotām precēm.',
            'blocked'       => 'Nav apvienojama ar grozā esošo kuponu %name%.',
            'only_special'  => 'Grozā ir tikai nocenotas preces — pakāpju atlaide attiecas uz precēm par parasto cenu.',
            'one_click'     => 'Kodu aktivizēsi ar vienu klikšķi grozā.',
            'success'       => '%percent% atlaide aktivizēta (kods %code%).',
            'home_title'    => 'Pērc vairāk, maksā mazāk',
            'home_text'     => 'Jo lielāks pasūtījums, jo zemāka cena. Kodu aktivizēsi ar vienu klikšķi grozā.',
        ],
        'et' => [
            'title'         => 'Osta rohkem, maksa vähem',
            'tier'          => '%percent% soodustust alates %amount%',
            'tier_short'    => '%percent% alates %amount%',
            'code'          => 'kood %code%',
            'missing'       => 'Lisa veel %amount% ja saad %percent% soodustust',
            'in_cart'       => 'Sinu ostukorv: %amount%',
            'reached'       => 'Sul on õigus %percent% soodustusele!',
            'savings'       => 'Säästad umbes %amount%',
            'applied'       => '%percent% soodustus on aktiivne — säästad %amount%',
            'applied_plain' => '%percent% soodustus on aktiivne',
            'next'          => 'Lisa veel %amount% ja soodustus tõuseb %percent%-ni',
            'upgrade'       => 'Sul on nüüd õigus %percent% soodustusele!',
            'cta_apply'     => 'Aktiveeri %percent% soodustus',
            'cta_upgrade'   => 'Vaheta %percent% soodustuse vastu',
            'top'           => 'See on meie suurim soodustus — head ostlemist!',
            'exclude'       => 'Soodustus ei kehti juba allahinnatud toodetele.',
            'blocked'       => 'Ei ole ühendatav ostukorvis oleva kupongiga %name%.',
            'only_special'  => 'Ostukorvis on ainult allahinnatud tooted — astmeline soodustus kehtib tavahinnaga toodetele.',
            'one_click'     => 'Koodi aktiveerid ühe klõpsuga ostukorvis.',
            'success'       => '%percent% soodustus aktiveeritud (kood %code%).',
            'home_title'    => 'Osta rohkem, maksa vähem',
            'home_text'     => 'Mida suurem tellimus, seda madalam hind. Koodi aktiveerid ühe klõpsuga ostukorvis.',
        ],
        'uk' => [
            'title'         => 'Купуйте більше, платіть менше',
            'tier'          => 'Знижка %percent% від %amount%',
            'tier_short'    => '%percent% від %amount%',
            'code'          => 'код %code%',
            'missing'       => 'Додайте ще на %amount% і отримайте знижку %percent%',
            'in_cart'       => 'Ваш кошик: %amount%',
            'reached'       => 'Вам належить знижка %percent%!',
            'savings'       => 'Ви заощаджуєте близько %amount%',
            'applied'       => 'Знижка %percent% активна — ви заощаджуєте %amount%',
            'applied_plain' => 'Знижка %percent% активна',
            'next'          => 'Додайте ще на %amount%, і знижка зросте до %percent%',
            'upgrade'       => 'Вам уже належить знижка %percent%!',
            'cta_apply'     => 'Активувати знижку %percent%',
            'cta_upgrade'   => 'Перейти на знижку %percent%',
            'top'           => 'Це наша найбільша знижка — приємних покупок!',
            'exclude'       => 'Знижка не поширюється на вже уцінені товари.',
            'blocked'       => 'Не поєднується з купоном %name% у вашому кошику.',
            'only_special'  => 'У кошику лише уцінені товари — порогова знижка діє на товари за звичайною ціною.',
            'one_click'     => 'Код активується одним кліком у кошику.',
            'success'       => 'Знижку %percent% активовано (код %code%).',
            'home_title'    => 'Купуйте більше, платіть менше',
            'home_text'     => 'Що більше замовлення, то нижча ціна. Код активується одним кліком у кошику.',
        ],
    ];

    /** @var Hummingbird_editor */
    private $module;

    /** @var Context */
    private $context;

    /** @var array<int,array>|null progi z reguł koszyka (memo na żądanie) */
    private static $tiers = null;

    /** @var array<string,array> stan per koszyk (memo na żądanie) */
    private static $state = [];

    public function __construct(Hummingbird_editor $module, ?Context $context = null)
    {
        $this->module  = $module;
        $this->context = $context ?: Context::getContext();
    }

    /* ── Konfiguracja ─────────────────────────────────────────────────────── */

    public static function isEnabled(): bool
    {
        return (bool) (int) Configuration::get(self::CONF_ENABLED) && self::configuredCodes() !== [];
    }

    public static function showInCart(): bool
    {
        $v = Configuration::get(self::CONF_SHOW_CART);

        return $v === false ? true : (bool) (int) $v;
    }

    public static function showOnProduct(): bool
    {
        $v = Configuration::get(self::CONF_SHOW_PRODUCT);

        return $v === false ? true : (bool) (int) $v;
    }

    public static function homeEnabled(): bool
    {
        return (bool) (int) Configuration::get(self::CONF_HOME_ENABLED);
    }

    /**
     * Kody z panelu — po przecinku, białe znaki obojętne, kolejność nieistotna
     * (progi i tak sortujemy po kwocie).
     *
     * @return string[]
     */
    public static function configuredCodes(): array
    {
        $raw = (string) Configuration::get(self::CONF_CODES);
        $codes = [];
        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $code) {
            $code = trim($code);
            if ($code !== '' && Validate::isCleanHtml($code)) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Czyści memo — po zmianie koszyka w tym samym żądaniu (kontroler
     * aktywujący kod) stan trzeba policzyć od nowa.
     */
    public static function reset(): void
    {
        self::$state = [];
    }

    /* ── Progi z reguł koszyka ────────────────────────────────────────────── */

    /**
     * Progi (reguły koszyka) aktualnie ważne — aktywne, w dacie, z zapasem użyć,
     * procentowe, z progiem kwotowym. Posortowane rosnąco po progu w walucie
     * bieżącego kontekstu.
     *
     * @return array<int,array{
     *   id:int, code:string, name:string, percent:float, percent_label:string,
     *   threshold:float, threshold_formatted:string, tax:bool, exclude_special:bool,
     *   restriction:bool, priority:int
     * }>
     */
    public function getTiers(): array
    {
        if (self::$tiers !== null) {
            return self::$tiers;
        }
        self::$tiers = [];

        $codes = self::configuredCodes();
        if (!$codes) {
            return self::$tiers;
        }

        $idLang = (int) $this->context->language->id;
        $rows = (array) Db::getInstance()->executeS(
            'SELECT cr.*, crl.`name`
             FROM `' . _DB_PREFIX_ . 'cart_rule` cr
             LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_lang` crl
                ON crl.`id_cart_rule` = cr.`id_cart_rule` AND crl.`id_lang` = ' . $idLang . '
             WHERE cr.`code` IN ("' . implode('","', array_map('pSQL', $codes)) . '")
               AND cr.`active` = 1
               AND cr.`date_from` <= NOW() AND cr.`date_to` >= NOW()
               AND cr.`quantity` > 0
               AND cr.`reduction_percent` > 0
               AND cr.`minimum_amount` > 0'
        );
        if (!$rows) {
            return self::$tiers;
        }

        $currency = $this->currency();
        $tiers = [];
        foreach ($rows as $row) {
            if (!$this->groupAllowed($row)) {
                continue;
            }
            $threshold = (float) $row['minimum_amount'];
            $idRuleCurrency = (int) $row['minimum_amount_currency'];
            if ($idRuleCurrency && $idRuleCurrency !== (int) $currency->id) {
                $threshold = (float) Tools::convertPriceFull($threshold, new Currency($idRuleCurrency), $currency);
            }
            $percent = (float) $row['reduction_percent'];
            $tiers[] = [
                'id'                  => (int) $row['id_cart_rule'],
                'code'                => (string) $row['code'],
                'name'                => (string) ($row['name'] ?? $row['code']),
                'percent'             => $percent,
                'percent_label'       => $this->formatPercent($percent),
                'threshold'           => $threshold,
                'threshold_formatted' => $this->formatPrice($threshold),
                // „500 zł” zamiast „500,00 zł” na plakietkach — próg to okrągła
                // kwota z reguły, grosze tylko zaśmiecają.
                'threshold_short'     => $this->formatPriceShort($threshold),
                'pos'                 => 100,
                'tax'                 => (bool) $row['minimum_amount_tax'],
                'with_shipping'       => (bool) $row['minimum_amount_shipping'],
                'exclude_special'     => (bool) $row['reduction_exclude_special'],
                'restriction'         => (bool) $row['cart_rule_restriction'],
                'priority'            => (int) $row['priority'],
            ];
        }
        usort($tiers, static function (array $a, array $b): int {
            return $a['threshold'] <=> $b['threshold'];
        });

        // Pozycja znacznika na wspólnym pasku (0–100% = 0 zł–najwyższy próg).
        $top = $tiers ? (float) end($tiers)['threshold'] : 0.0;
        foreach ($tiers as &$tier) {
            $tier['pos'] = $top > 0 ? (int) round($tier['threshold'] / $top * 100) : 100;
        }
        unset($tier);

        return self::$tiers = $tiers;
    }

    /**
     * Ten sam test grupy, który robi CartRule::checkValidity(): gość liczy się
     * do PS_UNIDENTIFIED_GROUP, zalogowany do swoich grup.
     */
    private function groupAllowed(array $row): bool
    {
        if (!(int) $row['group_restriction']) {
            return true;
        }
        $idCustomer = (int) ($this->context->customer->id ?? 0);
        $groupSql = $idCustomer
            ? 'IN (SELECT cg.`id_group` FROM `' . _DB_PREFIX_ . 'customer_group` cg WHERE cg.`id_customer` = ' . $idCustomer . ')'
            : '= ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP');

        return (bool) Db::getInstance()->getValue(
            'SELECT crg.`id_cart_rule` FROM `' . _DB_PREFIX_ . 'cart_rule_group` crg
             WHERE crg.`id_cart_rule` = ' . (int) $row['id_cart_rule'] . ' AND crg.`id_group` ' . $groupSql
        );
    }

    /** Próg po kodzie (z listy ważnych). */
    public function getTierByCode(string $code): ?array
    {
        foreach ($this->getTiers() as $tier) {
            if (strcasecmp($tier['code'], $code) === 0) {
                return $tier;
            }
        }

        return null;
    }

    /* ── Stan koszyka ─────────────────────────────────────────────────────── */

    /**
     * Wszystko, czego potrzebuje szablon paska — policzone raz na żądanie.
     *
     * Kwota liczona jak w CartRule::checkValidity(): suma produktów (brutto
     * lub netto wg reguły), bez wysyłki, bez produktów-prezentów z innych reguł.
     * Oszczędność szacowana jak w CartRule::getContextualValue(): procent od
     * pozycji bez przeceny, gdy reguła ma „bez przecenionych”.
     *
     * @return array<string,mixed>
     */
    public function getState(): array
    {
        $cart = $this->context->cart;
        $idCart = Validate::isLoadedObject($cart) ? (int) $cart->id : 0;
        if (isset(self::$state[$idCart])) {
            return self::$state[$idCart];
        }

        $tiers = $this->getTiers();
        $state = [
            'enabled'          => $tiers !== [],
            'tiers'            => $tiers,
            'amount'           => 0.0,
            'amount_formatted' => $this->formatPrice(0.0),
            'eligible'         => 0.0,
            'applied'          => null,
            'next'             => null,
            'reached'          => null,
            'missing'          => 0.0,
            'missing_formatted'=> '',
            'progress'         => 0,
            'progress_total'   => 0,
            'savings'          => 0.0,
            'savings_formatted'=> '',
            'blocked_by'       => '',
            'only_special'     => false,
            'exclude_special'  => false,
            'action'           => '',      // '' | 'apply' | 'upgrade'
            'action_tier'      => null,
            'message'          => '',
            'sub'              => '',
            'note'             => '',
        ];
        if (!$tiers) {
            return self::$state[$idCart] = $state;
        }

        foreach ($tiers as $tier) {
            if ($tier['exclude_special']) {
                $state['exclude_special'] = true;
            }
        }

        $hasCart = $idCart > 0 && $cart->nbProducts() > 0;
        $amount = 0.0;
        $eligible = 0.0;
        $appliedIds = [];
        $otherRules = [];

        if ($hasCart) {
            // Podstawa progu: wg flagi podatku pierwszego progu (izpol: brutto).
            $useTax = (bool) $tiers[0]['tax'];
            $amount = (float) $cart->getOrderTotal($useTax, Cart::ONLY_PRODUCTS);
            if ($tiers[0]['with_shipping']) {
                $amount += (float) $cart->getOrderTotal($useTax, Cart::ONLY_SHIPPING);
            }

            $products = (array) $cart->getProducts();
            $special = 0.0;
            foreach ($products as $product) {
                if (!empty($product['reduction_applies'])) {
                    $special += (float) ($useTax ? $product['total_wt'] : $product['total']);
                }
            }

            $cartRules = (array) $cart->getCartRules(CartRule::FILTER_ACTION_ALL, false);
            $tierIds = array_column($tiers, 'id');
            foreach ($cartRules as $rule) {
                $idRule = (int) $rule['id_cart_rule'];
                // Prezent z innej reguły nie liczy się do progu (tak liczy rdzeń).
                if (!empty($rule['gift_product'])) {
                    $gift = $cart->getProductQuantity((int) $rule['gift_product'], (int) $rule['gift_product_attribute']);
                    if (!empty($gift['quantity'])) {
                        foreach ($products as $product) {
                            if ((int) $product['id_product'] === (int) $rule['gift_product']
                                && (int) $product['id_product_attribute'] === (int) $rule['gift_product_attribute']) {
                                $amount -= (float) ($useTax ? $product['price_wt'] : $product['price']);
                                break;
                            }
                        }
                    }
                }
                if (in_array($idRule, $tierIds, true)) {
                    $appliedIds[] = $idRule;
                } else {
                    $otherRules[] = $rule;
                }
            }
            $amount = max(0.0, $amount);
            $eligible = max(0.0, $amount - $special);
        }

        $state['amount'] = $amount;
        $state['amount_formatted'] = $this->formatPrice($amount);
        $state['eligible'] = $eligible;

        // Który próg jest aktywny, który osiągnięty, który następny.
        $applied = null;
        $reached = null;
        $next = null;
        foreach ($tiers as $tier) {
            if (in_array($tier['id'], $appliedIds, true) && ($applied === null || $tier['threshold'] > $applied['threshold'])) {
                $applied = $tier;
            }
            if ($amount >= $tier['threshold']) {
                $reached = $tier;
            } elseif ($next === null) {
                $next = $tier;
            }
        }
        $state['applied'] = $applied;
        $state['reached'] = $reached;
        $state['next'] = $next;

        if ($next !== null) {
            $state['missing'] = max(0.0, $next['threshold'] - $amount);
            $state['missing_formatted'] = $this->formatPrice($state['missing']);
            $state['progress'] = (int) min(100, max(0, floor($amount / $next['threshold'] * 100)));
        } else {
            $state['progress'] = 100;
        }
        // Wypełnienie wspólnego paska ze znacznikami progów (0 zł → najwyższy próg).
        $top = (float) end($tiers)['threshold'];
        $state['progress_total'] = $top > 0 ? (int) min(100, max(0, floor($amount / $top * 100))) : 0;
        // Minimalnie widoczne wypełnienie, gdy coś już jest w koszyku — pusty pasek
        // przy 30 zł w koszyku wyglądałby na zepsuty.
        if ($amount > 0 && $state['progress_total'] < 2) {
            $state['progress_total'] = 2;
        }

        // Oszczędność: dla progu, który proponujemy (przycisk), a bez propozycji —
        // dla aktywnego. Przy „zamień na 10%” ma kusić stawka po zamianie, nie
        // obecne 5%.
        $state['only_special'] = $hasCart && $amount > 0 && $eligible <= 0.009 && $state['exclude_special'];

        // Inny kupon, którego reguła progowa nie toleruje.
        $candidate = $reached;
        if ($candidate !== null && $candidate['restriction']) {
            foreach ($otherRules as $rule) {
                if (!(int) $rule['cart_rule_restriction']) {
                    continue;
                }
                $combinable = Db::getInstance()->getValue(
                    'SELECT `id_cart_rule_1` FROM `' . _DB_PREFIX_ . 'cart_rule_combination`
                     WHERE (`id_cart_rule_1` = ' . (int) $candidate['id'] . ' AND `id_cart_rule_2` = ' . (int) $rule['id_cart_rule'] . ')
                        OR (`id_cart_rule_2` = ' . (int) $candidate['id'] . ' AND `id_cart_rule_1` = ' . (int) $rule['id_cart_rule'] . ')'
                );
                if (!$combinable) {
                    $state['blocked_by'] = (string) ($rule['name'] ?: $rule['code']);
                    break;
                }
            }
        }

        // Co proponujemy.
        if ($reached !== null && !$state['blocked_by'] && !$state['only_special']) {
            if ($applied === null) {
                $state['action'] = 'apply';
                $state['action_tier'] = $reached;
            } elseif ($reached['threshold'] > $applied['threshold']) {
                $state['action'] = 'upgrade';
                $state['action_tier'] = $reached;
            }
        }

        $savingsTier = $state['action_tier'] ?: ($applied ?: $reached);
        if ($savingsTier !== null) {
            $base = $savingsTier['exclude_special'] ? $eligible : $amount;
            $state['savings'] = Tools::ps_round($base * $savingsTier['percent'] / 100, 2);
            $state['savings_formatted'] = $this->formatPrice($state['savings']);
        }

        $this->compose($state);

        return self::$state[$idCart] = $state;
    }

    /**
     * Zdania dla klienta — jedna logika dla koszyka, podglądu i karty produktu,
     * żeby nigdzie nie obiecywać czego innego.
     */
    private function compose(array &$state): void
    {
        $applied = $state['applied'];
        $reached = $state['reached'];
        $next    = $state['next'];

        if ($state['amount'] <= 0) {
            // Pusty koszyk (karta produktu / strona główna): sama drabinka.
            $state['message'] = $this->label('title');
            $state['sub'] = '';
        } elseif ($state['action'] === 'upgrade') {
            $state['message'] = $this->label('upgrade', ['%percent%' => $reached['percent_label']]);
            $state['sub'] = $state['savings'] > 0 ? $this->label('savings', ['%amount%' => $state['savings_formatted']]) : '';
        } elseif ($state['action'] === 'apply') {
            $state['message'] = $this->label('reached', ['%percent%' => $reached['percent_label']]);
            $state['sub'] = $state['savings'] > 0 ? $this->label('savings', ['%amount%' => $state['savings_formatted']]) : '';
        } elseif ($applied !== null) {
            $state['message'] = $state['savings'] > 0
                ? $this->label('applied', ['%percent%' => $applied['percent_label'], '%amount%' => $state['savings_formatted']])
                : $this->label('applied_plain', ['%percent%' => $applied['percent_label']]);
            $state['sub'] = $next !== null
                ? $this->label('next', ['%amount%' => $state['missing_formatted'], '%percent%' => $next['percent_label']])
                : $this->label('top');
        } elseif ($next !== null) {
            $state['message'] = $this->label('missing', ['%amount%' => $state['missing_formatted'], '%percent%' => $next['percent_label']]);
            $state['sub'] = $this->label('in_cart', ['%amount%' => $state['amount_formatted']]);
        } else {
            $state['message'] = $this->label('title');
            $state['sub'] = '';
        }

        if ($state['only_special'] && $reached !== null) {
            $state['note'] = $this->label('only_special');
        } elseif ($state['blocked_by'] !== '') {
            $state['note'] = $this->label('blocked', ['%name%' => $state['blocked_by']]);
        } elseif ($state['exclude_special']) {
            $state['note'] = $this->label('exclude');
        }
    }

    /* ── Renderowanie ─────────────────────────────────────────────────────── */

    /**
     * Pasek w danym kontekście (cart / preview / product). Pusty string, gdy
     * nie ma co pokazać — szablon motywu nie musi niczego sprawdzać.
     */
    public function renderBar(string $ctx, $product = null): string
    {
        if (!self::isEnabled()) {
            return '';
        }
        if ($ctx === self::CTX_PRODUCT && !self::showOnProduct()) {
            return '';
        }
        // Produkt juz przeceniony nie zalapie sie na rabat progowy (reguly maja
        // „bez przecenionych”) — pasek na jego karcie tylko by mylil.
        if ($ctx === self::CTX_PRODUCT && $this->isDiscountedProduct($product) && $this->anyTierExcludesSpecial()) {
            return '';
        }
        if ($ctx !== self::CTX_PRODUCT && !self::showInCart()) {
            return '';
        }
        $state = $this->getState();
        if (!$state['enabled']) {
            return '';
        }
        // W koszyku i podglądzie bez produktów nie ma czego liczyć.
        if ($ctx !== self::CTX_PRODUCT && $state['amount'] <= 0) {
            return '';
        }

        $this->context->smarty->assign([
            'hbe_tiers'       => $state,
            'hbe_tiers_ctx'   => $ctx,
            'hbe_tiers_apply' => $this->applyUrl(),
            'hbe_tiers_token' => Tools::getToken(false),
            'hbe_tiers_l'     => $this->labelSet(),
        ]);

        return $this->module->display($this->module->getLocalPath() . 'hummingbird_editor.php', 'views/templates/hook/tiers-bar.tpl');
    }

    /** Czy ktorykolwiek prog pomija produkty przecenione. */
    private function anyTierExcludesSpecial(): bool
    {
        foreach ($this->getTiers() as $tier) {
            if ($tier['exclude_special']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Czy produkt karty ma przecene (cena specjalna z obnizka). Najpierw
     * prezentowany produkt z szablonu (`has_discount`), a bez niego — porownanie
     * ceny z obnizka i bez dla id_product z zadania.
     *
     * @param mixed $product ProductLazyArray / tablica z szablonu albo null
     */
    private function isDiscountedProduct($product): bool
    {
        if ($product instanceof ArrayAccess || is_array($product)) {
            try {
                if (isset($product['has_discount'])) {
                    return (bool) $product['has_discount'];
                }
            } catch (Throwable $e) {
                // ponizej sprawdzimy po id
            }
        }
        $idProduct = (int) Tools::getValue('id_product');
        if ($idProduct <= 0) {
            return false;
        }
        $idAttribute = (int) Tools::getValue('id_product_attribute', 0) ?: null;
        $withReduction = (float) Product::getPriceStatic($idProduct, true, $idAttribute, 6, null, false, true);
        $withoutReduction = (float) Product::getPriceStatic($idProduct, true, $idAttribute, 6, null, false, false);

        return $withoutReduction > 0 && $withReduction < $withoutReduction - 0.001;
    }

    /** Sekcja strony głównej — drabinka progów z nagłówkiem i opisem z panelu. */
    public function renderHome(): string
    {
        if (!self::isEnabled() || !self::homeEnabled()) {
            return '';
        }
        $tiers = $this->getTiers();
        if (!$tiers) {
            return '';
        }
        $idLang = (int) $this->context->language->id;
        $title = trim((string) (HbEditorConfig::get(self::CONF_HOME_TITLE, $idLang) ?: ''));
        $text  = trim((string) (HbEditorConfig::get(self::CONF_HOME_TEXT, $idLang) ?: ''));
        $ctaText = trim((string) (HbEditorConfig::get(self::CONF_HOME_CTA_TEXT, $idLang) ?: ''));
        $ctaUrl  = trim((string) (HbEditorConfig::get(self::CONF_HOME_CTA_URL, $idLang) ?: ''));
        if ($ctaUrl !== '' && !preg_match('#^https?://#i', $ctaUrl) && strpos($ctaUrl, '/') !== 0) {
            $ctaUrl = 'https://' . $ctaUrl;
        }

        $excludeSpecial = false;
        foreach ($tiers as $tier) {
            $excludeSpecial = $excludeSpecial || $tier['exclude_special'];
        }

        $this->context->smarty->assign([
            'hbe_tiers_home' => [
                'title'           => $title !== '' ? $title : $this->label('home_title'),
                'text'            => $text !== '' ? $text : $this->label('home_text'),
                'cta_text'        => $ctaText,
                'cta_url'         => $ctaUrl,
                'tiers'           => $tiers,
                'exclude_special' => $excludeSpecial,
            ],
            'hbe_tiers_l' => $this->labelSet(),
        ]);

        return $this->module->display($this->module->getLocalPath() . 'hummingbird_editor.php', 'views/templates/hook/tiers-home.tpl');
    }

    /** Adres kontrolera aktywującego kod (POST). */
    public function applyUrl(): string
    {
        return $this->context->link->getModuleLink($this->module->name, 'tiers', [], true);
    }

    /* ── Teksty i formaty ─────────────────────────────────────────────────── */

    /**
     * Zdanie w języku klienta z podstawionymi wartościami.
     *
     * @param array<string,string> $vars
     */
    public function label(string $id, array $vars = []): string
    {
        $key = self::LABELS['en'][$id] ?? $id;
        $text = $this->module->tiersTrans($key);
        if ($text === $key || $text === '') {
            $iso = strtolower((string) ($this->context->language->iso_code ?? ''));
            $text = self::LABELS[$iso][$id] ?? $key;
        }

        return $vars ? strtr($text, $vars) : $text;
    }

    /**
     * Teksty bez zmiennych dla szablonów (tytuł, „kod”, przypisy).
     *
     * @return array<string,string>
     */
    private function labelSet(): array
    {
        $out = [];
        foreach (['title', 'code', 'one_click', 'exclude', 'tier', 'tier_short', 'cta_apply', 'cta_upgrade'] as $id) {
            $out[$id] = $this->label($id);
        }

        return $out;
    }

    private function currency(): Currency
    {
        $cart = $this->context->cart;
        if (Validate::isLoadedObject($cart) && (int) $cart->id_currency) {
            return Currency::getCurrencyInstance((int) $cart->id_currency);
        }

        return $this->context->currency;
    }

    public function formatPrice(float $amount): string
    {
        $currency = $this->currency();
        $locale = $this->context->getCurrentLocale();
        if ($locale === null || !Validate::isLoadedObject($currency)) {
            return number_format($amount, 2, ',', ' ');
        }

        return $locale->formatPrice($amount, $currency->iso_code);
    }

    /** Kwota bez zerowych groszy: „500 zł”, „120 €”; „499,50 zł” zostaje. */
    public function formatPriceShort(float $amount): string
    {
        $text = $this->formatPrice($amount);

        return (string) preg_replace('/([.,])00(?![\d])/u', '', $text);
    }

    /** „5%” zamiast „5.00%”; połówki zostają („7,5%”). */
    public function formatPercent(float $percent): string
    {
        $text = rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');
        $iso = strtolower((string) ($this->context->language->iso_code ?? 'en'));
        if (!in_array($iso, ['en', 'lt', 'lv', 'et'], true)) {
            $text = str_replace('.', ',', $text);
        }

        return $text . '%';
    }
}
