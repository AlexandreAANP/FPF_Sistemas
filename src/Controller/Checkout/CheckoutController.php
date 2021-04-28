<?php

namespace App\Controller\Checkout;

use App\Controller\Customer\CustomerController;
use App\Controller\Forms\FormsController;
use App\Controller\SiteCacheController;
use App\Controller\Product\ProductController;
use App\Functions\Currency;
use App\Functions\MoneyParser;
use App\Functions\Validation;
use App\Service\Payment\StripeService;
use App\Service\SettingsService;
use App\Service\VivaWalletService;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutController extends SiteCacheController
{
    public $apiUrl = '';
    public $siteUrl = '';

    public $params = null;
    public $request = null;
    public $requestStack = null;
    public $session = null;
    public $dispenserId = null;

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);

        $this->params = $params;
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;

        $this->apiUrl = $requestStack->getCurrentRequest()->server->get('API_URL');
        $this->siteUrl = $this->requestStack->getCurrentRequest()->server->get('SITE_URL');
        $this->dispenserId = $requestStack->getCurrentRequest()->server->get('CUSTOMER_ID_DISPENSER');
    }

    /**
     * @Route("/checkout-test", name="frontoffice_checkout_test", methods={"GET","POST"})
     */
    public function test(Request $request)
    {
        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirectToRoute('frontoffice_checkout_auth');
        }

        dd($_POST);
        die;

        $arData = [
            'product'           => $arProduct,
            'customerId'        => $customerId,
            'customerAddressId' => $customerAddressId,
            'paymentMethod'     => $paymentMethod,
            'deliveryMethod'    => $deliveryMethod,
            'domainName'        => 'frontoffice.test',
            'orderStatusReferenceKey' => 'order-status-awaiting-payment',
        ];

        $data = setAPIData('https://{host}/api/addOrder',$arData);
        $objData = json_decode($data);
        echo $objData->orderInfoId;

    }

    /**
     * @Route("/checkout", name="frontoffice_checkout_index", methods={"GET","POST"})
     */
    public function index(Request $request)
    {
        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirectToRoute('frontoffice_checkout_auth');
        }

        $customerName = $this->session->get('customerName');

        $objProduct = new ProductController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);
        $return = 'array';
        $arCart = $objProduct->getCart($request, $return);

        if ($arCart['return'] === "error") {
            echo 'Error at productCart()';
            exit();
        }

        $enableAddressOnCheckout = false;
        $colCustomerAddress = [];
        if ($data = $this->getAPIData($this->apiUrl . '/api/getOrderCustomerAddress/' . $customerId)) {
            if ($objCustomerAddress = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('colCustomerAddress', $objCustomerAddress)) {
                    $colCustomerAddress = $objCustomerAddress['colCustomerAddress'];
                }

                if (array_key_exists('enableAddressOnCheckout', $objCustomerAddress)) {
                    $enableAddressOnCheckout = $objCustomerAddress['enableAddressOnCheckout'];
                }
            }
        }

        $colGeoCountry = [];
        if ($data = $this->getAPIData($this->apiUrl . '/api/getGeoCountry')) {
            if ($objData = json_decode($data)) {
                $colGeoCountry = $objData->colGeoCountry;
            }
        }

        $colGeoPtDistrict = [];
        if ($data = $this->getAPIData($this->apiUrl . '/api/getGeoPtDistrict')) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            if (array_key_exists('colGeoPtDistrict', $objData)) {
                $colGeoPtDistrict = $objData['colGeoPtDistrict'];
            }
        }

        $colGeoPtCouncil = [];
        if ($data = $this->getAPIData($this->apiUrl . '/api/getGeoPtCouncil')) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            if (array_key_exists('colGeoPtCouncil', $objData)) {
                $colGeoPtCouncil = $objData['colGeoPtCouncil'];
            }
        }



        $enableInvoiceOnCheckout = false;
        $colCustomerInvoice = [];
        $url = $this->apiUrl . '/api/getOrderCustomerInvoice/' . $customerId;
        if ($data = $this->getAPIData($url)) {
            $objCustomerInvoice = json_decode($data, JSON_UNESCAPED_UNICODE);
            if (array_key_exists('colCustomerInvoice', $objCustomerInvoice)) {
                $colCustomerInvoice = $objCustomerInvoice['colCustomerInvoice'];
            }

            if (array_key_exists('enableInvoiceOnCheckout', $objCustomerInvoice)) {
                $enableInvoiceOnCheckout = $objCustomerInvoice['enableInvoiceOnCheckout'];
            }
        }

        $listPaymentGateway = $this->getAPIData($this->apiUrl . '/api/getPaymentGateway');
        if ($listPaymentGateway) {
            $listPaymentGateway = json_decode($listPaymentGateway, JSON_UNESCAPED_UNICODE);
        }

        $arPaymentGatewayRoute = [
            'payment_gateway_none'                  => '/checkout/none',
            'payment_gateway_check_availability'    => '/checkout/check-availability',
            'payment_gateway_stripe'                => '/checkout/stripe',
            'payment_gateway_amazon_pay'            => '/checkout/amazon',
            'payment_gateway_apple_pay'             => '/checkout/apple',
            'payment_gateway_paypal'                => '/checkout/paypal',
        ];

        $arPaymentGateway = [];
        foreach ($listPaymentGateway['colPaymentGateways'] as $key => $val) {
            $name = $val['name'];
            $referenceKey = $val['referenceKey'];

            $icon = substr($referenceKey, 16);
            $icon = str_replace('_', '-', $icon);

            if (array_key_exists($referenceKey, $arPaymentGatewayRoute)) {
                $link = $arPaymentGatewayRoute[$referenceKey];
                if ($name == "") {
                    $name = 'OTHER';
                }
                if ($icon == 'none') {
                    $icon = 'money';
                }
                $arPaymentGateway[$key] = [
                    'referenceKey' => $referenceKey,
                    'name' => $name,
                    'icon' => 'fa-' . $icon,
                    'link' => $link,
                ];
            }
        }

        $this->setCacheSaveBlock(); // Do not save cache for Checkout

        $colCustomerAddress = $this->parseToCommonCase($colCustomerAddress);
        $colCustomerInvoice = $this->parseToCommonCase($colCustomerInvoice);

        if (array_key_exists('listProduct', $arCart)) {
            $objForms = new FormsController();
            foreach ($arCart['listProduct'] AS $key => $listProduct) {
                $colProductAdditionalFields = [];
                if (array_key_exists('productAdditionalFields', $listProduct)) {
                    $colProductAdditionalFields = $objForms->getFields($listProduct['productAdditionalFields'], [
                        'colProductCategory' => $listProduct['colProductCategory']
                    ]);
                }
                $arCart['listProduct'][$key]['productAdditionalFields'] = $colProductAdditionalFields;
            }
        }

        return $this->renderSite('checkout/index.html.twig', [
            'product'                 => $arCart,
            'customerName'            => $customerName,
            'enableAddressOnCheckout' => $enableAddressOnCheckout,
            'enableInvoiceOnCheckout' => $enableInvoiceOnCheckout,
            'customerAddress'         => $colCustomerAddress,
            'customerInvoice'         => $colCustomerInvoice,
            'colGeoCountry'           => $colGeoCountry,
            'colGeoPtDistrict'        => $colGeoPtDistrict,
            'colGeoPtCouncil'         => $colGeoPtCouncil,
            'listPaymentGateway'      => $arPaymentGateway,
        ]);
    }

    function changeUnderscoreToUpperCase($key) {
        $arLetter = explode('_', $key);

        $newKey = '';
        foreach ($arLetter AS $i => $val) {
            if ($i === 0) {
                $newKey .= strtolower($val);
            } else {
                $newKey .= ucfirst($val);
            }
        }

        return $newKey;
    }

    function parseToCommonCase($ar) {
        $newAr = [];

        foreach ($ar AS $key => $val) {
            if (is_array($val)) {
                $newAr[$key] = $this->parseToCommonCase($val);
            } else {
                if ($key === 'name_to_invoice') { // TODO: Trocar esse nome na tabela para não precisar fazer essa verificação aqui
                    $key = 'name';
                } else if (is_integer(strpos($key, '_'))) {
                    $key = $this->changeUnderscoreToUpperCase($key);
                }
                $newAr[$key] = $val;
            }
        }

        return $newAr;
    }

    /**
     * @Route("/checkout/auth", name="frontoffice_checkout_auth", methods={"GET"})
     */
    public function auth(Request $request)
    {

        $objProduct = new ProductController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);
        $return = 'array';
        $arCart = $objProduct->getCart($request, $return);

        if ($arCart['return'] === "error") {
            echo 'Error at productCart()';
            exit();
        }

        $this->setCacheSaveBlock(); // Do not save cache for Checkout

        $productItemsInCart = ($this->session->get('product') && $this->session->get('product') != "") ? count($this->session->get('product')) : 0;

        return $this->renderSite('checkout/auth.html.twig', [
            'product' => $arCart,
            'productItemsInCart' => $productItemsInCart,
        ]);
    }

    /**
     * @Route("/checkout/success", name="frontoffice_checkout_success", methods={"GET"})
     */
    public function checkoutSuccess(Request $request)
    {
        return $this->renderSite('checkout/success.html.twig', []);
    }

    /**
     * @Route("/checkout/error/{orderInfoId?}", name="frontoffice_checkout_error", methods={"GET"})
     */
    public function checkoutError(Request $request, $orderInfoId)
    {
        return $this->renderSite('checkout/error.html.twig', [
            'orderInfoId' => $orderInfoId
        ]);
    }

    function updateOrder($request, $orderInfoId, $orderStatusReferenceKey, $paymentLog = "")
    {
        $arData = [
            'orderInfoId' => $orderInfoId,
            'paymentLog' => json_encode($paymentLog),
            'orderStatusReferenceKey' => $orderStatusReferenceKey
        ];
           // dd($data = $this->setAPIData($this->apiUrl . '/api/updateOrder', $arData));
        if ($data = $this->setAPIData($this->apiUrl . '/api/updateOrder', $arData)) {
            //dd($data, $arData);
            $arData = json_decode($data, JSON_UNESCAPED_UNICODE);
            
            if ($arData && array_key_exists('return', $arData) && $arData['return'] == 'success') {
                $this->cartClear();
                return $arData['orderInfoId'];
            }
        }

        return false;
    }

    function addOrder($request, $customerAddressId, $customerInvoiceId, $paymentMethod, $deliveryMethodReferenceKey, $orderStatusReferenceKey)
    {
        $defaultLanguage = $request->getLocale();

        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirectToRoute('frontoffice_checkout_auth');
        }

        $objProduct = new ProductController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);
        $return = 'array';
        $arCart = $objProduct->getCart($request, $return);

        if ($arCart['return'] === "error") {
            echo 'Error at productCart()';
            exit();
        }

        if ($arCart['productQuantity'] === null) {
            echo 'Cart is empty!';
            exit();
        }

        /*
        Ao adicionar o produto no carrinho, criamos uma sessao product_item_stock, assim como temos product_id e quantity.
        product_item_stock_id é o ID da tabela product_item_stock
        Na tabela product_item_stock o stock e o preço do item
        */
        $arProduct = [];
        $line = 0;

        foreach ($arCart['productQuantity'] as $productItemStockId => $val) {
            $arProduct[$line] = [
                'productId' => $arCart['product'][$productItemStockId],
                'productItemStockId' => $productItemStockId,
                'price' => $arCart['productPrice'][$productItemStockId],
                'quantity' => $val,
                'calendar' => $arCart['productCalendar'] ?? null ?  $arCart['productCalendar'][$productItemStockId] : ""
            ];
            $line++;
        }
        
        $arData = [
            'colInvoice' => [
                [
                    'colProduct' => $arProduct,
                    'customerAddressId' => $customerAddressId,
                    'customerInvoiceId' => $customerInvoiceId,
                    'paymentMethod' => $paymentMethod
                ]
            ],
            'customerId' => $customerId,
            'deliveryMethodReferenceKey' => $deliveryMethodReferenceKey,
            'orderStatusReferenceKey' => $orderStatusReferenceKey,
            'domainName' => $_ENV['DOMAIN_NAME'],
            'language' => $defaultLanguage
        ];
           
        if ($data = $this->setAPIData($this->apiUrl . '/api/addOrder', $arData)) {
            
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            //dd($data, $arCart, $objData);
            if ($objData && array_key_exists('return', $objData) && $objData['return'] == 'success') {
                if ($paymentMethod == 'none'){
                    $this->cartClear();
                }
                
                return $objData['orderInfoId'];

            }
            //dd($data, $arCart, $objData);
        }

        return false;
    }

    function cartClear(){
        $this->session->remove('product');
        $this->session->remove('product_quantity');
        $this->session->remove('product_item_stock');
        $this->session->remove('product_max_quantity');
        $this->session->remove('product_price');
    }

    /**
     * @Route("/checkout/none", name="frontoffice_checkout_none", methods={"POST"})
     */
    public function checkoutNone(Request $request)
    {

        $customerId = $this->session->get('customerId');
       
        if (!$customerId) {
            return $this->redirectToRoute('frontoffice_checkout_auth');
        }

        $customerAddressId = $request->request->get('customer_address_id') ?? 0;

        $customerInvoiceId = $request->request->get('customer_invoice_id') ?? 0;

        $paymentMethod = 'none';
        $deliveryMethodReferenceKey = '';//'delivery-method-ctt';
        $orderStatusReferenceKey = 'order-status-completed';
        $orderInfoId = $this->addOrder($request, $customerAddressId, $customerInvoiceId, $paymentMethod, $deliveryMethodReferenceKey, $orderStatusReferenceKey);
        
        if ($orderInfoId) {
            //dd(gettype($orderInfoId),$orderInfoId);
            return $this->redirect('/checkout/none/complete/' . $orderInfoId);
        }

        return $this->redirect('/checkout/error');
    }

    /**
     * @Route("/checkout/check-availability", name="frontoffice_checkout_check_availability", methods={"POST"})
     */
    public function checkoutCheckAvailability(Request $request)
    {
        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirectToRoute('frontoffice_checkout_auth');
        }

        $customerAddress = $request->request->get('customer_address');
        if (is_numeric($customerAddress)) {
            $customerAddressId = $customerAddress;

        } else if ($customerAddress === 'new') {
            $objCustomerController = new CustomerController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);

            $line1 = $request->request->get('new_address_line1');
            $line2 = $request->request->get('new_address_line2');
            $city = $request->request->get('new_address_city');
            $state = $request->request->get('new_address_state');
            $country = $request->request->get('new_address_country');
            $postalCode = $request->request->get('new_address_postal_code');
            $newAddressSave = $request->request->get('new_address_save');

            $dontSave = 1;
            if ($newAddressSave === 'on') {
                $dontSave = 0;
            }

            $arData = [
                'customerId' => $customerId,
                'line1' => $line1,
                'line2' => $line2,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postalCode' => $postalCode,
                'dontSave' => $dontSave,
            ];
            $addCustomerAddress = $objCustomerController->addCustomerAddress($arData);
            $customerAddressId = $addCustomerAddress['id'];

        } else { // Entra aqui quando o enableAddress não está ligado, passa e não insere order_address para esse pedido
            $customerAddressId = 0;
        }

        $customerInvoiceId = $request->request->get('customer_invoice_id');

        $paymentMethod = 'check-availability';
        $deliveryMethodReferenceKey = '';//'delivery-method-ctt';
        $orderStatusReferenceKey = 'order-status-check-availability';
        $orderInfoId = $this->addOrder($request, $customerAddressId, $customerInvoiceId, $paymentMethod, $deliveryMethodReferenceKey, $orderStatusReferenceKey);

        if ($orderInfoId) {
            return $this->redirect('/checkout/complete/' . $orderInfoId);
        }

        return $this->redirect('/checkout/error');
    }

    /**
     * @Route("/checkout/none/complete/{orderInfoId}", name="frontoffice_checkout_none_complete", methods={"GET"})
     */
    public function checkoutNoneComplete(Request $request, $orderInfoId)
    {
        $orderStatusReferenceKey = "order-status-completed";
        if (is_numeric($orderInfoId) && intval($orderInfoId) > 0) {
           //dd($ds = $this->updateOrder($request, $orderInfoId, $orderStatusReferenceKey));
            if ($this->updateOrder($request, $orderInfoId, $orderStatusReferenceKey)) {
                
                if ($orderInfoId) {
                    return $this->redirect('/checkout/complete/' . $orderInfoId);
                }
            }
            // return $this->redirect('/checkout/complete/' . $orderInfoId);
            return $this->redirect('/checkout/error');
        } else {
            return $this->redirect('/checkout/error');
        }
    }

    /**
     * @Route("/checkout/stripe/complete", name="frontoffice_checkout_stripe_complete", methods={"GET"})
     */
    public function checkoutStripeComplete(Request $request, StripeService $stripeService)
    {
        $orderInfoId = $request->get('orderInfoId');
        $stripeCheckoutSessionId = $request->get('stripeCheckoutSessionId');
        $stripeSessionId = $this->session->get('stripe_session_id');

        if($stripeCheckoutSessionId != $stripeSessionId){
            return $this->redirect('/checkout/error');
        }

        $orderStatusReferenceKey = 'order-status-completed';
        if (is_numeric($orderInfoId) && intval($orderInfoId) > 0) {
            $paymentLog = '';
            if ($stripeCheckoutSessionId) {
                $stripeSecretKey =  $this->session->get('stripe_secret_key');
                $paymentLog = $stripeService->retrievePaymentItent($stripeSecretKey, $stripeCheckoutSessionId);

            }
            if ($this->updateOrder($request, $orderInfoId, $orderStatusReferenceKey, $paymentLog)) {
                if ($orderInfoId) {
                    return $this->redirect('/checkout/complete/' . $orderInfoId);
                }
            }
            return $this->redirect('/checkout/error');
        } else {
            return $this->redirect('/checkout/error');
        }
    }

    /**
     * @Route("/checkout/complete/{orderInfoId}", name="frontoffice_checkout_complete", methods={"GET"})
     */
    public function checkoutComplete(Request $request, $orderInfoId)
    {
        $customerId = $this->session->get('customerId');

        return $this->renderSite('checkout/complete.html.twig', [
            'customerId'  => $customerId,
            'orderInfoId' => $orderInfoId
        ]);
    }

    /**
     * @Route("/checkout/stripe/error", name="frontoffice_checkout_stripe_error", methods={"GET"})
     */
    public function checkoutStripeError(Request $request)
    {
        $orderInfoId = $request->get('orderInfoId');

        if (is_numeric($orderInfoId) && intval($orderInfoId) > 0) {
            $orderStatusReferenceKey = "order-status-cancelled";
            if ($orderInfoId = $this->updateOrder($request, $orderInfoId, $orderStatusReferenceKey)) {
                return $this->redirect('/checkout/error/' . $orderInfoId);
            }
            return $this->redirect('/checkout/error');
        } else if ($orderInfoId === 'error') {
            return $this->redirect('/checkout/error');
        }
    }

    /**
     * @Route("/checkout/stripe", name="frontoffice_checkout_stripe", methods={"GET", "POST"})
     */
    public function checkoutStripe(Request $request, RequestStack $requestStack, MoneyParser $moneyParser, StripeService $stripe)
    {
        $isFrontofficeCart = $request->request->get('is_frontoffice_cart');

        $colDiscount = [];
        $paymentCredentialsIds = 'stripe_publishable_key, stripe_secret_key';
        $arData = ['paymentCredentialsIds' => $paymentCredentialsIds];

        $colPaymentCredentials = [];
        if ($data = $this->setAPIData($this->apiUrl . '/api/dispenser/payment-gateway/' . $this->dispenserId, $arData)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                $colPaymentCredentials = $objData;
            }
        }

        $stripeApiKey = $colPaymentCredentials['stripe_secret_key'];
        $stripePublishableKey = $colPaymentCredentials['stripe_publishable_key'];

        // O cart pode vir do frontoffice ou do backoffice
        if($isFrontofficeCart == 'true') {
            $customerAddressId = $request->request->get('customer_address_id');
            if(!$customerAddressId) {
                $customerAddressId = 0;
            }
            $customerInvoiceId = $request->request->get('customer_invoice_id');
            if(!$customerInvoiceId) {
                $customerInvoiceId = 0;
            }

            $paymentMethod = 'stripe';
            $deliveryMethodReferenceKey = 'delivery-method-ctt';
            $orderStatusReferenceKey = 'order-status-awaiting-payment';

            $orderInfoId = $this->addOrder($request, $customerAddressId, $customerInvoiceId, $paymentMethod, $deliveryMethodReferenceKey, $orderStatusReferenceKey);

            $objProduct = new ProductController($this->em, $this->params, $this->requestStack, $this->session, $this->objSettingsService);
            $return = 'array';
            $colCartAll = $objProduct->getCart($request, $return);
            $colCart = $colCartAll['listProductItemStock'];

            if ($colCartAll['return'] === "error") {
                echo 'Error at productCart()';
                exit();
            }

        } else {
            $colCart = $this->session->get('col_products');
            $colDiscount = $this->session->get('col_discount');
            $orderInfoId = $this->session->get('order_info_id');
        }

        $lineItems = [];
        //preciso de productPrice, productQuantity, productsName
        foreach ($colCart as $itemKey => $itemValue) {
            $moneyObj = $moneyParser->parse($itemValue['price']);

            $name = $itemValue['name'];

            if($itemValue['color']) {
                $name .= ' | ' . $itemValue['color'];
            }

            if($itemValue['size']) {
                $name .= ' | ' . $itemValue['size'];
            }

            $product = [
                'name' => $name,
                'quantity' => $itemValue['quantity'],
                'unit_amount' => $moneyObj->getAmount(),
                'currency' =>  $moneyObj->getCurrency()->getCode()
            ];

            $lineItems[] = $stripe->createLineItem($stripeApiKey, $product);

        }

        $couponId = null;
        $currency = null;
        if(!empty($colDiscount)) {
            $couponName = '';
            $couponValue = null;
            $colDiscountChangedLength = count($colDiscount) - 1;
            foreach ($colDiscount as $itemKey => $itemValue) {
                $couponName = $couponName . $itemValue['name'];
                if($colDiscountChangedLength != $itemKey) {
                    $couponName .= ' |';
                }

                $moneyObj = $moneyParser->parse($itemValue['price']);
                $couponValue = $couponValue + $moneyObj->getAmount();

                if(!$currency){
                    $currency = $moneyObj->getCurrency()->getCode();
                }

            }
            $coupon = [
                'name' => $couponName,
                'value' => $couponValue,
                'type' => 'amount_off',     //A coupon has either a percent_off or an amount_off and currency.
                'currency' => $currency,    //Three-letter ISO code for the currency of the amount_off parameter (required if amount_off is passed).
                'duration' => 'once',       //Specifies how long the discount will be in effect. Can be forever, once, or repeating.
            ];
            $couponId = $stripe->createCoupon($stripeApiKey, $coupon);
        }


        $paymentItentDataDescription = 'Order' . $orderInfoId;
        $stripeCustomizedUrls = [
            'success' => $this->siteUrl . '/checkout/stripe/complete?stripeCheckoutSessionId={CHECKOUT_SESSION_ID}&orderInfoId=' . $orderInfoId,
            'cancel' => $this->siteUrl . '/checkout/stripe/error?stripeCheckoutSessionId={CHECKOUT_SESSION_ID}&orderInfoId=' . $orderInfoId,
        ];

        $stripeSession = $stripe->createSession($stripeApiKey, $lineItems, $paymentItentDataDescription, $stripeCustomizedUrls, $couponId);
        $this->session->set('stripe_session_id', $stripeSession->id);
        $this->session->set('stripe_secret_key', $stripeApiKey);

        $this->setCacheSaveBlock(); // Do not save cache for Checkout

        return $this->renderSite('checkout/stripe-checkout.html.twig', [
            'stripePublishableKey' => $stripePublishableKey,
            'stripeCheckoutSessionId' => $stripeSession->id,
        ]);
    }

    /**
    * @Route("/checkout/viva-wallet", name="frontoffice_checkout_viva_wallet", methods={"GET"})
    */
    public function checkoutVivaWallet(Request $request, MoneyParser $moneyParser, ParameterBagInterface $parameterBagInterface)
    {
        $customerAddressId = 0;
        $customerInvoiceId = $request->request->get('customer_invoice_id');

        $paymentMethod = 'viva-wallet';
        $deliveryMethodReferenceKey = 'delivery-method-ctt';
        $orderStatusReferenceKey = "order-status-awaiting-payment";
        $orderInfoId = $this->addOrder($request, $customerAddressId, $customerInvoiceId, $paymentMethod, $deliveryMethodReferenceKey, $orderStatusReferenceKey);

        $paymentGatewayId = 'payment_gateway_viva_wallet';
        $paymentCredentialsIds = 'payment_gateway_viva_wallet_client_id,payment_gateway_viva_wallet_client_secret,
                                payment_gateway_viva_wallet_merchant_id, payment_gateway_viva_wallet_api_key';

        $arData = ['paymentCredentialsIds' => $paymentCredentialsIds];

        if ($data = $this->setAPIData($this->apiUrl . '/api/dispenser/payment-gateway/' . $this->dispenserId, $arData)) {
            $arPaymentGatewayCredentials = json_decode($data, JSON_UNESCAPED_UNICODE);
        }

        $productQuantity = $this->session->get('product');
        $productsPrice = $this->session->get('product_item_stock');
        $productsName = $this->session->get('product_name');

        $productsPriceTotal = null;
        foreach ($productsPrice as $key => $value) {
            $productsPriceTotal += $value;
        }

        $moneyObj = $moneyParser->parse(strval($productsPriceTotal));

        $arPaymentOrder = [
            //'Email' => 'client@email.com',
            //'Phone' => '+351963963963',
            //'FullName' => 'Client Name',
            //'PaymentTimeOut' => 86400, // Limit the payment period default is 300 (5min)
            //'RequestLang' => 'pt-PT', // The invoice lang that the client sees
            'MaxInstallments' => 0,
            //'AllowRecurring' => true,
            //'IsPreAuth' => false, // false captures the amount, true waits to be captured manually on wallet
            'Amount' => $moneyObj->getAmount(),  // int value, 1 euro is 100,
            'MerchantTrns' => 'Booking:' . $orderInfoId,
            'CustomerTrns' => 'Reserva #' . $orderInfoId
        ];

        $vivaWallet = new VivaWalletService($parameterBagInterface, $arPaymentGatewayCredentials);

        return $this->render('checkout/viva_wallet_checkout.html.twig', [
            'redirect_url' => $vivaWallet->setPaymentOrder(json_encode($arPaymentOrder)),
            'payment_url' => $this->params->get("kernel.environment") == 'prod' ? 'https://www.vivapayments.com' : 'https://demo-api.vivapayments.com',
        ]);

    }

    /**
     * @Route("/checkout/viva-wallet/error", name="frontoffice_checkout_viva_wallet_error", methods={"GET"})
     */
    public function checkoutVivaWalletError(Request $request, VivaWalletService $vivaWallet)
    {
        dd('Error');

        $objTransaction = $vivaWallet->getTransaction($request->query->get('t'));

        $orderInfoId = $request->get('orderInfoId');

        if (is_numeric($orderInfoId) && intval($orderInfoId) > 0) {
            $orderStatusReferenceKey = "order-status-cancelled";

            if ($orderInfoId = $this->updateOrder($request, $orderInfoId, $orderStatusReferenceKey, $objTransaction)) {
                return $this->redirect('/checkout/error/' . $orderInfoId);
            }
            return $this->redirect('/checkout/error');
        } else if ($orderInfoId === 'error') {
            return $this->redirect('/checkout/error');
        }

    }

    /**
     * @Route("/checkout/viva-wallet/complete", name="frontoffice_checkout_viva_wallet_complete", methods={"GET"})
     */
    public function checkoutVivaWalletComplete(Request $request, VivaWalletService $vivaWallet)
    {
        dd('complete');

        $objTransaction = $vivaWallet->getTransaction($request->query->get('t'));

        $orderInfoId = $request->get('orderInfoId');

        $orderStatusReferenceKey = "order-status-completed";
        if (is_numeric($orderInfoId) && intval($orderInfoId) > 0) {
            if ($this->updateOrder($request, $orderInfoId, $orderStatusReferenceKey, $objTransaction)) {
                if ($orderInfoId) {
                    return $this->redirect('/checkout/complete/' . $orderInfoId);
                }
            }
            return $this->redirect('/checkout/error');
        } else {
            return $this->redirect('/checkout/error');
        }
    }

    /**
     * @Route("/checkout/amazon", name="frontoffice_checkout_amazon", methods={"GET"})
     */
    public function checkoutAmazon(Request $request)
    {
        echo 'Not yet implemented';
        exit();
    }

    /**
     * @Route("/checkout/apple", name="frontoffice_checkout_apple", methods={"GET"})
     */
    public function checkoutApple(Request $request)
    {
        echo 'Not yet implemented';
        exit();
    }

    /**
     * @Route("/checkout/paypal", name="frontoffice_checkout_paypal", methods={"GET"})
     */
    public function checkoutPaypal(Request $request)
    {
        echo 'Not yet implemented';
        exit();
    }

    public function checkoutStripeFrontoffice(){

    }

    function search($array, $key, $value)
    {
        $results = array();
        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }
            foreach ($array as $subarray) {
                $results = array_merge($results, $this->search($subarray, $key, $value));
            }
        }
        return $results;
    }
}
