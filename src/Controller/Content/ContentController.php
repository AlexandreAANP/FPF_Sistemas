<?php

namespace App\Controller\Content;

use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentController extends SiteCacheController
{
    private $arControllerVars = [];
    private $templateDir = '';

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params, RequestStack $requestStack, SessionInterface $session, SettingsService $objSettingsService)
    {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);

        $this->arControllerVars = $params->get('QUERYBIZ_CONTROLLER');
        $this->templateDir = dirname($_SERVER['DOCUMENT_ROOT']);
    }

    /**
     * @Route("/{req}", name="frontoffice_content_post", methods={"GET"}, requirements={"req"="%app.content_type%"})
     */
    public function contentPost(Request $request, $contentType = '', $contentCategoryReferenceKey = null, $parent = null)
    {
        if (!$contentType) {
            $pageInfo = $request->getPathInfo();
            if ($pageInfo) {
                $contentType = substr($pageInfo, 1);
            }
        }

        $contentType = $this->parseLanguageInRouteName($contentType);

        $self = $this;
        if ($parent) {
            $self = $parent;
        }

        $this->setControllerNameMenu('Content' . ucfirst($contentType));

        $defaultLanguage = $request->getLocale();

        $colContentCategory = [];
        $urlParameters = 'content?type=' . $contentType . '&table=category&language=' . $defaultLanguage;
        if (array_key_exists('CONTENT_POST_' .  strToUpper($contentType) . '_CATEGORY', $this->arControllerVars)) {
            $urlParameters = $this->setVarsInString($this->arControllerVars['CONTENT_POST_' .  strToUpper($contentType) . '_CATEGORY'], [
                'defaultLanguage' => $defaultLanguage
            ]);
        }
        if ($data = $this->getAPIData($this->apiUrl . '/api/' . $urlParameters)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('colContent', $objData)) {
                    $colContentCategory = $objData['colContent'];
                }
            }
        }

        $colContent = [];
        if ($contentCategoryReferenceKey) {
            $urlParameters = 'content?type=' . $contentType . '&category=' . $contentCategoryReferenceKey . '&fields=category&language=' . $defaultLanguage;
            if (array_key_exists('CONTENT_POST_' .  strToUpper($contentType) . '_CATEGORY_LIST', $this->arControllerVars)) {
                $urlParameters = $this->setVarsInString($this->arControllerVars['CONTENT_POST_' .  strToUpper($contentType) . '_CATEGORY_LIST'], [
                    'defaultLanguage' => $defaultLanguage,
                    'contentCategoryReferenceKey' => $contentCategoryReferenceKey
                ]);
            }
            if ($data = $this->getAPIData($this->apiUrl . '/api/' . $urlParameters)) {
                if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                    if (array_key_exists('colContent', $objData)) {
                        $colContent = $objData['colContent'];
                    }
                }
            }
        }

        if (!$contentCategoryReferenceKey) {
            $urlParameters = 'content?type=' . $contentType . '&fields=category&language=' . $defaultLanguage;
            if (array_key_exists('CONTENT_POST_' .  strToUpper($contentType) . '_LIST', $this->arControllerVars)) {
                $urlParameters = $this->setVarsInString($this->arControllerVars['CONTENT_POST_' .  strToUpper($contentType) . '_LIST'], [
                    'defaultLanguage' => $defaultLanguage,
                ]);
            }
            if ($data = $this->getAPIData($this->apiUrl . '/api/' . $urlParameters)) {
                if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                    if (array_key_exists('colContent', $objData)) {
                        $colContent = $objData['colContent'];
                    }
                }
            }
        }

        $contentPath = $this->templateDir . '/templates/content/' . $contentType . '.html.twig';
        if (file_exists($contentPath) && is_file($contentPath)) {
            $templateFile = 'content/' . $contentType . '.html.twig';
        } else {
            $templateFile = 'content/_default.html.twig';
        }

        return $self->renderSite($templateFile, [
            'contentType' => $contentType,
            'colContentCategory' => $colContentCategory,
            'colContentList' => $colContent,
            'templateFile' => $templateFile,
        ]);
    }

    /**
     * @Route("/{req}", name="frontoffice_content_category", methods={"GET"}, requirements={"req"="%app.content_type%-category"})
     * @Route("/articles-category", name="frontoffice_content_articles_category", methods={"GET"})
     * @Route("/authors-category", name="frontoffice_content_authors_category", methods={"GET"})
     * @Route("/blogs-category", name="frontoffice_content_blogs_category", methods={"GET"})
     * @Route("/events-category", name="frontoffice_content_events_category", methods={"GET"})
     * @Route("/faq-category", name="frontoffice_content_faq_category", methods={"GET"})
     * @Route("/galleries-category", name="frontoffice_content_galleries_category", methods={"GET"})
     * @Route("/news-category", name="frontoffice_content_news_category", methods={"GET"})
     * @Route("/services-category", name="frontoffice_content_services_category", methods={"GET"})
     */
    public function contentPostCategory(Request $request, $contentType = '')
    {
        if (!$contentType) {
            $pageInfo = $request->getPathInfo();
            if ($pageInfo) {
                $contentType = substr($pageInfo, 1);
            }
        }

        $contentType = $this->parseLanguageInRouteName($contentType);

        $defaultLanguage = $request->getLocale();

        $contentType = substr($contentType, 0, strpos($contentType, '-'));

        $this->setControllerNameMenu('Content' . ucfirst($contentType) . 'Category');

        $colContent = [];
        $url = $this->apiUrl . '/api/content?type=' . $contentType . '&table=category&language=' . $defaultLanguage;
        if ($data = $this->getAPIData($url)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('colContent', $objData)) {
                    $colContent = $objData['colContent'];
                }
            }
        }

        $contentType = str_replace('-', '_', $contentType);

        $contentPath = $this->templateDir . '/templates/content/' . $contentType . '_category.html.twig';
        if (file_exists($contentPath) && is_file($contentPath)) {
            $templateFile = 'content/' . $contentType . '_category.html.twig';
        } else {
            $templateFile = 'content/_default_category.html.twig';
        }

        return $this->renderSite($templateFile, [
            'colContentCategory' => $colContent,
            'contentType' => $contentType
        ]);
    }

    public function contentPostDetail(Request $request, $contentType, $contentPostReferenceKey = null, $parent = null)
    {
        $self = $this;
        if ($parent) {
            $self = $parent;
        }

        $from = $request->query->get('from');

        if (!$contentPostReferenceKey) {
            echo 'ReferenceKey not found';
            exit();
        }

        $defaultLanguage = $request->getLocale();

        $arContent = [];
        $urlParameters = 'content/' . $contentPostReferenceKey . '?fields=category&language=' . $defaultLanguage;
        if (array_key_exists('CONTENT_POST_' .  strToUpper($contentType) . '_DETAIL', $this->arControllerVars)) {
            $urlParameters = $this->setVarsInString($this->arControllerVars['CONTENT_POST_' .  strToUpper($contentType) . '_DETAIL'], [
                'defaultLanguage' => $defaultLanguage,
                'contentPostReferenceKey' => $contentPostReferenceKey,
            ]);
        }

        if ($data = $this->getAPIData($this->apiUrl . '/api/' . $urlParameters)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                $arContent = $objData;
            }
        }

        $contentPath = $this->templateDir . '/templates/content/' . $contentType . '_detail.html.twig';
        if (file_exists($contentPath) && is_file($contentPath)) {
            $templateFile = 'content/' . $contentType . '_detail.html.twig';
        } else {
            $templateFile = 'content/_default_detail.html.twig';
        }

        return $self->renderSite($templateFile, [
            'arContent' => $arContent,
            'from' => $from,
        ]);
    }

    /**
     * @Route("/files", name="frontoffice_content_content_files", methods={"GET"})
     */
    public function contentFiles(Request $request, $filesReferenceKey = null)
    {
        return $this->renderSite('content/files.html.twig', [
        ]);
    }

    public function contentPage($request, $pageReferenceKey, $parent = null)
    {
        $self = $this;
        if ($parent) {
            $self = $parent;
        }

        $defaultLanguage = $request->getLocale();

        $colContent = [];
        if ($data = $self->getAPIData($self->apiUrl . '/api/content/' . $pageReferenceKey . '?fields=category&language=' . $defaultLanguage)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                $colContent = $objData;
            }
        }

        return $self->renderSite('content/pages.html.twig', [
            'arPage' => $colContent,
        ]);
    }

    public function contentBlank($request, $pageReferenceKey, $parent = null)
    {
        $self = $this;
        if ($parent) {
            $self = $parent;
        }

        return $self->renderSite('content/blank.html.twig', [
            'referenceKey' => $pageReferenceKey
        ]);
    }

    public function parseLanguageInRouteName($contentType) {
        if (is_integer(strpos($contentType, '/'))) {
            $arContentType = explode('/', $contentType);
            if (count($arContentType) > 1) {
                $arSupportedLocales = $this->objSettingsService->getEnvVars('SUPPORTED_LOCALES');
                $maybeLanguage = $arContentType[0];
                if (in_array($maybeLanguage, $arSupportedLocales)) {
                    $contentType = $arContentType[1];
                }
            }
        }

        return $contentType;
    }
}