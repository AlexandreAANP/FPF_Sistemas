<?php
$uri = $_SERVER['REQUEST_URI'];

// READ THE FILE /.env AND SET THE LAYOUT VARIABLES ------------------------------------------- //
$arEnvVars = [];
$envSettings = @file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/.env');
if ($envSettings && is_integer(strpos($envSettings, "\n"))) {
    $arEnvSettings = explode("\n", $envSettings);
    foreach ($arEnvSettings AS $line) {
        $line = trim($line);
        if ($line && substr($line, 0, 1) !== '#') {
            if (is_integer(strpos($line, '='))) {
                $lineA = substr($line, 0, strpos($line, '='));
                $lineB = substr($line, strpos($line, '=') + 1);
                $arEnvVars[$lineA] = $lineB;
            }
        }
    }
}

// READ THE FILE /.env AND SET THE LAYOUT VARIABLES ------------------------------------------- //
$arSettingsVars = [];
$envSettings = @file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/.settings');
if ($envSettings && is_integer(strpos($envSettings, "\n"))) {
    $arEnvSettings = explode("\n", $envSettings);
    foreach ($arEnvSettings AS $line) {
        $line = trim($line);
        if ($line && substr($line, 0, 1) !== '#') {
            if (is_integer(strpos($line, '='))) {
                $lineA = substr($line, 0, strpos($line, '='));
                $lineB = substr($line, strpos($line, '=') + 1);
                $arSettingsVars[$lineA] = $lineB;
            }
        }
    }
}

// Configure -------------------------------- //
$prevModule = ''; // nome da pasta onde o site está. Ex: /site

$defaultLanguage = 'en';
$arSupportedLocales = ['en'];
if (array_key_exists('DEFAULT_LANGUAGE', $arEnvVars) && array_key_exists('INITIAL_LANGUAGE', $arSettingsVars)) {
    include_once(dirname($_SERVER['DOCUMENT_ROOT']) . '/src/Service/SettingsService.php');
    $objSettingsService = new \App\Service\SettingsService();

    $arInitialLanguage = $objSettingsService->getSettingsVars('INITIAL_LANGUAGE', $arSettingsVars);

    $initialLanguage = $arInitialLanguage[0];
    $defaultLanguage = $arEnvVars['DEFAULT_LANGUAGE'];
    $arSupportedLocales = $arEnvVars['SUPPORTED_LOCALES'];
}

if (is_integer(strpos($arSupportedLocales, '|'))) {
    $arSupportedLocales = explode('|', $arSupportedLocales);
}

// Module ----------------------------------- //
$languageBase = '';
$languageBaseError = '';
if (substr($uri, 0, 1) === '/' && substr($uri, 3, 1) === '/') {
    if (in_array(substr($uri, 1, 2), $arSupportedLocales)) {
        $languageBase = substr($uri, 1, 2);
    } else {
        $languageBaseError = substr($uri, 1, 2);
    }
}

if ($languageBase) {
    $prevModule = '/' . $languageBase . $prevModule;
}

if (substr($uri, 0, strlen($prevModule)) == $prevModule) {
    $uri = substr($uri, strlen($prevModule));
}

if ($languageBaseError) {
    $redir = substr($uri, 3);
    header('location: ' . $redir);
    exit();
}

$uriLanguageBase = '';
if ($languageBase) {
    $uriLanguageBase = $languageBase;
} else {
    $languageBase = $defaultLanguage;
}

define('SITE_LANGUAGE_BASE', $languageBase);

$uri = trim($uri, '/');

if (!$uri) {
    $uri = 'home';
}

$uri_page = $uri;
if (is_integer(strpos($uri, '?'))) {
    $uri_vars = substr($uri, strpos($uri, '?') + 1);
    $uri_page = substr($uri, 0, strpos($uri, '?'));
    $uri_page = trim($uri_page, '/');

    $uri_vars = urlencode($uri_vars);
    $uri_vars = base64_encode($uri_vars);

    $uri = $uri_page . '_vars_' . $uri_vars;
}

if ($uri_page == '' || $uri_page == 'home') {
    if ($uriLanguageBase == '' && $languageBase !== $initialLanguage) {
        header('location: /' . $initialLanguage);
        die;
    }
}

if ($uri) {
    $filepath = $_SERVER['DOCUMENT_ROOT'] . '/data/site/html/' . $languageBase . '/' . $uri . '.html';

    if (file_exists($filepath) && is_file($filepath)) {
        include($filepath);
        exit();
    }
}