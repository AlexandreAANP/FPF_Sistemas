<?php

namespace App\Controller;

use App\Entity\SiteAccess;
use App\Service\CurlService;
use App\Service\SettingsService;
use App\Service\PageErrorService;
use App\Template\Layout;
use App\Controller\APIAuthController;
use App\Controller\Content\ContentController;
use App\Controller\Product\ProductController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SiteCacheController extends AbstractController
{
    /* AS VARIAVEIS ABAIXO SAO RESERVADAS PELO SITE POIS SAO PASSADAS EM TODOS OS RENDERS PARA O TWIG
     * // TALVEZ COLOCAR UM UNDERSCORE ANTES E DEPOIS DA VARIAVEL PODE SER BOM, ex: _baseUri_
     * baseUri
     * apiUrl
     * cdnUrl
     * productItemsInCart
     * controllerName
     */

    // DO NOT Configure it here, It's necessary go to .ENV --- //
    public $arLayoutVars             = [];

    public $arProductType            = [];
    public $arContentType            = [];
    public $apiUrl                   = '';
    public $cdnUrl                = '';
    public $siteUrl                  = '';
    public $domainName               = '';
    public $arSiteLanguage           = [];
    public $defaultLanguage          = '';
    public $cacheSave                = false;
    public $cacheControlToken        = ''; // Requires to configure 'Site Published URL' on Backoffice/Settings/Cache
    public $APIAuth                  = false; // Requires to configure 'Config Authentication' on Backoffice/Settings/API
    public $appVersionCookiePolicy   = '';

    public $baseUri                  = ''; // Use this variable if the website is inside a folder,
    // like: /site, so, write "$baseUri = '/site';"
    // You also have to configure this folder '/site' in /public/site.php in '$prevModule'

    public $controllerNameMenu       = null;

    private $docRoot                 = '';
    private $cacheFilename           = '';
    private $siteDirectory           = '';
    private $pathCacheDirectory      = '';
    private $cacheSaveBlock          = false;
    private $renderView              = false;

    public $em                       = null;
    public $params                   = null;
    public $parent                   = null;
    public $request                  = null;
    public $session                  = null;
    public $requestStack             = null;
    public $productItemsInCart       = null;
    public $emailAddressSendMessages = null;
    public $objSettingsService       = null;
    public $languageUri              = null;
    public $currentLanguage          = null;

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService, $parent = null)
    {
        $request = $requestStack->getCurrentRequest();

        $this->em                    = $em;
        $this->params                = $params;
        $this->session               = $session;
        $this->parent                = $parent;
        $this->request               = $request;
        $this->requestStack          = $requestStack;
        $this->currentLanguage       = $this->request->getLocale();
        $this->objSettingsService    = $objSettingsService;

        $this->setEnvVars($requestStack);

        $this->productItemsInCart    = $this->getCacheItemsInCart($session);
        $this->languageUri           = $this->request->getRequestUri();

        if (in_array($this->currentLanguage, $this->arSiteLanguage) && $this->currentLanguage !== $this->defaultLanguage) {
            $this->baseUri = '/' . $this->currentLanguage . $this->baseUri;
        }

        $objLayout = new Layout($this->params, $this->requestStack, $this->session, $this->objSettingsService);
        $servicesPublicDirParameters = $objLayout->getProject($this->request, 'services_public_dir_parameters', $this);
        $this->siteDirectory         = $servicesPublicDirParameters['site_directory'];
        $this->docRoot               = $this->params->get('document_root');
        $this->pathCacheDirectory    = $this->docRoot . $this->siteDirectory;

        if ($this->apiUrl) {
            $url = $this->apiUrl;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            $ret = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!$ret) {
                $objErrorService = new PageErrorService();
                return $objErrorService->maintenance();
            }

            $this->registerAccess();
        }
    }

    public function registerAccess() {
        $versionCookiePolicy = 'cookie-consent-' . $this->appVersionCookiePolicy;

        $objSiteAccessController = new SiteAccessController();

        $ip = $_SERVER['REMOTE_ADDR'];
        $identification = $objSiteAccessController->getIdentification();
        $date = $this->getDate('now');

        $siteAccessId = 0;
        if ($this->request->cookies->has('site-access-view')) {
            $siteAccessId = $this->request->cookies->get('site-access-view');

        } else if ($this->request->cookies->has($versionCookiePolicy)) {
            $siteAccessId = $this->siteAccessView($identification, $ip, $date);

        } else if ($query = $this->em->getRepository(SiteAccess::class)->findTodayAccess($this->domainName, $identification, $ip, $date)) {
            $siteAccessId = $query[0]['id'];
        }

        $objSiteAccessController->setView($this->em, $this->domainName, $siteAccessId);
    }

    public function siteAccessView($identification, $ip, $date) {
        $id = 0;
        if ($query = $this->em->getRepository(SiteAccess::class)->findTodayAccess($this->domainName, $identification, $ip, $date)) {
            $id = $query[0]['id'];
        }

        $cookie = new Cookie('site-access-view', $id, strtotime('now + 1 day'));

        $response = new Response();
        $response->headers->setCookie($cookie);
        $response->sendHeaders();

        return $id;
    }

    public function getDate($req, $return = 'string') {
        $utc = new \DateTimeZone('UTC');
        $timeZone = $this->objSettingsService->getEnvVars('TIME_ZONE') ? $this->objSettingsService->getEnvVars('TIME_ZONE') : 'UTC';
        $date = new \DateTime($req, new \DateTimeZone($timeZone));
        $date->setTimezone($utc);

        if ($return === 'string') {
            return $date->format('Y-m-d H:i:s');
        } else if ($return == 'object') {
            return $date;
        }
        return '';
    }

    function getCacheSave() {
        return $this->cacheSave;
    }

    function setCacheSaveBlock() {
        $this->cacheSaveBlock = true;
    }

    function setCacheFilename($filename) {
        $this->cacheFilename = $filename;
    }

    function getRenderView() {
        return $this->renderView;
    }

    function setRenderView($act) {
        $this->renderView = $act;
    }

    function getControllerNameMenu() {
        return $this->controllerNameMenu;
    }

    function setControllerNameMenu($controllerNameMenu = null) {
        if (!$controllerNameMenu) {
            $controllerNameMenu = $this->requestStack->getCurrentRequest()->attributes->get('_controller');
            $params = explode('Controller::', $controllerNameMenu);
            $controllerNameMenu = substr($params[0], strrpos($params[0], '\\') + 1);

            if ($controllerNameMenu === 'Default') {
                if ($params[1] === 'index') {
                    $controllerNameMenu = 'Home';
                } else if ($params[1] === 'contact') {
                    $controllerNameMenu = 'Contact';
                }

            } else if ($controllerNameMenu === 'Content') {
                $controllerNameMenu = $params[1];
                if (substr($controllerNameMenu, 0, 7) === 'content') {
                    $controllerNameMenu = 'C' . substr($controllerNameMenu, 1);
                }

            } else if ($controllerNameMenu === 'Customer') {
                if ($params[1] === 'support') {
                    $controllerNameMenu = 'CustomerSupport';
                }

            } else if ($controllerNameMenu === 'Product') {
                if ($params[1] === 'productCart') {
                    $controllerNameMenu = 'ProductCart';
                }

            } else if ($controllerNameMenu === 'SiteCache') {
                $controllerNameMenu = null;
            }
        }

        $this->controllerNameMenu = $controllerNameMenu;
    }

    function getPathCacheDirectory() {
        return $this->pathCacheDirectory;
    }

    function getCacheItemsInCart($session = null) {
        $len = 0;
        if ($session->has('product')) {
            $arProduct = $session->get('product');
            if ($arProduct) {
                $len = count($arProduct);
            }
        }

        return $len;
    }

    public function cacheNotFound(Request $request, $action, $data): Response
    {
        // Exemplo: http://backoffice.test/site/action/data?var=bla
        // $var = $request->query->get('var');
        // dd($action, $data, $var);

        if (in_array($action, $this->arSiteLanguage) && $data) {
            $this->request->setLocale($action);
            $action = $data;
        }

        if (!$this->routeManager($request, $action)) { // Check in API if this $action is a FirendlyUrl
            $ret = '';

            if (!$action) {
                $ret = ''; // Show gets() and sets() on TWIG
            } else if (!method_exists($this, $action)) {
                $ret = 'This action doesn`t exists';
            } else if (method_exists($this, $action) && !$data) {
                $ret = 'This action needs more data';
            } else if (method_exists($this, $action)) {
                $ret = 'This action needs less data';
            }

            $defaultLanguage = $this->request->getLocale();
            $baseUri = $this->baseUri;
            if (in_array($defaultLanguage, $this->arSiteLanguage)) {
                $baseUri = '/' . $defaultLanguage . $baseUri;
            }

            return $this->render('_includes/error.html.twig', [
                'ret' => $ret,
                'baseUri' => $baseUri,
                'apiUrl' => $this->apiUrl,
                'cdnUrl' => $this->cdnUrl,
                'productItemsInCart' => $this->productItemsInCart,
            ]);
        }
    }

    function routeManager(Request $request, $refereceKey) {
        $cacheDirectory = $this->getPathCacheDirectory();

        $cacheFilename = $refereceKey . '.html';
        $cacheFilepath =  $cacheDirectory . '/' . $cacheFilename;

        if (file_exists($cacheFilepath) && is_file($cacheFilepath)) {
            echo file_get_contents($cacheFilepath);
            exit();

        } else if ($data = $this->getAPIData($this->apiUrl . '/api/getFriendlyUrl/' . $refereceKey)) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            if ($objData && array_key_exists('controllerName', $objData)) {
                $controllerName = $objData['controllerName'];
                $controllerNameMenu = $controllerName;

                $pageInfo = $request->getPathInfo();
                if ($pageInfo) {
                    $pageInfo = substr($pageInfo, 1);
                }

                $this->setRenderView(true);

                $html = '';
                $contentType = array_key_exists('contentType', $objData) ? $objData['contentType'] : $pageInfo;
                if (substr($controllerNameMenu, 0,7) === 'Content') {
                    $objContentController = new ContentController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);

                    $strCheck = 'ContentPost';
                    if (substr($controllerName,0, strlen($strCheck)) == $strCheck && strlen($controllerName) > strlen($strCheck)) {
                        $contentType = strToLower(substr($controllerName, strpos($controllerName, $strCheck) + strlen($strCheck)));
                        $controllerName = 'ContentCategory';
                        $refereceKey = '';
                    }
                    $controllerNameMenu = 'Content' . ucfirst($contentType);

                    if ($pageInfo == 'about-us') {
                        $controllerNameMenu .= 'AboutUs';
                    }

                    $this->setControllerNameMenu($controllerNameMenu);

                    if ($controllerName === 'ContentCategory') {
                        $html = $objContentController->contentPost($request, $contentType, $refereceKey, $this);

                    } else if ($controllerName === 'ContentPost') {
                        if ($contentType === 'pages') {
                            $html = $objContentController->contentPage($request, $refereceKey, $this);

                        } else {
                            $html = $objContentController->contentPostDetail($request, $contentType, $refereceKey, $this);
                        }

                    } else if ($controllerName === 'ContentBlank') {
                        $html = $objContentController->contentBlank($request, $refereceKey, $this);
                    }
                }

                if (substr($controllerNameMenu, 0,7) === 'Product') {
                    $objProductController = new ProductController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);

                    $this->setControllerNameMenu($controllerNameMenu);

                    if ($controllerName === 'Product') {
                        $this->setCacheSaveBlock(); // Do not save cache for Products
                        $html = $objProductController->productDetail($request, $refereceKey, $this);
                    }

                } else {
                    $this->setCacheSaveBlock();
                }

                if (!$html) {
                    if ($controllerName) {
                        $html = 'ControllerName was found, but there';
                    } else {
                        $html = 'There';
                    }
                    $html .= ' is no Route for: ' . $controllerName;
                }

                $this->setCacheFilename($cacheFilename);

                echo $html;
                exit();

            } else {
                echo 'Page Not Found';
                exit();
            }

        } else {
            dd('Error');
        }
    }

    function renderSite($filename, $options) {
        $customerId = $this->session->get('customerId');

        if (!$this->getControllerNameMenu()) {
            $this->setControllerNameMenu();
        }
        $controllerNameMenu = $this->getControllerNameMenu();

        // The follow variable are pass in all render in this project (using normally on base.html.twig)
        $options['apiUrl']                 = $this->apiUrl;
        $options['baseUri']                = $this->baseUri;
        $options['cdnUrl']                 = $this->cdnUrl;
        $options['customerId']             = $customerId;
        $options['languageUri']            = $this->languageUri;
        $options['productItemsInCart']     = $this->productItemsInCart;
        $options['appVersionCookiePolicy'] = $this->appVersionCookiePolicy;

        if (!array_key_exists('controllerNameMenu', $options)) {
            $options['controllerNameMenu'] = $controllerNameMenu;
        }

        if ($this->getRenderView()) {
            $this->setRenderView(false);
            $html = $this->renderView($filename, $options);
        } else {
            $html = $this->render($filename, $options);
        }

        $this->cacheSave($html);

        return $html;
    }

    function cacheSave($html, $cacheFilepath = null) {
        if ($this->cacheSaveBlock === false) {
            $siteLanguageBase = constant('SITE_LANGUAGE_BASE');

            if (!$cacheFilepath) {
                $sitePath = $this->request->getRequestUri();

                $uri = '';
                if (is_integer(strpos($sitePath, '?'))) {
                    $uri = substr($sitePath, strpos($sitePath, '?') + 1);
                    $sitePath = substr($sitePath, 0, strpos($sitePath, '?'));

                    $uri = urlencode($uri);
                    $uri = base64_encode($uri);
                }

                if ($this->cacheFilename != '') {
                    $sitePath = $this->cacheFilename;
                    $this->cacheFilename = '';

                } else if (substr($sitePath, 0, strlen($siteLanguageBase) + 2) == '/' . $siteLanguageBase . '/') {
                    $sitePath = substr($sitePath, strlen($siteLanguageBase) + 1);
                }

                $sitePath = str_replace($this->baseUri . '/', '', $sitePath);
                $sitePath = trim($sitePath);
                if ($uri) {
                    $sitePath .= '_vars_' . $uri;
                }
                if ($sitePath) {
                    $cacheFilepath = $sitePath . '.html';
                }
            }

            if (strpos($html, '<!DOCTYPE html>') > 10) {
                $html = substr($html, strpos($html, '<!DOCTYPE html>'));
            }

            if ($this->getCacheSave()) {
                $siteLanguageBase = constant('SITE_LANGUAGE_BASE');
                $cacheDirectory = $this->getPathCacheDirectory();
                $filePath = $cacheDirectory . '/' . $siteLanguageBase . '/' . $cacheFilepath;

                if (!file_exists($cacheDirectory) || !is_dir($cacheDirectory)) {
                    mkdir($cacheDirectory);
                }

                if (!file_exists($cacheDirectory . '/' . $siteLanguageBase) || !is_dir($cacheDirectory . '/' . $siteLanguageBase)) {
                    if (mkdir($cacheDirectory . '/' . $siteLanguageBase)) {
                        $this->cacheWrite($cacheDirectory . '/' . $siteLanguageBase, $filePath, $html);
                    }
                } else {
                    $this->cacheWrite($cacheDirectory . '/' . $siteLanguageBase, $filePath, $html);
                }
            }
        }

        $this->cacheSaveBlock = false;
    }

    function cacheWrite($dirPath, $filePath, $html) {
        if (is_dir($dirPath)) {
            file_put_contents($filePath, $html);
        }
    }

    function getAPIData($url) {
        $objCurlService = new CurlService();
        return $objCurlService->getAPIData($url);
    }

    function setAPIData($url, $arValues) {
        $arValues = $this->checkAPIAuth($arValues);

        $objCurlService = new CurlService();
        return $objCurlService->setAPIData($url, $arValues);
    }

    function checkAPIAuth($var) {
        if ($this->APIAuth) {
            if ($this->parent) {
                $thisProject = $this->parent;
            } else {
                $thisProject = $this;
            }

            $objAPIAuthController = new APIAuthController();
            $token = $objAPIAuthController->apiAuthentication($this->em, $this->apiUrl, $this->domainName);
            if ($token) {
                $var['_token'] = $token;
            }
        }
        return $var;
    }

    function isReservedWord($key) {
        $colReservedWord = [
            '_token'
        ];
        if (in_array($key, $colReservedWord)) {
            echo 'The key "' . $key . '" is a Reserved Word';
            return false;
        }
        return true;
    }

    /**
     * @Route("/cache-control", name="frontoffice_cache_constrol", methods={"POST"})
     */
    public function cacheControl(Request $request): Response
    {
        $token = $request->request->get('token');
        $arData = ['token' => $token, 'privateKey' => $this->cacheControlToken];
        $data = $this->setAPIData($this->apiUrl . '/api/cacheControlValidate', $arData);
        $data = json_decode($data, JSON_UNESCAPED_UNICODE);
        if ($data['return'] === "success") {
            $documentRoot = $this->getParameter('document_root');
            $path = $documentRoot . $this->siteDirectory;
            $listDir = scandir($path);
            foreach ($listDir AS $dir) {
                if ($dir !== '.' && $dir !== '..') {
                    $listFile = scandir($path . '/' . $dir);
                    foreach ($listFile AS $file) {
                        if ($file !== '.' && $file !== '..') {
                            $filepath = $path . '/' . $dir . '/' . $file;
                            if (file_exists($filepath) && is_file($filepath)) {
                                unlink($filepath);
                            }
                        }
                    }
                }
            }
        }

        return $this->json($data);
    }

    /**
     * @Route("/{action?}/{data?}", name="frontoffice_default", requirements={"data": "^.*","action": "^(?!%app.supported_locales%).*$"})
     */
    public function notFound(Request $request, $action, $data): Response
    {
        return $this->cacheNotFound($request, $action, $data);
    }

    function isPost() {
        return !empty($_POST);
    }

    function setVarsInString($str, $contextVars) {
        $newStr = '';
        $arVars = [];

        if (is_integer(strpos($str, '?'))) {
            $file = substr($str, 0, strpos($str, '?'));

            $newFile = '';
            while (is_integer(strpos($file, '$'))) {
                $f1 = substr($file, 0, strpos($file, '$'));
                $f2 = substr($file, strpos($file, '$'));
                $var = $f2;

                $var = substr($var, 1);

                $arVars[] = $var;
                $file = substr($f2, strlen($var) + 1);

                $newFile .= $f1 . '{' . $var . '}';
            }
            if ($newFile) {
                $file = $newFile;
            }

            $query = substr($str, strpos($str, '?'));

            $str = $file . $query;
        }

        while (is_integer(strpos($str, '$'))) {
            $s1 = substr($str, 0, strpos($str, '$'));
            $s2 = substr($str, strpos($str, '$'));
            if (is_integer(strpos($s2, '&'))) {
                $var = substr($s2, 0, strpos($s2, '&'));
            } else {
                $var = $s2;
            }

            $var = substr($var, 1);

            $arVars[] = $var;
            $str = substr($s2, strlen($var) + 1);

            $newStr .= $s1 . '{' . $var . '}';
        }

        $count = 0;
        $arVarsTemp = $arVars;
        while (count($arVarsTemp) && $count < 100) {
            $key = $arVarsTemp[0];
            if (array_key_exists($key, $contextVars)) {
                $newStr = str_replace('{' . $key . '}', $contextVars[$key], $newStr);
            }

            array_splice($arVarsTemp, array_search($key, $arVarsTemp), 1);

            $count++;
        }

        $arGet = $_GET;
        $arGetKeys = array_keys($arGet);
        $arVars = array_intersect($arVars, $arGetKeys);
        foreach ($arGet AS $key => $var) {
            if (in_array($key, $arVars)) {
                $newStr = str_replace('{' . $key . '}', $var, $newStr);
                unset($arGet[$key]);
                array_splice($arVars, array_search($key, $arVars), 1);
            }
        }

        foreach ($arVars AS $key) {
            $newStr = str_replace('{' . $key . '}', '', $newStr);
        }

        return $newStr;
    }

    function setEnvVars($requestStack) {
        $this->apiUrl                   = $requestStack->getCurrentRequest()->server->get('API_URL');
        $this->cdnUrl                   = $requestStack->getCurrentRequest()->server->get('CDN_URL');
        $this->siteUrl                  = $requestStack->getCurrentRequest()->server->get('SITE_URL');
        $this->baseUri                  = $requestStack->getCurrentRequest()->server->get('BASE_URI');
        $this->domainName               = $requestStack->getCurrentRequest()->server->get('DOMAIN_NAME');
        $this->APIAuth                  = $requestStack->getCurrentRequest()->server->get('API_AUTH') == 'true' ? true : false;
        $this->cacheSave                = $requestStack->getCurrentRequest()->server->get('CACHE_SAVE') == 'true' ? true : false;
        $this->cacheControlToken        = $requestStack->getCurrentRequest()->server->get('CACHE_CONTROL_TOKEN');
        $this->arContentType            = $this->objSettingsService->getEnvVars('CONTENT_TYPE');
        $this->emailAddressSendMessages = $requestStack->getCurrentRequest()->server->get('EMAIL_ADDRESS_SEND_MESSAGES');
        $this->defaultLanguage          = $requestStack->getCurrentRequest()->server->get('DEFAULT_LANGUAGE');

        $arVersionCookiePolicy = $this->objSettingsService->getSettingsVars('APP_VERSION_COOKIE_POLICY');
        if ($arVersionCookiePolicy && is_array($arVersionCookiePolicy)) {
            $this->appVersionCookiePolicy = $arVersionCookiePolicy[0];
        } else {
            $this->appVersionCookiePolicy = 0;
        }

        $arProductType                  = $this->objSettingsService->getEnvVars('PRODUCT_TYPE');
        if (is_string($arProductType)) { // When exists only one PRODUCT-TYPE, the function getEnvVars returns a String Type
                                         // But, in the Controllers, we check if in_array(), because can be more than one.
            $arProductType = [$arProductType]; // So, we put this String in a array variable and it's solved! ;-)
        }
        $this->arProductType            = $arProductType;

        $objLayout = new Layout($this->params, $this->requestStack, $this->session, $this->objSettingsService);
        $this->arSiteLanguage           = $objLayout->getSupportedLanguages($requestStack->getCurrentRequest()->server->get('SUPPORTED_LOCALES'));

        $this->arLayoutVars             = $objLayout->getLayoutVars();
    }
}