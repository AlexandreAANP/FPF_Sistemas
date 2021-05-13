<?php

namespace App\Controller;

use App\Service\SettingsService;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class SettingsTranslationFileController extends SiteCacheController
{
    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/getSettingsTranslationFile", name="frontoffice_get_api_settings_translation_file", methods={"POST"})
     */
    public function getSettingsTranslationFile()
    {
        $arContent = $this->getApiSettingsTranslationFile();
        $arContent = $this->organizeTranslationFileContent($arContent);

        $this->saveTranslationFileContent($arContent);

        return $this->json(['return' => 'success']);
    }

    function getApiSettingsTranslationFile() {
        $arContent = [];

        $domainName = $this->objSettingsService->getEnvVars('DOMAIN_NAME');
        if ($data = $this->getAPIData($this->apiUrl . '/api/getSettingsTranslationFile?domain=' . $domainName)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('colSettingsTranslationFile', $objData)) {
                    $arContent = $objData['colSettingsTranslationFile'];
                }
            }
        }
        return $arContent;
    }

    function saveTranslationFileContent($arContent) {
        $arContentSettings = $arContent[0];
        $arContentMessages = isSet($arContent[1]) ? $arContent[1] : null;

        $arContentFile = [];
        foreach ($arContentSettings AS $filename => $arContent) {
            $file = '.' . $filename;

            $text = '';
            foreach ($arContent AS $key => $arVal) {
                $text .= $key . '=';

                $line = 0;
                foreach ($arVal AS $val) {
                    if ($line > 0) {
                        $text .= '|';
                    }

                    $val = str_replace('"', '`', $val);

                    $text .= '"' . $val . '"';

                    $line++;
                }
                $text .= "\n";
            }

            $arContentFile[$file] = $text;
        }

        $arMessageFile = [];
        $arJavascriptFile = [];
        if ($arContentMessages) {
            foreach ($arContentMessages AS $filename => $arContent) {
                if ($filename === 'javascript') {
                    foreach ($arContent AS $lang => $arVal) {
                        $text = "arDefaultOptions['translationFiles']['" . $lang . "'] = {\n";
                        foreach ($arVal AS $key => $val) {
                            if (is_integer(strpos($val, '\''))) {
                                $val = str_replace('\'', '`', $val);
                            }
                            $text .= '\'' . $key . '\': \'' . $val . '\',' . "\n";
                        }
                        $text .= "};\n";

                        $arJavascriptFile['messages.' . $lang . '.js'] = $text;
                    }

                } else if ($filename === 'twig') {
                    foreach ($arContent AS $lang => $arVal) {
                        $text = '';
                        foreach ($arVal AS $key => $val) {
                            $text .= $key . ': ' . $val . "\n";
                        }

                        $arMessageFile['messages.' . $lang . '.yaml'] = $text;
                    }
                }
            }
        }

        if ($arContentFile) {
            foreach ($arContentFile AS $filename => $content) {
                $filepath = dirname($_SERVER['DOCUMENT_ROOT']) . '/' . $filename;
                $fo = fopen($filepath, 'w') or die('Unable to open file!');
                fwrite($fo, $content);
                fclose($fo);
            }
        }

        if ($arMessageFile) {
            foreach ($arMessageFile AS $filename => $content) {
                $filepath = dirname($_SERVER['DOCUMENT_ROOT']) . '/translations/' . $filename;
                $fo = fopen($filepath, 'w') or die('Unable to open file!');
                fwrite($fo, $content);
                fclose($fo);
            }
        }

        if ($arJavascriptFile) {
            foreach ($arJavascriptFile AS $filename => $content) {
                $filepath = dirname($_SERVER['DOCUMENT_ROOT']) . '/public/assets/js/translations/' . $filename;
                $fo = fopen($filepath, 'w') or die('Unable to open file!');
                fwrite($fo, $content);
                fclose($fo);
            }
        }

        $filepath = dirname($_SERVER['DOCUMENT_ROOT']) . '/var';
        $projectDocRoot = $this->objSettingsService->getEnvVars('PROJECT_DOC_ROOT');
        if ($filepath === $projectDocRoot . '/var') {
            shell_exec('rm -rf ' . $filepath);
        }
    }

    function organizeTranslationFileContent($arContent) {
        $arRet = [];

        foreach ($arContent AS $content) {
            $filename = $content['fileName'];
            $referenceKey = $content['referenceKey'];
            $name = $content['name'];
            $translationCode = $content['translationCode'];

            $isMessage = $content['isMessage'];

            if (!array_key_exists($isMessage, $arRet)) {
                $arRet[$isMessage] = [];
            }

            if (!array_key_exists($filename, $arRet[$isMessage])) {
                $arRet[$isMessage][$filename] = [];
            }

            if ($isMessage) {
                if (!array_key_exists($translationCode, $arRet[$isMessage][$filename])) {
                    $arRet[$isMessage][$filename][$translationCode] = [];
                }

                if (!array_key_exists($referenceKey, $arRet[$isMessage][$filename][$translationCode])) {
                    $arRet[$isMessage][$filename][$translationCode][$referenceKey] = $name;
                }

            } else {
                if (!array_key_exists($referenceKey, $arRet[$isMessage][$filename])) {
                    $arRet[$isMessage][$filename][$referenceKey] = [];
                }

                if (!array_key_exists($translationCode, $arRet[$isMessage][$filename][$referenceKey])) {
                    $arRet[$isMessage][$filename][$referenceKey][$translationCode] = $name;
                }

            }
        }

        return $arRet;
    }

    /**
     * @Route("/getSqlDebugTranslation", name="frontoffice_get_sql_debug_translation", methods={"GET"})
     */
    public function getSqlDebugTranslation(Request $request)
    {
        $arMessage = $this->getApiSettingsTranslationFile();
        if ($arMessage) {
            $arMessage = array_map(function($ar) {
                return $ar['referenceKey'];
            }, $arMessage);

            $arMessage = array_unique($arMessage);
        }

        $fileId = $request->get('file', 3);
        $trans = $request->get('trans', '1,2');
        $dbid = $request->get('dbid', '1');

        $filename = $filepath = dirname($_SERVER['DOCUMENT_ROOT']) . '/translations.txt';
        if (!is_file($filename)) {
            dd('Run: php bin/console debug:translation pt > translations.txt');
        }
        $content = file_get_contents($filename);
        $arContent = explode("\n", $content);

        echo '<b>1) Informe um queryparameter (?file=' . $fileId . ') para o `settings_translation_file_content_id` do File `message`</b><BR>';
        echo '<b>2) Informe um queryparameter (&trans=' . $trans . ') com os IDs da tabela `translation`, ex: 1,2</b><BR>';
        echo '<b>3) Informe um queryparameter (&dbid=' . $dbid . ') com o ID inicial / do último registro da tabela `settings_translation_file_content.id`</b><BR>';
        echo '<HR>';

        $arTrans = explode(',', $trans);

        $arColumnLength = [];
        foreach ($arContent AS $i => $line) {
            $line = trim($line);

            if ($i > 2 && $i + 3 < count($arContent)) {
                if (substr($line, 0, 8) === 'messages') {
                    $col01 = '';
                    $col02 = substr($line, $arColumnLength[0], $arColumnLength[1]);
                    $col03 = substr($line, $arColumnLength[1], $arColumnLength[2]);
                } else {
                    $col01 = substr($line, 0, $arColumnLength[0]);
                    $col02 = substr($line, $arColumnLength[0], $arColumnLength[1]);
                    $col03 = substr($line, $arColumnLength[0] + $arColumnLength[1], $arColumnLength[2]);
                }

                /*
                echo '<pre>';
                echo $line . '<BR>';
                echo '[' . 0 . ',' . $arColumnLength[0] . '] => ' . $col01 . ' | [' . $arColumnLength[0] . ',' . $arColumnLength[1] . '] => ' . $col02 . ' | [' . ($arColumnLength[0] + $arColumnLength[1]) . ',' . $arColumnLength[2] . '] => ' . $col03 . '<BR>';
                die;
                */

                $col03 = trim($col03);
                $name = htmlspecialchars($col03);

                if (!in_array($name, $arMessage)) {
                    $sql = 'INSERT INTO `settings_translation_file_content` (id, reference_key, settings_translation_file_id, active) VALUES (' . $dbid . ', "' . $name . '", ' . $fileId . ', 1);';
                    echo $sql . '<br>';
                    foreach ($arTrans AS $transId) {
                        $sql = 'INSERT INTO `settings_translation_file_content_translation` (settings_translation_file_content_id, translation_id, name) VALUES (' . $dbid . ', ' . $transId . ', "");';
                        echo $sql . '<br>';
                    }
                    echo '<br>';
                    $dbid++;
                }

            } else if ($i === 0) {
                $arLine = explode(' ' , $line);
                foreach ($arLine AS $col) {
                    $arColumnLength[] = strlen($col);
                }
            }
        }
        exit();
    }

    /**
     * @Route("/getDebugTranslation", name="frontoffice_get_debug_translation", methods={"GET"})
     */
    public function getDebugTranslation(Request $request)
    {
        $arMessage = $this->getApiSettingsTranslationFile();
        if ($arMessage) {
            $arMessage = array_map(function($ar) {
                return $ar['referenceKey'];
            }, $arMessage);

            $arMessage = array_unique($arMessage);
        }

        $filename = $filepath = dirname($_SERVER['DOCUMENT_ROOT']) . '/translations.txt';
        if (!is_file($filename)) {
            dd('Run: php bin/console debug:translation pt > translations.txt');
        }
        $content = file_get_contents($filename);
        $arContent = explode("\n", $content);

        $arName = [];
        $arColumnLength = [];
        foreach ($arContent AS $i => $line) {
            $line = trim($line);

            if ($i > 2 && $i + 3 < count($arContent)) {
                if (substr($line, 0, 8) === 'messages') {
                    $col01 = '';
                    $col02 = substr($line, $arColumnLength[0], $arColumnLength[1]);
                    $col03 = substr($line, $arColumnLength[1], $arColumnLength[2]);
                } else {
                    $col01 = substr($line, 0, $arColumnLength[0]);
                    $col02 = substr($line, $arColumnLength[0], $arColumnLength[1]);
                    $col03 = substr($line, $arColumnLength[0] + $arColumnLength[1], $arColumnLength[2]);
                }

                /*
                echo '<pre>';
                echo $line . '<BR>';
                echo '[' . 0 . ',' . $arColumnLength[0] . '] => ' . $col01 . ' | [' . $arColumnLength[0] . ',' . $arColumnLength[1] . '] => ' . $col02 . ' | [' . ($arColumnLength[0] + $arColumnLength[1]) . ',' . $arColumnLength[2] . '] => ' . $col03 . '<BR>';
                die;
                */

                $col03 = trim($col03);
                $name = htmlspecialchars($col03);

                $arName[] = $name;

            } else if ($i === 0) {
                $arLine = explode(' ' , $line);
                foreach ($arLine AS $col) {
                    $arColumnLength[] = strlen($col);
                }
            }
        }

        return $this->json($arName);
    }
}