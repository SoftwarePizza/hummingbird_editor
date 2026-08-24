<?php
/**
 * 1.23.1 — rabat za calosc nie znikal juz miedzy zamowieniem a bramka.
 *
 * Montonio (MontonioBeforePaymentOrderProcessing) najpierw robi validateOrder(),
 * co odejmuje stan magazynowy, i dopiero potem liczy kwote do pobrania przez
 * $cart->getOrderTotal(). Przy drugim przeliczeniu stanu juz nie ma, warunek
 * "klient bierze calosc" nie zachodzi i z ceny znikal rabat: zamowienie 18717
 * na 336,53 zl, pobrane 354,24 zl (dokladnie 336,53 / 0,95).
 *
 * Poprawka jest w samym kodzie — stan liczymy teraz razem z tym, co z magazynu
 * zdjelo zamowienie zlozone z tego samego koszyka. Nie ma tu nic do zrobienia
 * w bazie; plik istnieje po to, zeby PrestaShop podniosl numer wersji.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_23_1($module)
{
    return true;
}
