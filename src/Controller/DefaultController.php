<?php

namespace App\Controller;

use App\Controller\Forms\FormsController;
use App\Controller\SiteCacheController;
use App\Controller\Product\ProductHighlightedController as ProductHighlighted;
use App\Entity\SiteAccess;
use App\Service\SettingsService;
use Doctrine\DBAL\DBALException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class DefaultController extends SiteCacheController
{
    private $twig;

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService, Environment $twig)
    {
        $this->twig = $twig;
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/", name="frontoffice_index", methods={"GET"})
     */
    public function index(Request $request)
    {
        $this->setCacheFilename('home');
        $defaultLanguage = $request->getLocale();

        $colProductHighlighted = [];
        $colProductHighlightedAll = [];
        $colProductCategoryHighlighted = [];

        if ((count($this->arProductType) === 1 && trim($this->arProductType[0] !== '')) || count($this->arProductType) > 1) {
            $colAllHighlighted = [];

            $categoryId = '';
            if ($colHighlighted = ProductHighlighted::get($this, $this->arProductType, $categoryId, $defaultLanguage)) {
                $colProductCategoryHighlighted = array_key_exists('colProductCategoryHighlighted', $colHighlighted) ? $colHighlighted['colProductCategoryHighlighted'] : [];

                $colAllHighlighted = array_key_exists('colProducts', $colHighlighted) ? $colHighlighted['colProducts'] : [];
            }

            $objForms = new FormsController();
            foreach ($colAllHighlighted AS $productHighlighted) {
                $productType = $productHighlighted['productTypeReferenceKey'];
                if (!array_key_exists($productType, $colProductHighlighted)) {
                    $colProductHighlighted[$productType] = [];
                }

                if (array_key_exists('productAdditionalFields', $productHighlighted)) {
                    $colProductAdditionalFields = $objForms->getFields($productHighlighted['productAdditionalFields'], [
                        'colProductCategory' => $productHighlighted['colProductCategory']
                    ]);

                    $productHighlighted['productAdditionalFields'] = $colProductAdditionalFields;
                }

                $colProductHighlighted[$productType][] = $productHighlighted;

                $colProductHighlightedAll[] = $productHighlighted;
            }
        }

        if ($colProductHighlighted) {
            $colProductHighlighted['all'] = $colProductHighlightedAll;
        }

        $colRichmedia = [];
        $url = $this->apiUrl . '/api/content?type=richmedia&fields=url&language=' . $defaultLanguage;
        if ($data = $this->getAPIData($url)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('colContent', $objData)) {
                    $colRichmedia = $objData['colContent'];
                }
            }
        }

        return $this->renderSite('index.html.twig', [
            'colProductHighlighted'         => $colProductHighlighted,
            'colProductCategoryHighlighted' => $colProductCategoryHighlighted,
            'colRichmedia' => $colRichmedia
        ]);
    }

    /**
     * @Route("/cookieConsent", name="frontoffice_cookie_consent", methods={"POST"})
     */
    public function cookieConsent()
    {
        $appVersionFrontOffice = $this->objSettingsService->getEnvVars('APP_VERSION_FRONTOFFICE');

        $date = $this->getDate('now');
        $content = 'Privacy Policy ID: ' . $appVersionFrontOffice . ' | Agreed in ' . $date;

        $objCookieConsentController = new CookieConsentController();
        $objCookieConsentController->setConsent($this->em, $this->domainName, $appVersionFrontOffice, $this->appVersionCookiePolicy);

        $cookie = new Cookie('cookie-consent-' . $this->appVersionCookiePolicy, $content, strtotime('now + 1 year'));

        $response = new Response();
        $response->headers->setCookie($cookie);

        $response->setContent(json_encode([
            'gtmHead' => $this->twig->render('_includes/gtm_head.html.twig'),
            'gtmBody' => $this->twig->render('_includes/gtm_body.html.twig')
        ]));

        $response->headers->set('Content-Type', 'application/json');
        $response->send();
        exit();
    }

    /**
     * @Route("/getAPI", name="frontoffice_get_api", methods={"POST"})
     */
    public function getAPI(Request $request)
    {
        $url = $request->request->get('url');
        $return = [];
        if ($data = $this->getAPIData($this->apiUrl . '/api/' . $url)) {
            $return = json_decode($data, JSON_UNESCAPED_UNICODE);
        }
        if (!array_key_exists('return', $return)) {
            $return['return'] = 'success';
        }
        return $this->json($return);
    }
}