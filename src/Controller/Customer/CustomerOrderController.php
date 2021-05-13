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

class CustomerOrderController extends SiteCacheController
{

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/customer/my-orders", name="customer_my_order")
     */
    public function index(Request $request, SessionInterface $session): Response
    {
        $customerId = $session->get('customerId');
        $defaultLanguage = $request->getLocale();
        $arOrders = [];
        $colOrderStatusFrontoffice = [];

        $url = $this->apiUrl . '/api/getOrderStatusFrontoffice?language=' . $defaultLanguage;
        if ($data = $this->getAPIData($url)) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            if (array_key_exists('colOrderStatusFrontoffice', $objData)) {
                $colOrderStatusFrontoffice = $objData['colOrderStatusFrontoffice'];
            }
        }

        $now = new \Datetime('now', new \DateTimeZone('Europe/Lisbon'));
        $page = $request->request->get('page') ?? 1;
        $dateFrom = $request->request->get('dateFrom', (clone $now)->modify('-1 year')->format('Y-m-d'));
        $dateTo = $request->request->get('dateTo', $now->format('Y-m-d'));

        if($dateFrom > $dateTo) {
            return $this->renderSite('customer/customer_order/index.html.twig', [
                'orders'                 => $arOrders,
                'dateFrom'               => $dateFrom,
                'dateTo'                 => $dateTo,
                'page'                   => $page,
                'orderStatus'            => $request->request->get('orderStatus'),
                'orderStatusFrontoffice' => $colOrderStatusFrontoffice,
                'info'                   => 'Start Date is older than End Date',
            ]);
        }

        $colData = [
            'language' => $defaultLanguage,
            'page' => $page,
            'dateTo' => $dateTo,
            'dateFrom' => $dateFrom
        ];

        if($request->request->get('orderStatus') > 0) {
            $colData['orderStatusFrontofficeId'] = $request->request->get('orderStatus');
        }

        $arOrders = [];
        if ($data = $this->setAPIData($this->apiUrl . '/api/getCustomerOrders/' . $customerId, $colData)) {
          //  dd($data,$customerId,$colData);
            $objData = json_decode($data);
            if (array_key_exists('colOrders', $objData)) {
                $arOrders = $objData;
            }
        }

        return $this->renderSite('customer/customer_order/index.html.twig', [
            'orders'            => $arOrders,
            'dateFrom'          => $colData['dateFrom'],
            'dateTo'            => $colData['dateTo'],
            'page'            => $page,
            'orderStatus' => $request->request->get('orderStatus'),
            'orderStatusFrontoffice' => $colOrderStatusFrontoffice,
        ]);
    }

    /**
     * @Route("/customer/order-details", name="customer_order_details", methods={"POST"})
     */
    public function getOrderDetails(Request $request): Response
    {

        $orderInfoId = $request->request->get('id');
        $defaultLanguage = $request->getLocale();

        $data = $this->getAPIData($this->apiUrl . '/api/getOrderInvoice/' . $orderInfoId.'/'.$defaultLanguage);
        $objDataOrder = json_decode($data);
        $arOrders = [];

        if ($objDataOrder) {
            $arOrders = $objDataOrder->colOrderInvoice;
        }

        return $this->renderSite('customer/customer_order/order.html.twig', [
            'orderInfoId'       => $orderInfoId,
            'orderInvoices'     => $arOrders
        ]);
    }
}
