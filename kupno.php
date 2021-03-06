<?php

/**
 * @copyright Copyright (c) 2014 DodatkiJoomla.pl
 * @license GNU/GPL v2
 */
if (!defined('_VALID_MOS') && !defined('_JEXEC')) {
    die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');
}

class convertNBP_Kursy_Kupna
{
    public $archive = true;
    public $last_updated = '';
    public $ostatnie_kursy = 'http://www.nbp.pl/kursy/xml/LastC.xml';
    public $info = 'http://www.nbp.pl/home.aspx?f=/statystyka/kursy.html';
    public $dostawca = 'Narodowy Bank Polski';

    public function init()
    {
        global $vendor_currency, $vmLogger;

        // ustawienie daty odnowienia cachu
        date_default_timezone_set('Poland');
        $teraz = time();
        $jutro = $teraz + (24 * 3600);
        $jutro = mktime(0, 0, 0, date("n", $jutro), date("j", $jutro), date("Y", $jutro));
        $roznica = $jutro - $teraz;

        // cachowanie
        $cache = &JFactory::getCache();
        $cache->setCaching(1);
        $cache->setLifeTime($roznica);
        $currencies = $cache->call(array('convertNBP_Kursy_Kupna', 'getWaluty'), $this->ostatnie_kursy);

        if ($currencies != false) {
            $GLOBALS['converter_array'] = $currencies;
        } else {
            $GLOBALS['converter_array'] = -1;
            $vmLogger->err('Failed to retrieve the Currency Converter XML document.');
            $_SESSION['product_currency'] = $GLOBALS['product_currency'] = $vendor_currency;
            return false;
        }

        return true;

    }

    public function getWaluty($adres)
    {
        $contents = false;

        // pobierz kursy
        if (function_exists('curl_init')) {
            $curl = curl_init($adres);

            // nie wypluwaj!
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (curl_exec($curl) === false) {
                $contents = false;
            } else {
                $contents = curl_exec($curl);
            }
            curl_close($curl);
        } else {
            $contents = file_get_contents($adres);
        }

        // sprawdź czy pobrane
        if ($contents != false) {
            // kodowanie
            $contents = str_replace("ISO-8859-2", "UTF-8", $contents);
            $contents = str_replace(",", ".", $contents);
            $contents = iconv("ISO-8859-2", "UTF-8", $contents);

            $kursy = new SimpleXMLElement($contents);
            $zloty = $kursy->addChild('pozycja');
            $zloty->addChild('nazwa_waluty', 'polski złoty');
            $zloty->addChild('przelicznik', '1');
            $zloty->addChild('kod_waluty', 'PLN');
            $zloty->addChild('kurs_kupna', '1');
            $zloty->addChild('kurs_sprzedazy', '1');

            $lista_walut = $kursy->children();
            $waluty = array();

            foreach ($lista_walut as $wartosc) {
                if (count($wartosc) == 5) {
                    $kod = (string)$wartosc->kod_waluty;
                    $kurs = (string)((float)$wartosc->kurs_kupna / (float)$wartosc->przelicznik);
                    $waluty[$kod] = $kurs;
                    unset($wartosc);
                }
            }
        } else {
            $GLOBALS['converter_array'] = -1;
            $vmLogger->err('Failed to retrieve the Currency Converter XML document.');
            $_SESSION['product_currency'] = $GLOBALS['product_currency'] = $vendor_currency;
            return false;
        }
        return $waluty;
    }

    public function convert($cena, $walutaA = '', $walutaB = '')
    {
        global $vendor_currency;

        if (!$walutaA) {
            $walutaA = $vendor_currency;
        }
        if (!$walutaB) {
            $walutaB = $GLOBALS['product_currency'];
        }

        if ($walutaA == $walutaB) {
            return $cena;
        }
        if (!$this->init()) {
            $GLOBALS['product_currency'] = $vendor_currency;
            return $cena;
        }

        $wartoscA = isset($GLOBALS['converter_array'][$walutaA]) ? $GLOBALS['converter_array'][$walutaA] : 1;
        $wartoscB = isset($GLOBALS['converter_array'][$walutaB]) ? $GLOBALS['converter_array'][$walutaB] : 1;

        $wartosc = $cena * $wartoscA / $wartoscB;

        return $wartosc;
    }
}

?>
