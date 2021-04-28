<?php

namespace App\Controller\Customer;

use App\Functions\Validation;
use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CustomerInvoicesController extends SiteCacheController
{
    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/customer/my-invoices", name="customer_my_invoices")
     */
    public function index(Request $request, SessionInterface $session): Response
    {
        $customerId = $session->get('customerId');
        $defaultLanguage = $request->getLocale();

        $customerInvoices = json_decode($this->setAPIData($this->apiUrl . '/api/getCustomerInvoices/' . $customerId, [])) ?? [];
        $geoCountries = json_decode($this->getAPIData($this->apiUrl . '/api/getGeoCountry')) ?? [];
        $geoPtCouncil = json_decode($this->getAPIData($this->apiUrl . '/api/getGeoPtCouncil')) ?? [];
        $geoPtDistrict = json_decode($this->getAPIData($this->apiUrl . '/api/getGeoPtDistrict')) ?? [];

        return $this->renderSite('customer/invoices.html.twig', [
            'customerInvoices'  => $customerInvoices->colCustomerInvoices ?? [],
            'geoCountries'      => $geoCountries->colGeoCountry ?? [],
            'geoPtCouncil'      => $geoPtCouncil->colGeoPtCouncil ?? [],
            'geoPtDistrict'     => $geoPtDistrict->colGeoPtDistrict ?? [],
            'imageHost'         => 'http://backoffice.test',
            'controllerNameMenu' => 'Invoices',
        ]);
    }


}
