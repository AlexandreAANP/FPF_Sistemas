<?php

namespace App\Controller\Contact;

use App\Controller\Forms\FormsController;
use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use Doctrine\DBAL\DBALException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class ContactController extends SiteCacheController
{
    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/contact", name="frontoffice_contact", methods={"GET","POST"})
     */
    public function index(Request $request): Response
    {
        $defaultLanguage = $request->getLocale();

        $colContactForms = [];
        if ($this->objSettingsService->getEnvVars('HAS_CONTACT_FORMS')) {
            $url = $this->apiUrl . '/api/getForms?table=contact&language=' . $defaultLanguage;
            if ($data = $this->getAPIData($url)) {
                $objContactForms = json_decode($data, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('colForms', $objContactForms) && count($objContactForms['colForms'])) {
                    $colContactForms = $objContactForms['colForms'];
                }
            }
        }

        $objForms = new FormsController();
        $colContactForms = $objForms->getFields($colContactForms);
        if (array_key_exists('columns', $colContactForms) && array_key_exists('values', $colContactForms)) {
            $colContactForms = [
                'checkout' => [$colContactForms]
            ];
        }
        return $this->renderSite('contact/index.html.twig', [
            'colContactForms' => $colContactForms
        ]);
    }
}
