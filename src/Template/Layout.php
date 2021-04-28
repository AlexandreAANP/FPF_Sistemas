<?php
namespace App\Template;

use App\Controller\Forms\FormsController;
use App\Controller\SiteCacheController;
use App\Service\CurlService;
use App\Service\SettingsService;
use App\Service\StringFormat;
use App\Service\UtilService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

Class Layout extends AbstractController {
    public $CKEditorSetted     = false;
    public $session            = null;
    public $objSettingsService = null;
    public $requestStack       = null;
    public $request            = null;
    public $params             = null;

    public function __construct(ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        $this->params             = $params;
        $this->requestStack       = $requestStack;
        $this->request            = $this->requestStack->getCurrentRequest();
        $this->session            = $session;
        $this->objSettingsService = $objSettingsService;
    }

    public function echo($object, $var) {
        if (isSet($var) && array_key_exists($var, $object)) {
            return $object->{$var};
        } else {
            return '';
        }
    }

    // This function check if the LOCALE choose for the user is different of the Default Locale.
    // If is different, returns the name of Locale, but if is the same, it doesn't returns anything.
    public function getLocale(Request $request, $locale = null)
    {
        $ret = '';

        if (!$locale) {
            $locale = parent::getParameter('locale'); // parameters in \config\services.yaml
        }
        $defaultLanguage = $request->getLocale();

        if ($defaultLanguage != $locale) {
            $ret = '/' . $defaultLanguage;
        }

        return $ret;
    }

    public function CKEditor($type) { // $type = 'basic', 'standard', 'full' (standard-all => implements autogrow)
        $this->setCKEditor();

        echo '<script src="https://cdn.ckeditor.com/4.13.1/' . $type . '/ckeditor.js"></script>';
    }

    public function getCKEditor() {
        return $this->CKEditorSetted;
    }

    public function setCKEditor() {
        $this->CKEditorSetted = true;
    }

    function getEnvVars($var) {
        $ret = $this->objSettingsService->getEnvVars($var);
        return $ret;
    }

    function getLayoutVars($var = '') {
        $defaultLanguage = $this->request->getLocale();
        $ret = $this->objSettingsService->getLayoutVars($var);

        if (is_array($ret) && $defaultLanguage) {
            $supportedLocales = $this->getEnvVars('SUPPORTED_LOCALES');
            if (is_array($supportedLocales)) {
                $arLanguages = $supportedLocales;
            } else {
                $arLanguages = [$supportedLocales];
            }

            $localePosition = array_search($defaultLanguage, $arLanguages);

            if (isSet($ret[$localePosition])) {
                $ret = $ret[$localePosition];
            }
        }

        return $ret;
    }

    function getSettingsVars($var = '') {
        $defaultLanguage = $this->request->getLocale();
        $ret = $this->objSettingsService->getSettingsVars($var);

        if (is_array($ret) && $defaultLanguage) {
            $supportedLocales = $this->getEnvVars('SUPPORTED_LOCALES');
            if (is_array($supportedLocales)) {
                $arLanguages = $supportedLocales;
            } else {
                $arLanguages = [$supportedLocales];
            }

            $localePosition = array_search($defaultLanguage, $arLanguages);

            if (isSet($ret[$localePosition])) {
                $ret = $ret[$localePosition];
            }
        }

        return $ret;
    }

    public function getContentGroup($url, $groupType) {
        $colContentGroup = [];

        $apiUrl = $this->objSettingsService->getEnvVars('API_URL');
        $url = $apiUrl . '/api/' . $url;

        $objCurlService = new CurlService();
        if ($data = $objCurlService->getAPIData($url)) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            $colContent = array_key_exists('colContent', $objData) ? $objData['colContent'] : [];
            foreach ($colContent AS $key => $col) {
                $colContentGroupType = $col['colContent' . ucfirst($groupType)];

                foreach ($colContentGroupType AS $contentGroupType) {
                    $ref = $contentGroupType['referenceKey'];
                    if (!array_key_exists($ref, $colContentGroup)) {
                        $colContentGroup[$ref] = [];
                    }
                    $colContentGroup[$ref][] = $col;
                }
            }
        }

        return $colContentGroup;
    }

    public function isDev($request) {
        $host = $request->server->get('HTTP_HOST');

        $isDev = false;
        if (substr($host, strrpos($host, '.')) == '.test') {
            $isDev = true;
        } else if (is_numeric(preg_replace('/[^0-9]/', '', $host))){
            $isDev = true;
        }
        return $isDev;
    }

    public function isStaging($request) {
        $host = $request->server->get('HTTP_HOST');

        $isStaging = false;
        if (is_integer(strrpos($host, '.ibiz.pt'))) {
            $isStaging = true;
        }
        return $isStaging;
    }

    function getFileTypeImage($request) { // Return in String format to put inside a Javascript Array declaration
        $ret = '';
        $fileType = $this->getProject($request, 'file_type_image');

        foreach ($fileType AS $ext) {
            if ($ret) {
                $ret .= ', ';
            }
            $ret .= '\'' . $ext . '\'';
        }
        return $ret;
    }

    function getFileTypeDocument($request) { // Return in String format to put inside a Javascript Array declaration
        $ret = '';
        $fileType = $this->getProject($request, 'file_type_document');

        foreach ($fileType AS $ext) {
            if ($ret) {
                $ret .= ', ';
            }
            $ret .= '\'' . $ext . '\'';
        }
        return $ret;
    }

    function getDocumentRoot() {
        return $_SERVER['DOCUMENT_ROOT'];
    }

    function isImage($request, $filepath, $options) {
        $docRoot = '';
        if (isSet($options['document_root'])) {
            $docRoot = $options['document_root'];
        }

        if (substr($filepath, 0, strlen($docRoot)) != $docRoot) {
            $filepath = $docRoot . $filepath;
        }

        if (is_integer(strpos($filepath, '.'))) {
            $fileTypeImage = $this->getProject($request, 'file_type_image', $this);

            $ext = substr($filepath, strrpos($filepath, '.'));
            $ext = trim($ext);
            if ($ext && file_exists($filepath) && is_file($filepath)) {
                $ext = substr($ext, 1);
                return in_array($ext, $fileTypeImage);
            }
        }

        return false;
    }

    public function getProject(Request $request, $param, $parent = null) {
        if ($parent) {
            $dir = $parent->params->get('project_directory');
        } else {
            $dir = $this->getParameter('project_directory');
        }

        $configProject = [];
        $filename = $dir . '/_base.php';
        if (file_exists($filename)) {
            $configProject = include($filename);
        }

        $ret = 'none-parameter-found';
        if (isSet($configProject[$param])) {
            $ret = $configProject[$param];
        }

        return $ret;
    }

    function getSchemeHost($request) {
        //return $request->getSchemeAndHttpHost();
        return $request->getScheme() . '://' . $request->getHost();
    }

    public function getAPI($url) {
        if ($url) {
            $objCurlService = new CurlService();

            $apiUrl = $this->objSettingsService->getEnvVars('API_URL');
            $url = $apiUrl . '/api/' . $url;

            if ($data = $objCurlService->getAPIData($url)) {
                return json_decode($data, JSON_UNESCAPED_UNICODE);
            }
        }

        return [];
    }

    public function getCurrentLanguage() {
        return $this->request->getLocale();
    }

    public function changeLanguage($language, $languageUri) {
        $currentLanguage = $this->request->getLocale();
        $defaultLanguage = $this->getEnvVars('DEFAULT_LANGUAGE');
        $defaultLanguage = strtolower($defaultLanguage);

        if (trim($languageUri, '/') == $defaultLanguage) {
            $languageUri = '';
        }

        if ($currentLanguage == $language) {
            return '#';

        } else if ($currentLanguage != $defaultLanguage && $language != $defaultLanguage) {
            $newLanguageUri = substr($languageUri,1);
            $newLanguageUri = substr($newLanguageUri, strpos($newLanguageUri,'/'));

            return '/' . $language . $newLanguageUri;

        } else if ($language == $defaultLanguage) {
            $newLanguageUri = substr($languageUri,1);
            $newLanguageUri = substr($newLanguageUri, strpos($newLanguageUri,'/'));

            return $newLanguageUri;

        } else {
            return '/' . $language . $languageUri;
        }
    }

    public function getSupportedLanguages($supportedLocales) {
        $colSupportedLanguages = [];

        if (is_array($supportedLocales)) {
            foreach ($supportedLocales AS $val) {
                $colSupportedLanguages[] = $val;
            }
        } else {
            $colSupportedLanguages = explode('|', $supportedLocales);
        }

        return $colSupportedLanguages;
    }

    public function getHashFile($file) {
        $docRoot = $this->getParameter('document_root');
        if (file_exists($docRoot . $file) && is_file($docRoot . $file)) {
            return hash_file('md5', $docRoot . $file);
        }
        return '';
    }

    public function textEmbed($text, $options = []) {
        if (!$options) {
            return $text;
        }

        if (!is_integer(strpos($text, '[embed:'))) {
            return $text;
        }

        foreach ($options AS $key => $val) {
            if (!is_integer(strpos($text, '[embed:' . $key . ':'))) {
                unset($options[$key]);
            }
        }

        $searchSlideStart = '[embed:slide:start]';
        $searchSlideEnd = '[embed:slide:end]';

        if (array_key_exists('slide', $options)) {
            // This block divide for using different print for sliders ---------------------------------------------- //
            $arSlideKey = [];
            $searchSlideStartKey = '[embed:slide:start:';
            while (is_integer(strpos($text, $searchSlideStartKey))) {
                $slideKey = substr($text, strpos($text, $searchSlideStartKey));
                $slideKey = substr($slideKey, 0, strpos($slideKey, ']'));
                $slideKey = substr($slideKey, strrpos($slideKey, ':') + 1);

                $arSlideKey[] = $slideKey;
                $text = str_replace($searchSlideStartKey . $slideKey . ']', $searchSlideStart, $text);
            }
            // ------------------------------------------------------------------------------------------------------ //

            // This block check if there is more than One Slide in the text content---------------------------------- //
            $arSlide = array_filter(explode($searchSlideEnd, $text));

            $lastSlide = $arSlide[count($arSlide) - 1];
            $secondLastSlide = $arSlide[count($arSlide) - 2];

            if (substr($secondLastSlide, -3) === '<p>' && substr($lastSlide, 0, 4) === '</p>') {
                $contentLastSlide = trim(preg_replace('/\s\s+/', ' ', $lastSlide));

                if (is_integer(strpos($contentLastSlide, '[embed:slide:')) && $contentLastSlide === '</p>') {
                    $arSlide[count($arSlide) - 2] .= $searchSlideEnd . $contentLastSlide;
                    unset($arSlide[count($arSlide) - 1]);
                } else {
                    $arSlide[count($arSlide) - 2] .= $searchSlideEnd . $contentLastSlide;
                    unset($arSlide[count($arSlide) - 1]);
                }
            }

            for ($i = 0; $i < count($arSlide); $i++) {
                if ($i + 1 <= count($arSlide)) {
                    if (substr($arSlide[$i], -3) === '<p>' && substr($arSlide[$i+1], -4) === '</p>') {
                        $arSlide[$i] .= $searchSlideEnd . '</p>';

                        $nextSlide = substr($arSlide[$i + 1], 4);
                        $nextSlide = trim($nextSlide, '/\s/');
                        $arSlide[$i + 1] = $nextSlide;
                    }
                }

                $cont = 0;
                while (substr($arSlide[$i], 0, 2) === "\r\n" && $cont < 1000) {
                    $arSlide[$i] = substr($arSlide[$i], 2);
                    $cont++;
                }

                $cont = 0;
                while (substr($arSlide[$i], 0, 2) === "\n" && $cont < 1000) {
                    $arSlide[$i] = substr($arSlide[$i], 2);
                    $cont++;
                }
            }
            // ------------------------------------------------------------------------------------------------------ //

            $textSlide = '';
            foreach ($arSlide AS $slideKey => $text) {
                $positionStart = strpos($text, $searchSlideStart);
                $positionEnd = strpos($text, $searchSlideEnd);

                if (is_integer($positionStart) && is_integer($positionEnd)) {
                    if (strToLower(substr($text, $positionStart - 3, 3)) == '<p>') {
                        $positionStart = $positionStart - 3;
                    }
                    $slideContent = substr($text, $positionStart);

                    $positionEndBlock = strpos($slideContent, $searchSlideEnd) + strlen($searchSlideEnd);
                    if (strToLower(substr($text, $positionStart + $positionEndBlock, 4)) == '</p>') {
                        $positionEndBlock = $positionEndBlock + 4;
                    }

                    $slideContent = substr($slideContent, 0, $positionEndBlock);

                    $t1 = substr($text, 0, $positionStart);
                    $t2 = substr($text, $positionStart + strlen($slideContent));

                    $embeddedDivision = '[embedded:division]';
                    $slideContent = trim($slideContent);

                    if ($slideContent) {
                        $slideContent = str_replace('<p>' . $searchSlideStart . '</p>', $searchSlideStart, $slideContent);
                        $slideContent = str_replace('<p>' . $searchSlideEnd . '</p>', $searchSlideEnd, $slideContent);
                        $slideContent = str_replace('<p>' . $embeddedDivision . '</p>', $embeddedDivision, $slideContent);

                        $slideContent = str_replace($searchSlideStart, '', $slideContent);
                        $slideContent = str_replace($searchSlideEnd, '', $slideContent);
                    }
                    if ($slideContent) {
                        $arSlideContent = explode($embeddedDivision, $slideContent);
                    }

                    if (count($arSlideContent) > 0) {
                        $slideContent = '';
                        $embedContentFirst = '';
                        $embedContentAll = '';
                        $embedBlockContent = '';

                        $embedContent = $options['slide'];

                        if (count($arSlideKey) > 0) {
                            $embedContent = $embedContent[$arSlideKey[$slideKey]];
                            unset($arSlideKey[$slideKey]);
                        }

                        if (is_array($embedContent)) {
                            $arEmbedContent = $embedContent;

                            $embedContent = $arEmbedContent[0];
                            if (is_array($embedContent)) {
                                $arEmbedContentSlide = $embedContent;

                                if (array_key_exists('first', $arEmbedContentSlide)) {
                                    $embedContentFirst = $arEmbedContentSlide['first'];
                                }
                                if (array_key_exists('all', $arEmbedContentSlide)) {
                                    $embedContentAll = $arEmbedContentSlide['all'];
                                }
                            }

                            $embedBlockContent = $arEmbedContent[1];
                        }

                        foreach ($arSlideContent AS $i => $slide) {
                            if ($embedContentFirst && $i === 0) {
                                $slideContent .= str_replace('[embed:content]', $slide, $embedContentFirst);
                            } else if ($embedContentAll && $i > 0) {
                                $slideContent .= str_replace('[embed:content]', $slide, $embedContentAll);
                            } else {
                                $slideContent .= str_replace('[embed:content]', $slide, $embedContent);
                            }
                        }

                        if ($embedBlockContent) {
                            $slideContent = str_replace('[embed:content]', $slideContent, $embedBlockContent);
                        }
                    }

                    $text = $t1 . $slideContent . $t2;

                    $text = str_replace("\r\n", '', $text);
                    $text = str_replace("\n", '', $text);

                    $textSlide .= $text;

                } else if (is_integer($positionStart) || is_integer($positionEnd)) {
                    return $text;
                }
            }
            $text = $textSlide;
        }

        if (is_integer(strpos($text, $searchSlideStart))) {
            $text = str_replace($searchSlideStart, '{' . substr($searchSlideStart, 1, -1) . '}', $text);
        }

        if (is_integer(strpos($text, $searchSlideEnd))) {
            $text = str_replace($searchSlideEnd, '{' . substr($searchSlideEnd, 1, -1) . '}', $text);
        }

        $colContent = [];
        $originalText = $text;

        $arSearch = [];
        foreach (array_keys($options) AS $key) {
            $arSearch[$key] = '[embed:' . $key . ':';
        }

        $newText = '';
        foreach ($arSearch AS $key => $search) {
            while (is_integer(strpos($text, $search))) {
                $position = strpos($text, $search);
                $t1 = substr($text, 0, $position);
                $embed = substr($text, $position);
                $embed = substr($embed, 0, strpos($embed, ']') + 1);
                $content = substr($embed, strlen($search), strlen($embed) - strlen($search) - 1);

                $newText .= $t1 . $embed;
                $text = substr($text, strlen($t1) + strlen($embed));

                $colContent[$key][] = $content;
            }
        }

        if ($newText) {
            $text = $newText . $text;
        } else {
            $text = $originalText;
        }

        foreach ($colContent AS $key => $arContent) {
            foreach ($arContent AS $content) {
                if (array_key_exists($key, $options)) {
                    $from = '[embed:' . $key . ':' . $content . ']';
                    $to = str_replace('[embed:content]', $content, $options[$key]);
                    $text = str_replace($from, $to, $text);
                }
            }
        }

        return $text;
    }

    public function readTranslationJs($language = null) {
        if ($language) {
            $arSupportedLocales = [$language];
        } else {
            $supportedLocales = $this->requestStack->getCurrentRequest()->server->get('SUPPORTED_LOCALES');
            if (is_integer(strpos($supportedLocales, '|'))) {
                $arSupportedLocales = explode('|', $supportedLocales);
            } else {
                $arSupportedLocales = [$supportedLocales];
            }
        }

        $arReturn = [];
        $projectDocRoot = $this->requestStack->getCurrentRequest()->server->get('PROJECT_DOC_ROOT');
        foreach($arSupportedLocales AS $language) {
            $file = '/assets/js/translations/messages.' . $language . '.js';
            if (file_exists($projectDocRoot . '/public' . $file)) {
                $arReturn[] = $file;
            }
        }

        return $arReturn;
    }

    public function textSplit($text) {
        $ret = [];

        if (is_array($text)) {
            $arText = $text;
            foreach ($arText AS $text) {
                $arOptions = explode('|', $text);
                foreach ($arOptions AS $t) {
                    if (is_integer(strpos($t, ':'))) {
                        $arOp = explode(':', $t);
                        if (count($arOp) === 3) {
                            if (!array_key_exists($arOp[0], $ret)) {
                                $ret[$arOp[0]] = [];
                            }
                            $ret[$arOp[0]][] = [
                                'key' => $arOp[1],
                                'val' => $arOp[2],
                            ];
                        }
                    }
                }
            }
        } else if (is_integer(strpos($text, ':'))) {
            $arOp = explode(':', $text);
            if (count($arOp) === 3) {
                $ret[$arOp[0]] = [
                    'key' => $arOp[1],
                    'val' => $arOp[2],
                ];
            }
        }
        return $ret;
    }

    public function unset($var, $key) {
        unset($var[$key]);
        return $var;
    }

    public function array_reset($var)
    {
        $newVar = [];
        foreach ($var AS $v) {
            $newVar[] = $v;
        }
        return $newVar;
    }

    public function findKeyInFormsCollection($key, $ar) {
        if ($position = array_search($key, array_column($ar, 'field'))) {
            return $ar[$position]['value'];
        } else {
            return false;
        }
    }

    public function pad($input, $length, $options = null) {
        $string = '0';
        if (is_array($options) && array_key_exists('string', $options)) {
            $string = $options['string'];
        }

        $type = STR_PAD_LEFT;
        if (is_array($options) && array_key_exists('type', $options)) {
            $type = $options['type'];
        }

        return str_pad($input, $length, $string, $type);
    }

    public function base64_encode($str) {
        $objUtilService = new UtilService();
        return $objUtilService->base64_encode($str);
    }

    public function base64_decode($str) {
        $objUtilService = new UtilService();
        return $objUtilService->base64_decode($str);
    }

    function dateDecode($str, $options = '') {
        $objStringFormat = new StringFormat();
        return $objStringFormat->dateDecode($str, $options);
    }

    function dateEncode($str) {
        $objStringFormat = new StringFormat();
        return $objStringFormat->dateEncode($str);
    }

    public function substr($str, $start, $end = null) {
        if ($end) {
            return substr($str, $start, $end);
        } else {
            return substr($str, $start);
        }
    }

    public function strpos($str, $search) {
        return strpos($str, $search);
    }

    public function getImageSize($filename) {
        $ret['width'] = '';
        $ret['height'] = '';

        if ($filename) {
            $size = @getimagesize($filename);
            $fp = @fopen($filename, 'rb');
            if ($size && $fp) {
                $ret['width'] = $size[0];
                $ret['height'] = $size[1];
            }
        }

        return $ret;
    }

    public function pregMatchAll(string $pattern, string $subject): array
    {
        preg_match_all($pattern, $subject, $matches);
        return $matches;
    }
}