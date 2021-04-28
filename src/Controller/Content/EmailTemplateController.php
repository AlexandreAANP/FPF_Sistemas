<?php

namespace App\Controller\Content;

use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use App\Template\Layout;
use Doctrine\DBAL\DBALException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class EmailTemplateController extends SiteCacheController
{
    public function __construct(
        EntityManagerInterface $em,
        ContainerBagInterface $params,
        RequestStack $requestStack,
        SessionInterface $session,
        SettingsService $objSettingsService
    ) {
        parent::__construct($em, $params, $requestStack, $session, $objSettingsService);
    }

    /**
     * @Route("/sendmailTemplate", name="frontoffice_email_template_sendemail", methods={"POST"})
     */
    public function sendmailTemplate(Request $request): Response
    {
        $contentEmailTemplateReferenceKey = $request->request->get('content_email_template');
        $contentEmailTemplateTable = $request->request->get('content_email_template_table');

        $defaultLanguage = $request->getLocale();

        $recaptchaV2 = $request->request->get('g-recaptcha-response');
        $recaptchaV3 = $request->request->get('g-recaptcha-response-v3');

        if (!$recaptchaV3 && !$recaptchaV2) {
            $recaptchaV2 = 'null';
        }

        // reCAPTCHA ---------------------- //

        // V2
        if ($recaptchaV2) {
            $secretKey = '';
            $arSecretKey = $this->objSettingsService->getSettingsVars('GOOGLE_CAPTCHA_SECRET_KEY_V2');
            if (is_array($arSecretKey)) {
                $arLanguages = $this->objSettingsService->getEnvVars('SUPPORTED_LOCALES');
                $localePosition = array_search($defaultLanguage, $arLanguages);

                if (isSet($arSecretKey[$localePosition])) {
                    $secretKey = $arSecretKey[$localePosition];
                }
            }

            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($recaptchaV2);
            $response = file_get_contents($url);
            $arRet = json_decode($response, true);

            if ($arRet && array_key_exists('success', $arRet) && $arRet['success'] == false) {
                if (array_key_exists('error-codes', $arRet) && is_array($arRet['error-codes']) && count($arRet['error-codes']) > 0) {
                    return $this->json([
                        'return' => 'error',
                        'msg' => $arRet['error-codes'][0]
                    ]);
                }
            }
        }

        // reCaptcha V3
        if ($recaptchaV3) {
            $defaultLanguage = $request->getLocale();

            $secretKey = '';
            $arSecretKey = $this->objSettingsService->getSettingsVars('GOOGLE_CAPTCHA_SECRET_KEY_V3');
            if (is_array($arSecretKey)) {
                $arLanguages = $this->objSettingsService->getEnvVars('SUPPORTED_LOCALES');
                $localePosition = array_search($defaultLanguage, $arLanguages);

                if (isSet($arSecretKey[$localePosition])) {
                    $secretKey = $arSecretKey[$localePosition];
                }
            }

            $data = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $recaptchaV3 . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                if (array_key_exists('success', $objData) && $objData['success'] === false) {

                    $msg = $objData['error-codes'][0];
                    if ($msg == 'timeout-or-duplicate') {
                        $msg = 'Too long time without activity, reload this page';
                    }

                    return $this->json([
                        'return' => 'error',
                        'from' => 'recaptch',
                        'msg' => $msg
                    ]);
                } else if (array_key_exists('success', $objData) && $objData['success'] == true && $objData['score'] <= 0.5) {
                    return $this->json([
                        'return' => 'error',
                        'msg' => 'Invalid Access - reCaptcha score: not qualified'
                    ]);
                }
            }
        }
        // -------------------------------- //

        $this->request->getMethod();    // e.g. GET, POST, PUT, DELETE or HEAD
        $this->request->getLanguages(); // an array of languages the client accepts

        $message = null;
        if ($this->request->getMethod() === 'POST') {
            $arSendmailTo = $this->objSettingsService->getSettingsVars('SENDMAIL_TO');
            if ($arSendmailTo && is_array($arSendmailTo)) {
                $mailTo = $arSendmailTo[0];
            } else {
                $mailTo = '';
            }

            if (!$mailTo) {
                return $this->json(['return' => 'error', 'msg' => 'Variable SENDMAIL_TO not filled in .settings']);
            }

            $arPost = $_POST;
            foreach ($arPost as $field => $value) {
                if ($field !== 'content_email_template' && $field !== 'content_email_template_save') {
                    if (is_array($value) && $field === 'additional_fields') {
                        foreach ($value as $key => $val) {
                            $arFields[strToUpper($key)] = $val;
                        }
                    }

                    $arFields[strToUpper($field)] = $value;
                }
            }

            if (array_key_exists('ADDITIONAL_FIELDS', $arFields)) {
                unset($arFields['ADDITIONAL_FIELDS']);
            }

            $arData = [
                'language' => $defaultLanguage,
                'fields' => $arFields,
                'mailTo' => $mailTo,
                'referenceKey' => $contentEmailTemplateReferenceKey,
                'table' => $contentEmailTemplateTable,
            ];

            $objData = [];
            if ($data = $this->setAPIData($this->apiUrl . '/api/sendEmailTemplate', $arData)) {
                $objData = json_decode($data);
            }
            return $this->json($objData);
        }
    }
}
