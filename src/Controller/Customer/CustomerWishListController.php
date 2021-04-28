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

/**
 * @Route("/customer/my-wishlist")
*/
class CustomerWishListController extends SiteCacheController
{
    public $params = null;
    public $request = null;
    public $requestStack = null;
    public $session = null;

    public $apiUrl = '';
    public $siteUrl = '';

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);

        $this->setEnvVars($requestStack);

        $this->params = $params;
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;

        $this->apiUrl = $requestStack->getCurrentRequest()->server->get('API_URL');
        $this->siteUrl = $requestStack->getCurrentRequest()->server->get('SITE_URL');

        $customerId = $this->session->get('customerId');
        $customerId = intval($customerId);
        $uri = $this->request->getRequestUri();

        if (!is_numeric($customerId) || $customerId === 0) {
            header('location: /customer/login');
            exit();
        }
    }

    /**
     * @Route("/", name="customer_my_wishlist")
     */
    public function index(Request $request, SessionInterface $session): Response
    {
        $customerId = $session->get('customerId');
        $arWishlist = [];
        if ($data = $this->setAPIData($this->apiUrl . '/api/getCustomerWishlist/' . $customerId, []) ?? []) {
            $objData = json_decode($data) ?? [];
            if (array_key_exists('colProducts', $objData)) {
                $arWishlist = $objData->colProducts;
            }
        }

        return $this->renderSite('customer/my-wishlist.html.twig', [
            'arWishlist' => $arWishlist,
        ]);
    }

    /**
     * @Route("/save", name="customer_my_wishlist_save")
     */
    public function save(Request $request, SessionInterface $session)
    {
        $customerId = $session->get('customerId');
        $productId = $request->request->get('id');

        $arData = [
            'customerId' => $customerId,
            'productId'=> $productId
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/addCustomerWishlistProduct', $arData);
        $objData = json_decode($data);
        if ($data) {
            $data = json_decode($data);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }

    /**
     * @Route("/delete", name="customer_my_wishlist_delete")
     */
    public function delete(Request $request, SessionInterface $session)
    {
        $customerId = $session->get('customerId');
        $productId = $request->request->get('id');
        if ($data = $this->setAPIData($this->apiUrl . '/api/deleteCustomerWishlistProduct', ['productId' => $productId, 'customerId' => $customerId])) {
            $data = json_decode($data);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }
}
