<?php
declare(strict_types=1);
/**
 * Endpoint doladowywania karuzel produktowych ze strony glownej.
 *
 * Zwraca JSON {blocks: {id: html}}. Przyjmuje kilka id naraz (`ids=2,3,4`) i
 * wariant losowania strony (`v=0..n`), bo kazde zadanie
 * to pelny start PrestaShopa — pociagniecie szesciu karuzel jednym zapytaniem
 * kosztuje serwer szescio razy mniej niz szesc osobnych zapytan. Tresc niemal
 * zawsze idzie prosto z cache (HbEditorCarouselCache).
 *
 * ?warm=<klucz> wymusza przebudowe cache z pominieciem TTL — do wolania z crona.
 * Bez klucza tego trybu nie da sie uzyc, inaczej kazdy z internetu moglby
 * kazac sklepowi przeliczac karuzele w kolko.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Hummingbird_editorCarouselModuleFrontController extends ModuleFrontController
{
    /** Bez tego PrestaShop probowalby zlozyc naglowek i stopke strony. */
    public $ajax = true;

    /** Gorna granica na jedno zadanie — trzyma koszt w ryzach. */
    const MAX_IDS = 20;

    public function initContent()
    {
        // Karuzela renderuje sie szablonami motywu (productlist -> miniatura
        // produktu), a te licza na komplet zmiennych, ktore rdzen przypisuje
        // przy skladaniu pelnej strony: $urls, $static_token i reszta. Bez tego
        // wywolania karty wychodza z pustym tokenem "do koszyka" — i taki HTML
        // wladowalby sie do cache.
        $this->assignGeneralPurposeVariables();

        $warmKey = trim((string) Tools::getValue('warm', ''));
        $isWarmup = $warmKey !== '' && hash_equals(HbEditorCarouselCache::warmKey(), $warmKey);

        if ($warmKey !== '' && !$isWarmup) {
            $this->respond(['error' => 'invalid warm key'], 403);
        }

        $ids = $this->requestedIds($isWarmup);

        // Wariant losowania tej wizyty (?v=) — strona przekazuje swoj, zeby karuzele
        // doladowane przy scrollu wykluczaly dokladnie te produkty, ktore gosc
        // widzi w karuzelach wyzej. Bez parametru modul losuje sam.
        $variant = (string) Tools::getValue('v', '');
        if ($variant !== '' && ctype_digit($variant)) {
            $this->module->setCarouselVariant((int) $variant);
        }

        if ($isWarmup) {
            $built = 0;
            foreach ($ids as $id) {
                $built += $this->module->warmProductsCarousel($id);
            }
            // Edycja karuzeli zmienia klucz cache, wiec stary plik nikomu juz nie
            // sluzy — sprzatamy przy okazji rozgrzewania, nie w zadaniu goscia.
            $removed = HbEditorCarouselCache::purgeExpired();

            $this->respond([
                'warmed'  => count($ids),
                'entries' => $built,
                'removed' => $removed,
            ]);
        }

        $blocks = [];
        foreach ($ids as $id) {
            $blocks[(string) $id] = $this->module->renderProductsCarousel($id);
        }

        $this->respond(['blocks' => $blocks]);
    }

    /**
     * Id z zapytania: `ids=3,4,5`. Przy rozgrzewaniu `ids=all` (lub brak) bierze
     * wszystkie aktywne karuzele sklepu.
     *
     * @return int[]
     */
    private function requestedIds(bool $isWarmup): array
    {
        $raw = trim((string) Tools::getValue('ids', ''));

        if ($isWarmup && ($raw === '' || $raw === 'all')) {
            return $this->module->getProductsCarouselIds();
        }

        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $ids = array_values($ids);

        return $isWarmup ? $ids : array_slice($ids, 0, self::MAX_IDS);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function respond(array $payload, int $status = 200): void
    {
        header('Content-Type: application/json; charset=utf-8', true, $status);
        // Fragmenty niosa token koszyka zalogowanego klienta — nie moga wyladowac
        // w cache przegladarki ani posrednika.
        header('Cache-Control: private, no-store, max-age=0');

        $this->ajaxRender((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        exit;
    }
}
