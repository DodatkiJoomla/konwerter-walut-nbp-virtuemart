<?php
/**
 * @copyright Copyright (c) 2014 DodatkiJoomla.pl
 * @license GNU/GPL v2
 */

defined('_JEXEC') or die('Restricted access');
jimport('joomla.installer.installer');
jimport('joomla.filesystem.file');
$japp = new JApplication(array('clientId' => 1));


// pobranie ścieżki
$src = $this->parent->getPath('source');
$errors = "";
$link = "";

// sprawdź czy VM 1
if (file_exists(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'virtuemart.cfg.php')) {
    // kopiuj
    if (!JFile::copy($src . DS . 'kupno.php',
        JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'classes' . DS . 'currency' . DS . 'convertNBP_Kursy_Kupna.php')
    ) {
        $errors .= "Nie można skopiować plików. Zmień ustawienia CHMOD na 755 dla katalogu '/components/com_virtuemart/classes/currency'. ";
    }
    JFile::copy($src . DS . 'sprzedaz.php',
        JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'classes' . DS . 'currency' . DS . 'convertNBP_Kursy_Sprzedazy.php');
    JFile::copy($src . DS . 'srednie.php',
        JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'classes' . DS . 'currency' . DS . 'convertNBP_Kursy_Srednie.php');

    // usunięcie wpisu z bazy
    $address = explode(DS, $this->parent->getPath('extension_site'));
    $address = array_pop($address);
    $db = &JFactory::getDBO();
    $query = " delete from #__components where name like '%" . str_replace("com_", "", $address) . "%' ";
    $db->setQuery($query);
    $db->query();

    $link = 'index.php?pshop_mode=admin&page=admin.show_cfg&option=com_virtuemart';
} else {
    // kopiuj do VM 2 i "wyżej"
    if (!JFile::copy($src . DS . 'kupno.php',
        JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'plugins' . DS . 'currency_converter' . DS . 'convertNBP_Kursy_Kupna.php')
    ) {
        $errors .= "Nie można skopiować plików. Zmień ustawienia CHMOD na 755 dla katalogu '/components/com_virtuemart/plugins/currency_converter'. ";
    }
    JFile::copy($src . DS . 'sprzedaz.php',
        JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'plugins' . DS . 'currency_converter' . DS . 'convertNBP_Kursy_Sprzedazy.php');
    JFile::copy($src . DS . 'srednie.php',
        JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'plugins' . DS . 'currency_converter' . DS . 'convertNBP_Kursy_Srednie.php');

    if (version_compare(JVERSION, '1.6.0', 'ge')) {
    } else {
        // usunięcie wpisu z bazy
        $address = explode(DS, $this->parent->getPath('extension_site'));
        $address = array_pop($address);
        $db = &JFactory::getDBO();
        $query = " delete from #__components where name like '%" . str_replace("com_", "", $address) . "%' ";
        $db->setQuery($query);
        $db->query();
    }
    $link = 'index.php?option=com_virtuemart&view=config';
}

// błędy
if (empty($errors)) {
    // przekierowanie i exit, zeby nie wykonywały się dalsze operacje (m.in. tworzenie tabeli komponentu w J 1.7)
    $this->parent->abort();
    $japp->redirect($link,
        "Konwerter walut NBP dla Virtuemart został zainstalowany poprawnie. </li><li> W poniższym oknie możesz go ustawić jako domyślny. ");
    exit();
} else {
    // błędne przekierowanie
    $this->parent->abort();
    $japp->redirect('index.php?option=com_installer', $errors, 'error');
    exit();
}