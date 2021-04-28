<?php

namespace App\Controller\Customer;

use App\Functions\Validation;
use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CustomerInvoiceController extends SiteCacheController
{
    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/addCustomerInvoice", name="add_customer_invoice")
     */
    public function index(Request $request, SessionInterface $session): Response
    {

        //var_dump($request); die;

        $customerId = $session->get('customerId');
        $line1      = filter_var(trim($request->request->get('line1')),FILTER_SANITIZE_STRING);
        $line2      = filter_var(trim($request->request->get('line2')),FILTER_SANITIZE_STRING);
        $countryId  = filter_var(trim($request->request->get('countryId')),FILTER_SANITIZE_STRING);
        $city       = filter_var(trim($request->request->get('city')),FILTER_SANITIZE_STRING);
        $state      = filter_var(trim($request->request->get('state')),FILTER_SANITIZE_STRING);
        $postalCode = filter_var(trim($request->request->get('postalCode')),FILTER_SANITIZE_STRING);
        $active     = filter_var(trim($request->request->get('active')),FILTER_SANITIZE_STRING);

        $errorMessage = [];
        if(!$line1)     { array_push($errorMessage,'Error: line1'); }
        if(!$line2)     { array_push($errorMessage,'Error: line2'); }
        if(!$countryId) { array_push($errorMessage,'Error: Country'); }
        if(!$city)      { array_push($errorMessage,'Error: city'); }
        if(!$state)     { array_push($errorMessage,'Error: state'); }
        if(!$postalCode){ array_push($errorMessage,'Error: postalCode'); }

        if(count($errorMessage)>0) {
            return $this->json(['return' => 'error', 'erros_description' => "CustomerInvoiceController :: ".$errorMessage]);
        }

        $arData = [
            'customerId' => $customerId,
            'line1'      => $line1,
            'line2'      => $line2,
            'country'    => $countryId,
            'city'       => $city,
            'state'      => $state,
            'postalCode' => $postalCode,
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/addCustomerInvoice', $arData);
        $objData = json_decode($data);
        if ($data) {
            $data = json_decode($data);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }
}
