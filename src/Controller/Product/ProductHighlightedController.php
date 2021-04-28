<?php

namespace App\Controller\Product;

use App\Controller\SiteCacheController;
use App\Controller\HighLightedController as HighLighteds;
use Doctrine\DBAL\DBALException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductHighlightedController
{
    /**
     *  
     * @param SiteCacheController $scc        
     * @param String $productType
     * @param String $options 
     * @return ?array
     */
    public static function get(SiteCacheController $parent, $productType, $categoryId, $defaultLanguage)
    {
        if (!$categoryId) {
            $categoryId = '0';
        }

        if (is_array($productType)) {
            if (in_array('all', $productType)) {
                $productType = '';
            } else {
                $productType = implode(',', $productType);
            }
        } else if ($productType === 'all') {
            $productType = '';
        }

        $objData = [];
        $url = $parent->apiUrl . '/api/getProductHighlighted?productType=' . $productType . '&categoryId=' . $categoryId . '&language=' . $parent->request->getLocale();
        if ($data = $parent->getAPIData($url)) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
        }

        return $objData;
    }
}
