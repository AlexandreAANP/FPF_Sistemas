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
use Symfony\Component\Filesystem\Filesystem;

/**
 * @Route("/customer/review")
*/
class CustomerReviewController extends SiteCacheController
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

        if ((!is_numeric($customerId) || $customerId === 0)
            && $uri !== '/customer/login' && $uri !== '/login'
            && $uri !== '/customer/signup' && $uri !== '/signup'
            && $uri !== '/customer/recover-password' && $uri !== '/recover-password'
            && $uri !== '/customer/reset-password' && $uri !== '/reset-password'
        ) {
            header('location: /customer/login');
            exit();
        }
    }

    /**
     * @Route("/", name="customer_review")
     */
    public function index(Request $request, SessionInterface $session): Response
    {
        $customerId = $session->get('customerId');
        $orderInfoId = $request->request->get('id');
        $defaultLanguage = $request->getLocale();
        $arOrderProducts = [];
        $fileType = [];

        $data = $this->getAPIData($this->apiUrl . '/api/getOrderProducts/' . $orderInfoId.'?language='.$defaultLanguage);
        $objData = json_decode($data);

        if ($objData) {
            $arOrderProducts = $objData->colOrderProducts;
        }

        $fileTypeImage = $this->objSettingsService->getEnvVars('FILE_TYPE_IMAGE') ?? [];
        $fileMaxSize = ($this->objSettingsService->getEnvVars('FILE_MAX_UPLOAD') ?? 0) * 1000000;

        foreach ($fileTypeImage as $type) {
            $fileType[] = 'image/'.$type;
        }

        return $this->renderSite('customer/review.html.twig', [
            'orderInfoId' => $orderInfoId,
            'arOrderProducts' => $arOrderProducts,
            'fileMaxSize' => $fileMaxSize,
            'fileTypeImage' => json_encode($fileType)
        ]);
    }

    /** TODO
     * @Route("/reviews", name="customer_reviews")
     */
    public function reviews(Request $request, SessionInterface $session): Response
    {
        $customerId = $session->get('customerId');
        $orderInfoId = $request->request->get('id');
        $defaultLanguage = $request->getLocale();
        $arOrderProducts = [];

        $data = $this->getAPIData($this->apiUrl . '/api/getOrderProducts/' . $orderInfoId.'?language='.$defaultLanguage);
        $objData = json_decode($data);

        if ($objData) {
            $arOrderProducts = $objData->colOrderProducts;
        }

        $fileMaxSize = ($this->objSettingsService->getEnvVars('FILE_MAX_UPLOAD') ?? 0) * 1000000;

        return $this->renderSite('customer/review.html.twig', [
            'orderInfoId' => $orderInfoId,
            'arOrderProducts' => $arOrderProducts,
            'fileMaxSize' => $fileMaxSize,
            'imageExtension' => json_encode(['image/jpeg','image/png'])
        ]);
    }


    /**
     * @Route("/save", name="customer_review_save")
     */
    public function save(Request $request, SessionInterface $session)
    {
        $customerId = $session->get('customerId');
        $productId = $request->request->get('productId');
        $orderInfoId = $request->request->get('orderInfoId');
        $rating = $request->request->get('rate');
        $comments = $request->request->get('observations');

        $arData = [
            'customerId' => $customerId,
            'productId' => $productId,
            'orderInfoId' => $orderInfoId,
            'rating' => $rating,
            'title' => '',
            'comments' => $comments
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/addProductReview', $arData);
        $objData = json_decode($data);
        if ($data) {
            return $this->json($objData);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }

    /** TODO
     * @Route("/delete", name="customer_review_delete")
     */
    public function delete(Request $request, SessionInterface $session)
    {
        $customerId = $session->get('customerId');
        $reviewId = $request->request->get('id');
        $data = $this->setAPIData($this->apiUrl . '/api/deleteCustomerReviewProduct/'.$reviewId, []);
        $objData = json_decode($data);

        if ($data) {
            $data = json_decode($data);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }

    }

}
