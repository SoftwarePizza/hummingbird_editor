# Hummingbird Editor

Hummingbird Editor to moduł PrestaShop do budowania i zarządzania sekcjami treści na froncie sklepu. Moduł łączy klasyczne bloki tekstowe, banery, sekcje z obrazami, karuzele, slider oraz prosty edytor bloków przypinanych do hooków. Jest przygotowany do pracy w środowisku multi-store, multi-language i z układem responsywnym. Najlepiej współpracuje z forkiem szablonu Hummingbird Elegant, dla którego był projektowany i testowany.

## Najważniejsze możliwości

- tworzenie własnych bloków treści przypinanych do dowolnego hooka,
- obsługa standardowych hooków PrestaShop oraz hooków własnych,
- pełna obsługa wielu sklepów,
- pełna obsługa wielu języków,
- osobne treści i linki dla desktopu oraz mobile,
- gotowe sekcje: top bar, info bar, banery, tagline, kolumny tekstowe, sekcje obrazowe, ikony, marki, slider,
- eksport i import ustawień w formacie XML,
- możliwość sortowania bloków metodą drag and drop,
- dołączone style i skrypty frontowe oraz panel administracyjny oparty o Bootstrap.

## Wymagania

- PrestaShop 1.7.7 lub nowszy,
- aktywny panel administracyjny z uprawnieniami do instalacji modułów,
- dostęp do katalogów `modules/` i `img/` z możliwością zapisu,
- PHP zgodne z wersją wspieraną przez Twoją instalację PrestaShop.
- najlepsze dopasowanie uzyskasz na forku szablonu Hummingbird Elegant.

## Instalacja

1. Skopiuj katalog `hummingbird_editor` do folderu `modules/` w instalacji PrestaShop.
2. Zaloguj się do panelu administracyjnego sklepu.
3. Wejdź w `Moduły > Menedżer modułów` i zainstaluj `Hummingbird Editor`.
4. Po instalacji moduł utworzy wymagane tabele bazy danych, katalog na grafiki oraz zakładkę administracyjną.
5. Otwórz konfigurację modułu i uzupełnij sekcje treści.

## Co robi instalacja

Podczas instalacji moduł:

- tworzy tabele dla bloków treści i slajdów,
- tworzy katalog `img/hb_editor/` na pliki graficzne bloków,
- tworzy katalog `modules/hummingbird_editor/images/` dla zasobów slidera,
- rejestruje hooki `actionFrontControllerSetMedia`, `displayAfterBodyOpeningTag` i `displayHome`,
- dodaje zakładkę administracyjną `Hummingbird Editor` w panelu PrestaShop,
- ustawia domyślne wartości konfiguracji dla dostępnych sekcji.

## Panel administracyjny

Moduł ma własną zakładkę w panelu administracyjnym. Interfejs jest podzielony na kilka głównych obszarów:

- `Paski info` - top bar i dwa paski informacyjne,
- `Banery` - banery z obrazami i CTA,
- `Kolumny tekstowe` - układy 3-kolumnowe,
- `Sekcje z obrazkami` - sekcje kategorii, split-block i bloki ikon,
- `Karuzele` - nagłówki dla zestawów produktowych / karuzel,
- `Slider` - zarządzanie slajdami i ustawieniami slidera,
- `Koszyk` - ustawienia podglądu koszyka,
- `Ustawienia` - dodatkowe opcje globalne.

W panelu dostępne są też:

- przycisk dodawania nowego bloku,
- eksport ustawień do XML,
- import ustawień z XML,
- przeciąganie bloków w celu zmiany kolejności,
- widok bloków pogrupowanych według hooka.

## Dostępne sekcje i typy treści

Moduł obsługuje gotowe sekcje frontowe, które można włączać i konfigurować niezależnie:

- `Top promo bar` - pasek promocyjny u góry strony,
- `Info bar` - pasek informacyjny pod sliderem,
- `Info bar 2` - druga kopia paska informacyjnego,
- `Baner z obrazkiem` - duży baner hero,
- `Baner z obrazkiem 2` - druga wersja baneru hero,
- `Blok 3 kolumn` - trzy proste kolumny z tekstem i linkiem,
- `Blok 3 kolumn z opisami` - trzy kolumny z tytułem, opisem i linkiem,
- `Blok tagline` - krótki blok z hasłem i odnośnikiem,
- `Sekcja Kategorie` - dwie kolumny z grafikami i podpisami,
- `Sekcja 3 kolumn (tekst+obraz+obraz)` - rozbudowany blok typu split,
- `Blok 4 kolumn z ikonami` - cztery pola z ikonami i opisami,
- `Pasek marek (logotypy)` - lista logotypów producentów lub własnych grafik,
- `Slider (banery)` - slajdy z tekstem, overlayem i CTA,
- własne bloki przypinane do dowolnych hooków.

### Własne bloki

Każdy blok zapisywany w module może zawierać:

- nazwę hooka,
- typ bloku,
- kolejność wyświetlania,
- stan aktywności,
- osobne treści dla desktopu i mobile,
- osobne linki dla desktopu i mobile,
- obrazy desktopowe i mobilne,
- przypisanie do wielu sklepów,
- treści tłumaczone na wiele języków.

Jeżeli zapiszesz blok do nowego hooka, moduł potrafi zarejestrować taki hook automatycznie i podpiąć do niego moduł.

## Hooki

Moduł jest przygotowany do działania na standardowych hookach PrestaShop oraz hookach własnych.

Standardowe hooki sugerowane w panelu:

- `displayHome`
- `displayBanner`
- `displayTop`
- `displayNav`
- `displayLeftColumn`
- `displayRightColumn`
- `displayFooter`
- `displayFooterBefore`
- `displayAfterBodyOpeningTag`
- `displayHeader`
- `displayProductButtons`
- `displayProductAdditionalInfo`
- `displayShoppingCart`
- `displayOrderConfirmation`
- `displayContentWrapperTop`
- `displayContentWrapperBottom`
- `displayWrapperTop`
- `displayWrapperBottom`
- `displayNotFound`
- `displayMaintenance`

Jeśli blok zostanie przypięty do innego, własnego hooka, moduł utworzy go po stronie PrestaShop i będzie renderował treść przez mechanizm `__call()`.

## Kolejność sekcji na stronie głównej

Ustawienie `HBE_HOME_ORDER` kontroluje kolejność elementów na `displayHome`.

W kolejności mogą występować:

- gotowe elementy systemowe, takie jak `infobar`, `imghero`, `cols3`, `tagline`,
- konkretne bloki z bazy danych,
- zewnętrzne moduły przejęte do wspólnego renderowania przez Hummingbird Editor.

Domyślna kolejność po instalacji to:

- `infobar`
- `imghero`
- `cols3`
- `tagline`

## Multimedia i pliki

- obrazy bloków są zapisywane w `img/hb_editor/`,
- grafiki slidera są przechowywane w `modules/hummingbird_editor/images/`,
- frontowe style znajdują się w `views/css/front.css`,
- panel administracyjny korzysta z `views/css/admin.css` i `views/js/admin.js`,
- slider korzysta z `views/js/slider.js`,
- koszyk i dodatkowe widżety mają własne skrypty w katalogu `views/js/`.

## Import i eksport

Moduł pozwala eksportować ustawienia do XML i importować je z powrotem.

Z tej funkcji warto korzystać, gdy chcesz:

- przenieść konfigurację między sklepami,
- wykonać kopię roboczą ustawień,
- odtworzyć zestaw bloków po migracji,
- przygotować środowisko testowe z takim samym układem sekcji.

## Koszyk i elementy dodatkowe

Hummingbird Editor zawiera również funkcje wspierające moduł koszyka PrestaShop:

- podgląd koszyka po najechaniu kursorem,
- modalny podgląd koszyka,
- ręcznie ustawiany próg darmowej dostawy,
- opcje ukrywania waluty, języka i szybkiego podglądu w wybranych wariantach.

## Ustawienia slidera

Slider posiada ustawienia globalne, między innymi:

- czas zmiany slajdów,
- autoplay,
- pauza po najechaniu,
- widoczność strzałek,
- styl strzałek,
- widoczność kropek nawigacyjnych.

Domyślne wartości są ustawiane podczas instalacji, ale możesz je później zmienić z poziomu panelu modułu.

## Struktura katalogów

```text
hummingbird_editor/
├── hummingbird_editor.php
├── config_pl.xml
├── classes/
├── controllers/admin/
├── images/
├── upgrade/
├── views/css/
├── views/js/
└── views/templates/
```

## Upgrade i odinstalowanie

Podczas aktualizacji modułu przewidziano skrypt `upgrade-1.1.0.php` oraz metody dbające o zachowanie schematu slidera i tabel bloków.

Przy odinstalowaniu moduł:

- usuwa utworzone tabele,
- usuwa wpisy konfiguracji,
- usuwa zakładkę administracyjną.

Same pliki graficzne w katalogach mogą pozostać na dysku, dlatego przed pełnym usunięciem warto wykonać ręczne porządki, jeśli są potrzebne.

## Dobre praktyki przy użyciu modułu

- przygotuj osobne treści dla desktopu i mobile tam, gdzie układ wymaga innego zachowania,
- używaj tłumaczeń dla elementów widocznych dla klienta,
- trzymaj porządek w kolejności bloków na `displayHome`,
- po zmianie grafik sprawdzaj także wariant mobilny,
- jeśli korzystasz z własnych hooków, nadaj im stabilne i czytelne nazwy.

## Pliki, od których warto zacząć

- [hummingbird_editor.php](hummingbird_editor.php)
- [controllers/admin/AdminHbEditorController.php](controllers/admin/AdminHbEditorController.php)
- [views/templates/admin/main.tpl](views/templates/admin/main.tpl)
- [views/css/admin.css](views/css/admin.css)
- [views/css/front.css](views/css/front.css)
- [views/js/admin.js](views/js/admin.js)
- [views/js/slider.js](views/js/slider.js)

## Wersja

Aktualna wersja modułu: `1.1.0`.
