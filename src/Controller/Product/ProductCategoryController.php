<?php

namespace App\Controller\Product;

use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProductCategoryController extends SiteCacheController 
{
    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    public static function getCategories(SiteCacheController $parent, $productType, $productCategoryId, $search, $defaultLanguage)
    {
        if ($productCategoryId == null) {
            $productCategoryId = '';
        }

        $pages                = 0;
        $currentPage          = 0;
        $totalRegisters       = 0;
        $colProductCategories = [];
        $objData              = [];

        $url = $parent->apiUrl . '/api/getProductCategories?productType=' . $productType . '&productCategoryId=' . $productCategoryId . '&language=' . $defaultLanguage . '&search=' . urlencode($search);
        if ($data = $parent->getAPIData($url)) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('pages', $objData)) {
            $pages = $objData['pages'];
        }

        if (array_key_exists('currentPage', $objData)) {
            $currentPage = $objData['currentPage'];
        }

        if (array_key_exists('registers', $objData)) {
            $totalRegisters = $objData['registers'];
        }

        if (array_key_exists('colProductCategories', $objData)) {
            $colProductCategories = $objData['colProductCategories'];
        }

        $arPagination = [
            'totalRegisters' => $totalRegisters,
            'pages'          => $pages,
            'currentPage'    => $currentPage,
        ];

        return [
            'colProductCategories'  => $colProductCategories,
            'arPagination' => $arPagination,
        ];
    }

    public static function getCategory(SiteCacheController $parent, $categoryId, $defaultLanguage)
    {
        $arProductCategory  = [];
        $url = $parent->apiUrl . '/api/getProductCategory/' . $categoryId . '/'. $defaultLanguage;
        if ($data = $parent->getAPIData($url)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                $arProductCategory = $objData;
            }
        }

        return $arProductCategory;

        /*

    'urlSelect'         => $request->getPathInfo(),
    'baseUri'           => $this->baseUri,
    'apiUrl'            => $this->apiUrl,
    'referenceKey'      => $referenceKey,
    'cdnUrl'         => 'http://backoffice.test',
    'listProducts'      => $listProducts->list,
    'totalRegisters'    => $totalRegisters,
    'pages'             => $pages,
    'currentPage'       => $currentPage,
    'categories'        => self::getCategories ($this, $request->getLocale()),
    'productItemsInCart'=> $this->productItemsInCart,

 */
    }

    public static function getProductsInCategory(SiteCacheController $parent, $categoryId, $defaultLanguage) {
        $colProductsInCategory = [];
        if ($data = $parent->getAPIData($parent->apiUrl . '/api/getProductInCategory/' . $categoryId . '/' . $defaultLanguage)) {
            $objData = json_decode($data);
            if (array_key_exists('colProductCategories', $objData)) {
                $colProductsInCategory = $objData->colProductCategories ;
            }
        }
        return $colProductsInCategory;
    }

    public static function getProductCategory(SiteCacheController $parent, int $referenceKey, string $defaultLanguage)
    {
        $objData = [];
        $url = $parent->apiUrl . '/api/getProductInCategory/' . $referenceKey . '/' . $defaultLanguage;
        if ($data = $parent->getAPIData($url)) {
            $objData = json_decode($data);
        }

        return $objData;
    }
}
