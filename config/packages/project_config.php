<?php
date_default_timezone_set("UTC");

$host = isSet($_SERVER['HTTP_HOST']) ?? '';
$host = strToLower($host);

$configProjectBase  = [];
$configProject = [];

if (file_exists( __DIR__ . '/../project/_base.php')) {
	$configProject = include( __DIR__ . '/../project/_base.php');
}

if (!$configProject) {
    echo 'Config Project File not found';
    exit();
}

foreach ($configProject['services_public_dir_parameters'] AS $key => $val) {
	$container->setParameter($key, $_SERVER['DOCUMENT_ROOT'] . $val);
}

// READ THE FILE /.layout AND SET THE LAYOUT VARIABLES ------------------------------------------- //
$arLayoutSettingsVars = [];
$layoutSettings = @file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/.layout');
if ($layoutSettings && is_integer(strpos($layoutSettings, "\n"))) {
    $arLayoutSettings = explode("\n", $layoutSettings);
    foreach ($arLayoutSettings AS $line) {
        $line = trim($line);
        if ($line && substr($line, 0, 1) !== '#') {
            if (is_integer(strpos($line, '='))) {
                $lineA = substr($line, 0, strpos($line, '='));
                $lineB = substr($line, strpos($line, '=') + 1);
                $arLayoutSettingsVars[$lineA] = $lineB;
            }
        }
    }
}

$container->setParameter('QUERYBIZ_LAYOUT', $arLayoutSettingsVars);

// READ THE FILE /.controller AND SET THE CONTROLLER VARIABLES ------------------------------------- //
$arControllerSettingsVars = [];
$controllerSettings = @file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/.controller');
if ($controllerSettings && is_integer(strpos($controllerSettings, "\n"))) {
    $arControllerSettings = explode("\n", $controllerSettings);
    foreach ($arControllerSettings AS $line) {
        $line = trim($line);
        if ($line && substr($line, 0, 1) !== '#') {
            if (is_integer(strpos($line, '='))) {
                $lineA = substr($line, 0, strpos($line, '='));
                $lineB = substr($line, strpos($line, '=') + 1);
                $arControllerSettingsVars[$lineA] = $lineB;
            }
        }
    }
}

$container->setParameter('QUERYBIZ_CONTROLLER', $arControllerSettingsVars);

// READ THE FILE /.settings AND SET THE CONTROLLER VARIABLES ------------------------------------- //
$arControllerSettingsVars = [];
$controllerSettings = @file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/.settings');
if ($controllerSettings && is_integer(strpos($controllerSettings, "\n"))) {
    $arControllerSettings = explode("\n", $controllerSettings);
    foreach ($arControllerSettings AS $line) {
        $line = trim($line);
        if ($line && substr($line, 0, 1) !== '#') {
            if (is_integer(strpos($line, '='))) {
                $lineA = substr($line, 0, strpos($line, '='));
                $lineB = substr($line, strpos($line, '=') + 1);
                $arControllerSettingsVars[$lineA] = $lineB;
            }
        }
    }
}

$container->setParameter('QUERYBIZ_SETTINGS', $arControllerSettingsVars);

// ----------------------------------------------------------------------------------------------- //