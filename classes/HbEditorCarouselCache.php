<?php
declare(strict_types=1);
/**
 * Hummingbird Editor — dyskowy cache karuzel produktowych ze strony glownej
 * (sekcje `products`, przeniesione tu z modulu multislider).
 *
 * Zbudowanie jednej karuzeli to wyszukiwanie produktow w kategorii plus
 * przepuszczenie kazdego trafienia przez presenter rdzenia. Przy kilkunastu
 * karuzelach na stronie glownej to pochlania wiekszosc czasu zadania, wiec
 * gotowy HTML ladzie na dysku i odswieza sie co TTL (domyslnie raz na dobe)
 * zamiast przy kazdym wejsciu.
 *
 * Zapisany fragment jest **neutralny wzgledem klienta**: token CSRF formularza
 * "do koszyka" ($static_token) to jedyna wartosc zalezna od zalogowanego
 * klienta, jaka miniaturka produktu osadza w HTML — przed zapisem podmieniamy
 * go na znacznik, a przy odczycie wstawiamy token biezacego odwiedzajacego.
 * Bez tego cache jednego klienta trafilby do innych.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorCarouselCache
{
    /** Zastepuje $static_token w zapisanym HTML — patrz docblock klasy. */
    const TOKEN_PLACEHOLDER = '{{HBE_STATIC_TOKEN}}';

    const CONF_ENABLED   = 'HBE_CAROUSEL_CACHE';
    const CONF_TTL       = 'HBE_CAROUSEL_CACHE_TTL';
    const CONF_VARIANTS  = 'HBE_CAROUSEL_VARIANTS';
    const CONF_LAZY      = 'HBE_CAROUSEL_LAZY';
    const CONF_EAGER     = 'HBE_CAROUSEL_EAGER';
    const CONF_WARM_KEY  = 'HBE_CAROUSEL_WARM_KEY';

    /** Doba — tyle zyje wpis, zanim zostanie przebudowany. */
    const DEFAULT_TTL = 86400;

    /** Ile wariantow trzymamy dla karuzeli z losowa kolejnoscia. */
    const DEFAULT_VARIANTS = 3;

    /** Ile karuzel renderuje sie w HTML strony, zanim wlaczy sie doladowywanie. */
    const DEFAULT_EAGER = 1;

    /**
     * Gdy wpis wygasnie, pierwsze zadanie przesuwa jego mtime o tyle sekund do
     * przodu i dopiero wtedy przebudowuje. Rownolegle zadania widza wpis jako
     * swiezy i dostaja stara tresc, zamiast rzucac sie wszystkie na odbudowe.
     */
    const REBUILD_GRACE = 60;

    /* ── Ustawienia ───────────────────────────────────────────────────────── */

    public static function isEnabled(): bool
    {
        $v = Configuration::get(self::CONF_ENABLED);

        return $v === false ? true : (bool) (int) $v;
    }

    public static function lazyEnabled(): bool
    {
        $v = Configuration::get(self::CONF_LAZY);

        return $v === false ? true : (bool) (int) $v;
    }

    public static function ttl(): int
    {
        $ttl = (int) Configuration::get(self::CONF_TTL);

        return $ttl > 0 ? $ttl : self::DEFAULT_TTL;
    }

    public static function variants(): int
    {
        $n = (int) Configuration::get(self::CONF_VARIANTS);

        return $n > 0 ? min($n, 10) : self::DEFAULT_VARIANTS;
    }

    /** Ile karuzel produktowych renderuje sie od razu w HTML strony glownej. */
    public static function eagerCount(): int
    {
        $v = Configuration::get(self::CONF_EAGER);

        return $v === false ? self::DEFAULT_EAGER : max(0, (int) $v);
    }

    /**
     * Sekret do wymuszenia odbudowy z crona (?warm=...). Generowany przy
     * pierwszym uzyciu, zeby endpoint doladowywania nie byl darmowa dzwignia
     * do obciazania sklepu przez kogokolwiek z internetu.
     */
    public static function warmKey(): string
    {
        $key = (string) Configuration::get(self::CONF_WARM_KEY);
        if ($key === '') {
            $key = bin2hex(random_bytes(16));
            Configuration::updateValue(self::CONF_WARM_KEY, $key);
        }

        return $key;
    }

    /* ── Sciezki ──────────────────────────────────────────────────────────── */

    /**
     * Katalog cache. Celowo poza `var/cache/` — czyszczenie cache PrestaShopa
     * (a robi to wiele akcji w panelu) nie moze kasowac karuzel, bo kazde takie
     * czyszczenie oznaczaloby kilkanascie sekund odbudowy dla pierwszego gosca.
     */
    public static function dir(): string
    {
        return _PS_ROOT_DIR_ . '/var/hbe_carousel/';
    }

    private static function ensureDir(): bool
    {
        $dir = self::dir();
        if (is_dir($dir)) {
            return true;
        }
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        // Fragmenty sa publiczna trescia strony, ale katalog lezy w docroocie —
        // nie ma powodu, zeby dalo sie je pobrac z pominieciem sklepu.
        @file_put_contents($dir . '.htaccess', "Order deny,allow\nDeny from all\n");
        @file_put_contents($dir . 'index.php', "<?php header('HTTP/1.0 404 Not Found');\n");

        return true;
    }

    /**
     * Sciezka pliku dla jednej karuzeli. Klucz obejmuje wszystko, co zmienia
     * wyrenderowany HTML: sklep, jezyk, waluta, kraj (podatek) i grupa klienta
     * (ceny), plus odcisk konfiguracji bloku — dzieki niemu edycja karuzeli w
     * edytorze daje nowy klucz i zmiana jest widoczna od razu, bez czyszczenia.
     */
    public static function fileForBlock(int $idBlock, string $sectionData, int $variant = 0): string
    {
        return self::dir() . 'b' . $idBlock . '_' . self::hash([
            'block',
            $sectionData,
            (int) $variant,
        ]) . '.html';
    }

    /**
     * Sciezka dla nadpisania zrodla karuzeli motywu (HBE_NP/BS/CP_CATEGORY_ID) —
     * tu cache'ujemy zserializowana liste produktow, nie HTML.
     */
    public static function fileForOverride(int $idCategory, int $limit, int $variant = 0): string
    {
        return self::dir() . 'o' . $idCategory . '_' . self::hash([
            'override',
            (int) $limit,
            (int) $variant,
        ]) . '.data';
    }

    /** @param array<int,mixed> $parts */
    private static function hash(array $parts): string
    {
        $ctx = Context::getContext();

        $group = 0;
        if ($ctx !== null) {
            $current = Group::getCurrent();
            $group = $current ? (int) $current->id : 0;
        }

        // v2: HTML niesie data-hbe-ids (wykluczanie produktow z karuzel wyzej).
        array_unshift(
            $parts,
            'v2',
            $ctx ? (int) $ctx->shop->id : 0,
            $ctx ? (int) $ctx->language->id : 0,
            $ctx && $ctx->currency ? (int) $ctx->currency->id : 0,
            $ctx && $ctx->country ? (int) $ctx->country->id : 0,
            $group
        );

        return sha1(implode('|', array_map('strval', $parts)));
    }

    /* ── Odczyt / zapis ───────────────────────────────────────────────────── */

    /**
     * Zwraca zawartosc wpisu albo null, gdy go nie ma lub wygasl.
     * Pusty string jest poprawna wartoscia (karuzela bez produktow) — dlatego
     * "brak" sygnalizuje null, a nie pusty ciag.
     */
    public static function get(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }
        if (time() - (int) @filemtime($file) > self::ttl()) {
            return null;
        }
        $data = @file_get_contents($file);

        return $data === false ? null : $data;
    }

    /** Zawartosc wpisu bez sprawdzania waznosci — do serwowania starej tresci. */
    public static function getStale(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }
        $data = @file_get_contents($file);

        return $data === false ? null : $data;
    }

    /**
     * Rezerwuje prawo do przebudowy wygaslego wpisu.
     *
     * Zwraca true, gdy to zadanie ma odbudowac wpis. Przy false ktos inny wlasnie
     * to robi i nalezy podac stara tresc z getStale(). Chroni przed sytuacja, w
     * ktorej po wygasnieciu TTL kilkunastu jednoczesnych gosci naraz odpala
     * pelne renderowanie tych samych karuzel.
     */
    public static function claimRebuild(string $file): bool
    {
        if (!is_file($file)) {
            return true;
        }
        $mtime = (int) @filemtime($file);
        if (time() - $mtime <= self::ttl()) {
            // Ktos zdazyl odswiezyc miedzy naszym get() a tym sprawdzeniem.
            return false;
        }

        return (bool) @touch($file, time() - self::ttl() + self::REBUILD_GRACE);
    }

    /** Zapis atomowy — czytelnicy nigdy nie zobacza polowy pliku. */
    public static function set(string $file, string $contents): bool
    {
        if (!self::ensureDir()) {
            return false;
        }
        $tmp = $file . '.' . getmypid() . '.' . mt_rand(1000, 9999) . '.tmp';
        if (@file_put_contents($tmp, $contents) === false) {
            @unlink($tmp);

            return false;
        }
        @chmod($tmp, 0644);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }

    /* ── Token klienta ────────────────────────────────────────────────────── */

    /** Token biezacego odwiedzajacego -> znacznik (przed zapisem). */
    public static function detokenize(string $html): string
    {
        $token = (string) Tools::getToken(false);

        return $token === '' ? $html : str_replace($token, self::TOKEN_PLACEHOLDER, $html);
    }

    /** Znacznik -> token biezacego odwiedzajacego (przy serwowaniu). */
    public static function retokenize(string $html): string
    {
        return str_replace(self::TOKEN_PLACEHOLDER, (string) Tools::getToken(false), $html);
    }

    /* ── Czyszczenie ──────────────────────────────────────────────────────── */

    /**
     * Kasuje wpisy: wszystkie, albo tylko jednego bloku.
     *
     * @return int liczba usunietych plikow
     */
    public static function purge(?int $idBlock = null): int
    {
        $pattern = self::dir() . ($idBlock === null ? '*' : 'b' . (int) $idBlock . '_*');
        $removed = 0;
        foreach ((array) glob($pattern) as $file) {
            if (is_file($file) && basename($file) !== 'index.php' && basename($file) !== '.htaccess' && @unlink($file)) {
                ++$removed;
            }
        }

        return $removed;
    }

    /**
     * Sprzata wpisy, po ktore nikt juz nie siega — po edycji karuzeli jej stary
     * klucz przestaje byc uzywany i plik zostalby na dysku na zawsze.
     *
     * @return int liczba usunietych plikow
     */
    public static function purgeExpired(): int
    {
        $deadline = time() - (self::ttl() * 2);
        $removed = 0;
        foreach ((array) glob(self::dir() . '*.{html,data,tmp}', GLOB_BRACE) as $file) {
            if (is_file($file) && (int) @filemtime($file) < $deadline && @unlink($file)) {
                ++$removed;
            }
        }

        return $removed;
    }

    /**
     * Podsumowanie dla panelu: liczba wpisow, rozmiar i wiek najstarszego.
     *
     * @return array{files:int,bytes:int,oldest:?int}
     */
    public static function stats(): array
    {
        $files = 0;
        $bytes = 0;
        $oldest = null;
        foreach ((array) glob(self::dir() . '*.{html,data}', GLOB_BRACE) as $file) {
            if (!is_file($file)) {
                continue;
            }
            ++$files;
            $bytes += (int) @filesize($file);
            $mtime = (int) @filemtime($file);
            if ($oldest === null || $mtime < $oldest) {
                $oldest = $mtime;
            }
        }

        return ['files' => $files, 'bytes' => $bytes, 'oldest' => $oldest];
    }
}
