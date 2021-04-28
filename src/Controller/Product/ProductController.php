<?php

namespace App\Controller\Product;

use App\Controller\Forms\FormsController;
use App\Controller\SiteCacheController;
use App\Controller\Product\ProductCategoryController AS ProductCategory;
use App\Functions\Currency;
use App\Service\SettingsService;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProductController extends SiteCacheController
{
    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/product", name="frontoffice_product_index", methods={"GET"}, requirements={"page"="\d+"})
     */
    public function index(Request $request)
    {
        $colProductTypeSigned = [];

        $arProductType = [
            'physical-products' => 'Physical Products',
            'services' => 'Services',
            'courses' => 'Courses',
            'events' => 'Events',
            'experiences' => 'Experiences',
            'lodgings' => 'Lodgings',
            'packages' => 'Packages',
            'judicial-process' => 'Judicial Process',
            'proposals' => 'Proposals',
            'properties' => 'Propriedades',
            'transfers' => 'Transfers'
        ];

        if ($arProductTypeEnv = $this->objSettingsService->getEnvVars('PRODUCT_TYPE')) {
            foreach ($arProductTypeEnv AS $productType) {
                if (array_key_exists($productType, $arProductType)) {
                    $colProductTypeSigned[] = [
                        'referenceKey' => $productType,
                        'name' => $arProductType[$productType]
                    ];
                }
            }
        }

        return $this->renderSite('product/type.html.twig', [
            'controllerName' => 'Product',
            'colProductType' => $colProductTypeSigned,
        ]);
    }

    /**
     * @Route("/product/{productType}/{page?}", name="frontoffice_product_type", methods={"GET"}, requirements={"page"="\d+"})
     */
    public function productType(Request $request, $productType, $page = 1)
    {
        $defaultLanguage    = $request->getLocale();
        $customerId = $this->session->get('customerId') ?? null;
        $search = $request->query->get('search');
        $order = $request->query->get('order');

        $productCategoryId = '0';

        $categoryId = $request->query->get('category');
        $lastCategory = $request->query->get('lastCategory');
        if ($categoryId && $lastCategory) {
            $productCategoryId = $categoryId;
        }

        $colProductCategory = [];
        $colProductCategorySub = [];
        $colProductAllCategory = ProductCategory::getCategories($this, $productType, $productCategoryId, $search, $defaultLanguage);

        if ($productCategoryId = $request->query->get('category')) {
            $colProductCategory = ProductCategory::getCategories($this, $productType, $productCategoryId, $search, $defaultLanguage);

            $colProductCategorySub = $this->organizePerCategoryId($colProductCategory);

            if (!$colProductCategorySub) {
                $colProductCategorySub = ProductCategory::getCategory($this, $productCategoryId, $defaultLanguage);
                $colProductCategorySub = ProductCategory::getCategories($this, $productType, $colProductCategorySub['productCategoryId'], $search, $defaultLanguage);
                $colProductCategorySub = $this->organizePerCategoryId($colProductCategorySub);
            }
        }

        $page = $page == '' ? 1 : $page;

        $colProducts    = [];
        $pages          = 0;
        $currentPage    = 0;
        $totalRegisters = 0;

        $url = $this->apiUrl . '/api/getProductList?productType=' . $productType . '&productCategoryId=' . $productCategoryId . '&page=' . $page . '&language='. $defaultLanguage . '&order=' . $order . '&customerId=' .$customerId. '&search=' . urlencode($search);
        if ($data = $this->getAPIData($url)) {

            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);

            if ($objData && array_key_exists('colProducts', $objData)) {
                $colProducts = $objData['colProducts'];
            }

            if ($objData && array_key_exists('pages', $objData)) {
                $pages = $objData['pages'];
            }

            if ($objData && array_key_exists('currentPage', $objData)) {
                $currentPage = $objData['currentPage'];
            }

            if ($objData && array_key_exists('registers', $objData)) {
                $totalRegisters = $objData['registers'];
            }
        }
        $arCartProduct = $this->session->get('product');
        if ($arCartProduct && array_key_exists('products', $arCartProduct)) {
            foreach ($objData AS $row) {
                if (array_key_exists($row['id'], $arCartProduct['products'])) {
                    $row->addedToCart = true;
                }
            }
        }

        $objForms = new FormsController();

        foreach ($colProducts AS $key => $products) {
            if (array_key_exists('productAdditionalFields', $products)) {
                $colProductAdditionalFields = $objForms->getFields($products['productAdditionalFields'], [
                    'colProductCategory' => $products['colProductCategory']
                ]);

                $colProducts[$key]['productAdditionalFields'] = $colProductAdditionalFields;
            }
        }

        $this->setCacheSaveBlock(); // Do not save cache for Products

        $productTypeUcf = ucfirst($productType);
        $controllerName = 'Product';
        if (is_integer(strpos($productTypeUcf, '-'))) {
            $colProductType = explode('-', $productTypeUcf);
            foreach ($colProductType AS $name) {
                $controllerName .= ucfirst($name);
            }
        } else {
            $controllerName .= $productTypeUcf;
        }

        $arPagination = [
            'totalRegisters' => $totalRegisters,
            'pages'          => $pages,
            'currentPage'    => $currentPage,
        ];
        //dd($controllerName,$productType, $colProductCategory, $colProductAllCategory, $colProductCategorySub, $colProducts, $arPagination);
        return $this->renderSite('product/index.html.twig', [
            'controllerName'        => $controllerName,
            'productType'           => $productType,
            'colProductCategory'    => $colProductCategory,
            'colProductAllCategory' => $colProductAllCategory,
            'colProductCategorySub' => $colProductCategorySub,
            'colProducts'           => $colProducts,
            'arPagination'          => $arPagination,
        ]);
    }

    function organizePerCategoryId($colProductCategory) {
        if (array_key_exists('colProductCategories', $colProductCategory)) {
            $colProductCategory = $colProductCategory['colProductCategories'];
        }

        $colProductCategories = [];
        if (count($colProductCategory)) {
            $productCategoryId = $colProductCategory[0]['productCategoryId'];
            $colProductCategories[$productCategoryId] = $colProductCategory;
        }

        return $colProductCategories;
    }

    /**
     * @Route("/product-category/{productType}/{productCategoryId?}/{page?}", name="frontoffice_product_category", methods={"GET"}, requirements={"page"="\d+"})
     */
    public function productCategory(Request $request, $productType, $productCategoryId = 0, $page = 1)
    {
        $search = '';
        $defaultLanguage   = $request->getLocale();
        $arProductCategory = ProductCategory::getCategories($this, $productType, $productCategoryId, $search, $defaultLanguage);

        $this->setCacheSaveBlock(); // Do not save cache for Products

        $productTypeUcf = ucfirst($productType);
        $controllerName = 'ProductCategory';
        if (is_integer(strpos($productTypeUcf, '-'))) {
            $colProductType = explode('-', $productTypeUcf);
            foreach ($colProductType AS $name) {
                $controllerName .= ucfirst($name);
            }
        } else {
            $controllerName .= $productTypeUcf;
        }

        return $this->renderSite('product/category.html.twig', [
            'controllerName'       => $controllerName,
            'productType'          => $productType,
            'colProductCategories' => $arProductCategory['colProductCategories'],
            'arPagination'         => $arProductCategory['arPagination'],
        ]);
    }

    /**
     * @Route("/product-in-category/{productType}/{productCategoryId?}/{page?}", name="frontoffice_product_in_category", methods={"GET"}, requirements={"page"="\d+"})
     */
    public function productInCategory(Request $request, $productType, $productCategoryId = 0, $page = 1)
    {
        $defaultLanguage       = $request->getLocale();
        $colProductsInCategory = ProductCategory::getProductsInCategory($this, $productCategoryId, $defaultLanguage);

        $this->setCacheSaveBlock(); // Do not save cache for Products

        $productTypeUcf = ucfirst($productType);
        $controllerName = 'ProductCategory';
        if (is_integer(strpos($productTypeUcf, '-'))) {
            $colProductType = explode('-', $productTypeUcf);
            foreach ($colProductType AS $name) {
                $controllerName .= ucfirst($name);
            }
        } else {
            $controllerName .= $productTypeUcf;
        }

        return $this->renderSite('product/category.html.twig', [
            'controllerName'              => $controllerName,
            'productType'                 => $productType,
            'colProductsInCategories'     => $colProductsInCategory,
        ]);
    }

    /**
     * @Route("/product-detail", name="frontoffice_product_detail", methods={"GET"})
     */
    public function productDetail(Request $request, $productReferenceKey = null, $parent = null)
    {
        $self = $this;
        if ($parent) {
            $self = $parent;
        }

        if (!$productReferenceKey) {
            echo 'ReferenceKey not found';
            exit();
        }

        $defaultLanguage = $request->getLocale();

        $objProduct = [];
        $url = $self->apiUrl . '/api/getProductDetail?id=' . $productReferenceKey . '&language=' . $defaultLanguage;
        if ($data = $self->getAPIData($url)) {
            $objProduct = json_decode($data, JSON_UNESCAPED_UNICODE);
        }

        $cartMap = function($obj) {
            return $obj['id'];
        };

        $return = 'array';
        $arCart = $this->getCart($request, $return);

        $inCart = [];
        if ($arCart && array_key_exists('listProduct', $arCart) && $arCart['listProduct'] !== '') {
            $inCart = array_map($cartMap, $arCart['listProduct']);
        }

        $productInCart = false;
        if ($objProduct && in_array($objProduct['id'], $inCart)) {
            $productInCart = true;
        }

        $arProductFileNoSession = [];
        if ($objProduct && array_key_exists('colProductFiles', $objProduct)) {
            foreach ($objProduct['colProductFiles'] AS $file) {
                if (!$file->inSession) {
                    $productFileNoSession[] = $file;
                }
            }
        }

        $objForms = new FormsController();
        $colProductAdditionalFields = [];
        if (array_key_exists('productAdditionalFields', $objProduct)) {
            $colProductAdditionalFields = $objForms->getFields($objProduct['productAdditionalFields'], [
                'colProductCategory' => $objProduct['colProductCategory']
            ]);

            $objProduct['productAdditionalFields'] = $colProductAdditionalFields;
        }

        $self->setCacheSaveBlock(); // Do not save cache for Products

        $hasProductDetailFormsCheckout = $this->objSettingsService->getEnvVars('HAS_PRODUCT_DETAIL_FORMS_CHECKOUT');
        return $self->renderSite('product/detail.html.twig', [
            'arProduct'                     => $objProduct,
            'arProductFileNoSession'        => $arProductFileNoSession,
            'productInCart'                 => $productInCart,
            'colProductAdditionalFields'    => $colProductAdditionalFields,
            'hasProductDetailFormsCheckout' => $hasProductDetailFormsCheckout
        ]);
    }

    /**
     * @Route("/product-cart", name="frontoffice_product_cart", methods={"GET"})
     */
    public function productCart(Request $request)
    {
        $return = 'array';
        $arCart = $this->getCart($request, $return);

        if (array_key_exists('productItems', $arCart) && count($arCart['productItems']) === 0) {
            $this->clearCart($request);
/*            return $this->renderSite('product/cart_error.html.twig', [
                'product' => $arCart,
            ]);*/
        }

        $msg = $request->query->get('msg');
        $msg64 = $request->query->get('msg64');

        if ($msg64 === 'true') {
            $msg = base64_decode($msg);
        } else {
            $msg = '';
        }

        if ($arCart['return'] === "error") {
            return $this->json(['return' => "error", 'msg' => 'Error at productCart()']);
        }

        $this->setCacheSaveBlock(); // Do not save cache for Products

        return $this->renderSite('product/cart.html.twig', [
            'msg'                     => $msg,
            'product'                 => $arCart,
        ]);
    }

    /**
     * @Route("/getProductInCart", name="frontoffice_get_product_in_cart", methods={"POST"})
     */
    public function getProductInCart(Request $request)
    {
        $return = 'array';
        $arCart = $this->getCart($request, $return);

        if ($arCart['return'] === "error") {
            return $this->json(['return' => "error", 'msg' => 'Error at productCart()']);
        }

        return $this->json($arCart);
    }

    /**
     * @Route("/getProductItemStock/{productId}", name="frontoffice_order_get_product_item_stock", methods={"POST"})
     */
    public function getProductItemStock(Request $request, $productId)
    {
        $defaultLanguage = $request->getLocale();

        $arProductItemStockItems = ['return' => 'error'];
        if ($data = $this->getAPIData($this->apiUrl . '/api/getProductsById/' . $productId . '/' . $defaultLanguage)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('colProducts', $objData)) {
                    $colProducts = $objData['colProducts'];
                    if (count($colProducts) === 1) {
                        $name = $colProducts[0]['name'];
                        $arProductItemStockItems['colItems'] = $colProducts[0]['colItems'];
                        $arProductItemStockItems['name'] = $name;
                        $arProductItemStockItems['return'] = 'success';
                    }
                }
            }
        }

        return $this->json($arProductItemStockItems);
    }

    /**
     * @Route("/layout-modal-items", name="frontoffice_order_get_layout_modal_items", methods={"GET"})
     */
    public function getLayoutModalItems(Request $request)
    {
        return $this->renderSite('layout-modal-items.html.twig', []);
    }

    /**
     * @Route("/getProductText/{id}", name="frontoffice_order_get_product_text", methods={"POST"})
     */
    public function getProductText(Request $request, $id)
    {
        $defaultLanguage = $request->getLocale();

        $arProductText = [];
        $data = $this->getAPIData($this->apiUrl . '/api/getProductText/' . $id . '/' . $defaultLanguage);
        $objDataProductText = json_decode($data);
        if ($objDataProductText) {
            $arProductText = $objDataProductText;
        }

        $arProductText = json_encode($arProductText);
        $arProductText = json_decode($arProductText, JSON_UNESCAPED_UNICODE);

        return $this->json($arProductText);
    }

    /**
     * @Route("/getProductFile/{id}", name="frontoffice_order_get_product_file", methods={"POST"})
     */
    public function getProductFile(Request $request, $id)
    {
        $defaultLanguage = $request->getLocale();

        $arProductFile = [];
        $data = $this->getAPIData($this->apiUrl . '/api/getProductFile/' . $id . '/' . $defaultLanguage);
        $objDataProductFile = json_decode($data);
        if ($objDataProductFile) {
            $arProductFile = $objDataProductFile;
        }

        $arProductFile = json_encode($arProductFile);
        $arProductFile = json_decode($arProductFile, JSON_UNESCAPED_UNICODE);

        return $this->json($arProductFile);
    }

    /**
     * @Route("/getProductSession/{id}", name="frontoffice_order_get_product_session", methods={"POST"})
     */
    public function getProductSession(Request $request, $id)
    {
        $defaultLanguage = $request->getLocale();

        $arProductSession = [];
        $data = $this->getAPIData($this->apiUrl . '/api/getProductSession/' . $id . '/' . $defaultLanguage);
        $objDataProductSession = json_decode($data);
        if ($objDataProductSession) {
            $arProductSession = $objDataProductSession;
        }

        $arProductSession = json_encode($arProductSession);
        $arProductSession = json_decode($arProductSession, JSON_UNESCAPED_UNICODE);

        return $this->json($arProductSession);
    }

    /**
     * @Route("/getProductDetail/{id}", name="frontoffice_order_get_product_detail", methods={"POST"})
     */
    public function getProductDetail(Request $request, $id)
    {
        $defaultLanguage = $request->getLocale();

        $arProductSession = [];
        $data = $this->getAPIData($this->apiUrl . '/api/getProductDetail?id=' . $id . '&language=' . $defaultLanguage);
        $objDataProductSession = json_decode($data);
        if ($objDataProductSession) {
            $arProductSession = $objDataProductSession;
        }

        $arProductSession = json_encode($arProductSession);
        $arProductSession = json_decode($arProductSession, JSON_UNESCAPED_UNICODE);

        return $this->json($arProductSession);
    }

    /**
     * @Route("/getProductInCategory/{categoryId}", name="frontoffice_order_get_product_in_category", methods={"POST"})
     */
    public function getProductInCategory(Request $request, $categoryId)
    {
        $defaultLanguage = $request->getLocale();
        $colProductInCategory = ProductCategory::getProductsInCategory($this, $categoryId, $defaultLanguage);
        return $this->json($colProductInCategory);
    }

    /**
     * @Route("/addProductToCart", name="frontoffice_order_add_product_to_cart", methods={"POST"})
     */
    function addProductToCart(Request $request, TranslatorInterface $translator)
    {
        $productItemStockId = $request->request->get('id');
        $redirect = $request->request->get('redirect');

        $ret = null;

        if ($productItemStockId) {
            $price = 0;
            $quantity = 1;
            $productId = 0;
            $maxQuantity = -1;
            $allowPriceZero = 0;

            if ($data = $this->getAPIData($this->apiUrl . '/api/getProductInfo?stockId=' . $productItemStockId)) {
                if ($objDataProductInfo = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                    if ($objDataProductInfo) {
                        $price = $objDataProductInfo['price'];
                        $productId = $objDataProductInfo['productId'];
                        $maxQuantity = $objDataProductInfo['maxQuantity'];
                        $allowPriceZero = $objDataProductInfo['allowPriceZero'];
                    }

                    if ($price == 0 && $allowPriceZero == 0) {
                        $msg = $translator->trans('It´s not possible to order a product without price') . '.';
                        $ret = ['return' => "error", 'msg' => $msg];

                    } else if ($maxQuantity > 0) {
                        $arProduct = $this->session->get('product');
                        $arProductItemStock = $this->session->get('product_item_stock');
                        $arProductMaxQuantity = $this->session->get('product_max_quantity');
                        $arProductPrice = $this->session->get('product_price');
                        $arProductQuantity = $this->session->get('product_quantity');

                        $arProduct[$productItemStockId] = $productId;
                        $arProductMaxQuantity[$productItemStockId] = $maxQuantity;
                        $arProductQuantity[$productItemStockId] = $quantity;
                        $arProductPrice[$productItemStockId] = $price;
                        $arProductItemStock[$productItemStockId] = $productItemStockId;

                        $this->session->set('product', $arProduct);
                        $this->session->set('product_item_stock', $arProductItemStock);
                        $this->session->set('product_max_quantity', $arProductMaxQuantity);
                        $this->session->set('product_quantity', $arProductQuantity);
                        $this->session->set('product_price', $arProductPrice);

                        $ret = ['return' => "success", 'msg' => 'product_in_cart'];

                    } else {
                        $msg = $translator->trans('Sorry, we do not have enough “item” in stock to fulfil your order') . '.';
                        $ret = ['return' => "error", 'msg' => $msg];
                    }
                } else {
                    $msg = $translator->trans('Error getting ProductInfo') . '.';
                    $ret = ['return' => "error", 'msg' => $msg];
                }
            } else {
                $msg = $translator->trans('Error getting ProductInfo 2') . '.';
                $ret = ['return' => "error", 'msg' => $msg];
            }

        } else {
            $ret = ['return' => "error", 'msg' => 'no_product_identified'];
        }

        if ($redirect) {
            if (array_key_exists('return', $ret)) {
                $redirect .= '?';
                $redirect .= 'r=' . $ret['return'];
            }

            if (array_key_exists('msg', $ret)) {
                $redirect .= '&';
                if ($ret['return'] === "error") {
                    $redirect .= 'msg=' . base64_encode($ret['msg']) . '&msg64=true';
                } else {
                    $redirect .= 'msg=' . $ret['msg'];
                }
            }

            return $this->redirect($redirect);
        } else {
            return $this->json($ret);
        }
    }

    /**
     * @Route("/deleteProductFromCart", name="frontoffice_order_delete_product_from_cart", methods={"POST"})
     */
    function deleteProductFromCart(Request $request)
    {
        $productItemStockId = $request->request->get('id');
        $redirect = $request->request->get('redirect');

        $ret = null;
        if ($productItemStockId) {
            $newArProduct = [];
            $arProduct = $this->session->get('product');

            $arProductItemStock = [];
            $arProductItemStock = $this->session->get('product_item_stock');

            $newArProductMaxQuantity = [];
            $arProductMaxQuantity = $this->session->get('product_max_quantity');

            $newArProductQuantity = [];
            $arProductQuantity = $this->session->get('product_quantity');

            $newArProductPrice = [];
            $arProductPrice = $this->session->get('product_price');

            $newArProductItemStock = [];

            foreach ($arProduct AS $itemStockId => $productId) {
                if ($itemStockId != $productItemStockId) {
                    $newArProduct[$itemStockId] = $productId;
                }
            }

            foreach ($arProductItemStock AS $itemStockId => $id) {
                if ($itemStockId != $productItemStockId) {
                    $newArProductItemStock[$itemStockId] = $id;
                }
            }

            foreach ($arProductMaxQuantity AS $itemStockId => $maxQuantity) {
                if ($itemStockId != $productItemStockId) {
                    $newArProductMaxQuantity[$itemStockId] = $maxQuantity;
                }
            }

            foreach ($arProductQuantity AS $itemStockId => $quantity) {
                if ($itemStockId != $productItemStockId) {
                    $newArProductQuantity[$itemStockId] = $quantity;
                }
            }

            foreach ($arProductPrice AS $itemStockId => $price) {
                if ($itemStockId != $productItemStockId) {
                    $newArProductPrice[$itemStockId] = $price;
                }
            }

            $this->session->set('product', $newArProduct);
            $this->session->set('product_item_stock', $newArProductItemStock);
            $this->session->set('product_max_quantity', $newArProductMaxQuantity);
            $this->session->set('product_quantity', $newArProductQuantity);
            $this->session->set('product_price', $newArProductPrice);

            //dd($newArProduct, $newArProductItemStock, $newArProductMaxQuantity, $newArProductQuantity, $newArProductPrice);

            $ret = ['return' => "success", 'totalProducts' => count($newArProduct)];
        } else {
            $ret = ['return' => "error", 'msg' => 'no_product_identified'];
        }

        if ($redirect) {
            return $this->redirect($redirect);
        } else {
            return $this->json($ret);
        }
    }

    /**
     * @Route("/getItemsInCart", name="frontoffice_order_get_items_in_cart", methods={"POST"})
     */
    function getItemsInCart()
    {
        $len = $this->getCacheItemsInCart($this->session);
        return $this->json(['return' => "success", 'totalProductInCart' => $len]);
    }

    /**
     * @Route("/getCart", name="frontoffice_order_get_cart", methods={"POST"})
     */
    function getCart(Request $request, $return = null)
    {
        $objCurrency = new Currency();

        $defaultLanguage = $request->getLocale();

        $arProduct = $this->session->get('product');
        $arProductItemStock = $this->session->get('product_item_stock');
        $arProductPrice = $this->session->get('product_price', []);
        $arProductMaxQuantity = $this->session->get('product_max_quantity');
        $arProductQuantity = $this->session->get('product_quantity', []);

        $totalPrice = 0.00;
        $arProductPriceQuantity = [];
        $arListProductItemStock = [];
        $arProductItems = [];

        $arProductById = [];
        $products = '';

        if ($arProduct) {
            foreach ($arProduct AS $key => $val) {
                $products .= $val . ',';
            }
            $products = trim($products, ',');

            $url = $this->apiUrl . '/api/getProductsById/' . $products . '/' . $defaultLanguage;
            if ($data = $this->getAPIData($url)) {
                $objDataProducts = json_decode($data, JSON_UNESCAPED_UNICODE);
                if ($objDataProducts) {
                    $arProductById = $objDataProducts;
                }
            }

            $arStockPriceFromApi = [];
            $arStockMaxQuantityFromApi = [];

            if ($arProductById) {
                if (array_key_exists('colProducts', $arProductById)) {
                    $arProductName = [];
                 
                    foreach ($arProductById['colProducts'] AS $i => $product) {
                        $arProductById['colProducts'][$i]['addedToCart'] = true;

                        if (array_key_exists('colItems', $product)) {
                            foreach ($product['colItems'] AS $item) {
                                $arProductName[$item['id']] = $product['name'];
    
                                foreach ($arProductItemStock AS $productItemStockId) {
                                    if (intval($item['id']) == intval($productItemStockId)) {
                                        $arStockPriceFromApi[$productItemStockId] = doubleval($item['price']);
                                        $arStockMaxQuantityFromApi[$productItemStockId] = intval($item['amount']);

                                        $arProductPrice[$productItemStockId] = doubleval($item['price']);
                                        $arProductMaxQuantity[$productItemStockId] = intval($item['amount']);
                                        $arProductItems[$productItemStockId] = $item;
                                    }
                                }
                            }
                        }
                    }

                    if ($arProductName) {
                        $this->session->set('product_name', $arProductName);
                    }

                    $arProductById = $arProductById['colProducts'];
                }
            }

            foreach ($arProductQuantity AS $productItemStockId => $quantity) {
                $quantity = intval($quantity);
                $maxQuantity = intval($arStockMaxQuantityFromApi[$productItemStockId]);

                if ($quantity > $maxQuantity) {
                    $quantity = $maxQuantity;
                }

                $price = doubleval($arStockPriceFromApi[$productItemStockId]);
                $priceQuantity = $quantity * $price;

                $totalPrice += $priceQuantity;

                $priceQuantity = $objCurrency->round($priceQuantity);
                $priceQuantity = number_format($priceQuantity, 2);

                $arProductPriceQuantity[$productItemStockId] = $priceQuantity;
            }

            $totalPrice = $objCurrency->round($totalPrice);

            $line = 0;
            $arListProductItemStock = [];
            foreach ($arProductItemStock AS $itemStockId) {
                $arListProductItemStock[$line]['productItemStockId'] = $itemStockId;
                foreach ($arProductById AS $product) {
                    if (array_key_exists('colItems', $product)) {
                        foreach ($product['colItems'] AS $item) {
                            if (intval($item['id']) === intval($itemStockId)) {
                                $arListProductItemStock[$line]['productId'] = $product['id'];
                                $arListProductItemStock[$line]['referenceKey'] = $product['referenceKey'];
                                $arListProductItemStock[$line]['name'] = $product['name'];
                                $arListProductItemStock[$line]['color'] = $item['color'];
                                $arListProductItemStock[$line]['size'] = $item['size'];
                                $arListProductItemStock[$line]['price'] = $item['price'];
                                $arListProductItemStock[$line]['quantity'] = $arProductQuantity[$itemStockId];
                                $arListProductItemStock[$line]['productTypeReferenceKey'] = $product['productTypeReferenceKey'];
                                $arListProductItemStock[$line]['filename'] = $product['filename'];
                                $arListProductItemStock[$line]['allowQuantity'] = $product['allowQuantity'];
                                $line++;
                            }
                        }
                    }
                }
            }
        }

        $ret = ['return' => 'success', 
                'productPrice' => $arProductPrice, 
                'productItems' => $arProductItems, 
                'productQuantity' => $arProductQuantity, 
                'productPriceQuantity' => $arProductPriceQuantity, 
                'totalPrice' => $totalPrice, 
                'listProductItemStock' => $arListProductItemStock,
                'product' => $arProduct
        ];

        if ($return === 'array') {
            return $ret;
        } else {
            return $this->json($ret);
        }
    }

    /**
     * @Route("/sendQuantityToCart", name="frontoffice_order_send_quantity_to_cart", methods={"POST"})
     */
    function sendQuantityToCart(Request $request)
    {
        $strId = $request->request->get('id');
        $strId = trim($strId, ',');
        $arId = explode(',', $strId);

        $strQuantity = $request->request->get('quantity');
        $strQuantity = trim($strQuantity, ',');
        $arQuantity = explode(',', $strQuantity);

        if (count($arId) > 0) {
            $arProductQuantity = $this->session->get('product_quantity');
            $arProductMaxQuantity = $this->session->get('product_max_quantity');

            foreach ($arId AS $i => $id) {
                $quantity = $arQuantity[$i];
                $maxQuantity = $arProductMaxQuantity[$id];
                if (is_numeric($quantity) && intval($quantity) > 0 && $quantity <= $maxQuantity) {
                    $arProductQuantity[$id] = $quantity;
                }
            }

            $this->session->set('product_quantity', $arProductQuantity);
        }

        return $this->json(['return' => "success"]);
    }

    /**
     * @Route("/clearCart", name="frontoffice_order_clear_cart", methods={"GET", "POST"})
     */
    function clearCart(Request $request)
    {
        $this->session->remove('product');
        $this->session->remove('product_quantity');
        $this->session->remove('product_item_stock');
        $this->session->remove('product_max_quantity');
        $this->session->remove('product_price');

        return $this->json(['return' => "success"]);
    }
}
