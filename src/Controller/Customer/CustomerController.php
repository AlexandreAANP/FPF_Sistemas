<?php

namespace App\Controller\Customer;

use App\Controller\Product\ProductController;
use App\Functions\Validation;
use App\Controller\SiteCacheController;
use App\Service\SettingsService;
use App\Template\Layout;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CustomerController extends SiteCacheController
{
    public $apiUrl = '';
    public $siteUrl = '';

    public $params = null;
    public $request = null;
    public $requestStack = null;
    public $session = null;

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

        $defaultLanguage = $this->request->getLocale();
        $uriLanguage = $uri;

        if (substr($uriLanguage, 0, strlen($defaultLanguage) + 2) === '/' . $defaultLanguage . '/') {
            $uriLanguage = substr($uriLanguage, strlen($defaultLanguage) + 1);
        }

        if (is_integer(strpos($uriLanguage, '?'))) {
            $uriLanguage = substr($uriLanguage, 0, strpos($uriLanguage, '?'));
        }

        if ((!is_numeric($customerId) || $customerId === 0)
            && $uriLanguage !== '/customer/login' && $uriLanguage !== '/login' && substr($uriLanguage, 0, 7) !== '/login/'
            && $uriLanguage !== '/customer/signup' && $uriLanguage !== '/signup'
            && $uriLanguage !== '/customer/recover-password' && $uri !== '/recover-password'
            && $uriLanguage !== '/customer/reset-password' && $uriLanguage !== '/reset-password'
            && strpos($uriLanguage, '/customer/hash/') === false
        ) {
            header('location: /customer/login');
            exit();
        }
    }

    /**
     * @Route("/customer", name="frontoffice_customer_index", methods={"GET"})
     */
    public function index(Request $request)
    {
        $customerId = $this->session->get('customerId');

        $profilePicture = '/assets/images/profile.png';
        $profilePictureOrigin = $profilePicture;
        $getData = $this->setAPIData($this->apiUrl . '/api/getProfileDriveCustomer/' . $customerId, []);

        if ($getData) {
            $arData = json_decode($getData, JSON_UNESCAPED_UNICODE);
            if ($arData['profileDriveCustomerUrl']) {
                $profilePicture = $this->apiUrl . '/data/customer/' . $arData['profileDriveCustomerUrl'];
            }
        }

        if ($profilePicture === $profilePictureOrigin) {
            $profilePicture = $this->siteUrl . $profilePicture;
        }

        if (false === @file_get_contents($profilePicture, 0, null, 0, 1)) {
            $profilePicture = $profilePictureOrigin;
        }

        $customerName = $this->session->get('customerName');

        $this->setCacheSaveBlock(); // Do not save cache for Customer

        return $this->renderSite('customer/index.html.twig', [
            'customerId' => $customerId,
            'customerName' => $customerName,
            'profilePicture' => $profilePicture,
        ]);
    }

    /**
     * @Route("/customer/login/{redirect?}", name="frontoffice_customer_login", methods={"GET", "POST"}, requirements={"redirect": "^.*"})
     * @Route("/login/{redirect?}", name="frontoffice_customer_login_shortcut", methods={"GET", "POST"}, requirements={"redirect": "^.*"})
     */
    public function login(Request $request, $redirect)
    {
        if ($reqRedirect = $request->request->get('redirect')) {
            $redirect = $reqRedirect;
        }

        if ($this->isPost()) {
            $objValidation = new Validation();

            $email = $request->request->get('email');
            $password = $request->request->get('password');

            // reCAPTCHA Google ---------------------------- //
/*


            $recaptcha = $request->request->get('g-recaptcha-response');

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

            $data = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $recaptcha . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
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
            // --------------------------------------------- //
*/
            $retValidation = [];
            if ($ret = $objValidation->email($email)) {
                $retValidation[] = $ret;
            }

            if ($retValidation) {
                return $this->json(['return' => 'error', 'msg' => $retValidation]);
            }

            $arData = [
                'email' => $email,
                'password' => $password,
                'customerType' => 'customer'
            ];

            if ($authCustomer = $this->setAPIData($this->apiUrl . '/api/authCustomer', $arData)) {
                 $authCustomer = json_decode($authCustomer, JSON_UNESCAPED_UNICODE);

                if ($authCustomer['return'] === 'success') {
                    $customerId = $authCustomer['customerId'];
                    if (is_numeric($customerId) && $customerId > 0) {
                        $customerName = $authCustomer['customerName'];
                        $this->session->set('customerId', $customerId);
                        $this->session->set('customerName', $customerName);

                        $redirectUrl = '';
                        //$redirect = 'aHR0cDovL3d3dy5nb29nbGUuY29t';
                        $objValidation = new Validation();
                        if ($redirect && $objValidation->is_base64($redirect)) {
                            $redirectUrl = base64_decode($redirect);

                        } else if ($redirect) {
                            $redirectUrl = $redirect;
                        }

                        return $this->json(['return' => 'success', 'redirectUrl' => $redirectUrl]);

                    } else {
                        return $this->json(['return' => 'error', 'msg' => 'user not found']);
                    }
                }

                return $this->json($authCustomer);
            }
        }

        $this->setCacheSaveBlock(); // Do not save cache for Customer

        return $this->renderSite('customer/login.html.twig', [
            'redirect' => $redirect
        ]);
    }

    /**
     * @Route("/customer/signup", name="frontoffice_customer_signup", methods={"GET", "POST"})
     * @Route("/signup", name="frontoffice_customer_signup_shortcut", methods={"GET", "POST"})
     */
    public function signup(Request $request)
    {
        $defaultLanguage = $request->getLocale();

        if ($this->isPost()) {
            // reCAPTCHA Google ---------------------------- //
           /* $recaptcha = $request->request->get('g-recaptcha-response');

            $secretKey = '';
            $arSecretKey = $this->objSettingsService->getSettingsVars('GOOGLE_CAPTCHA_SECRET_KEY_V2');
            if (is_array($arSecretKey)) {
                $arLanguages = $this->objSettingsService->getEnvVars('SUPPORTED_LOCALES');
                $localePosition = array_search($defaultLanguage, $arLanguages);

                if (isSet($arSecretKey[$localePosition])) {
                    $secretKey = $arSecretKey[$localePosition];
                }
            }

            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) . '&response=' . urlencode($recaptcha);
            $response = file_get_contents($url);
            $arRet = json_decode($response, true);

            if ($arRet && array_key_exists('success', $arRet) && $arRet['success'] == false) {
                if (array_key_exists('error-codes', $arRet) && is_array($arRet['error-codes']) && count($arRet['error-codes']) > 0) {
                    return $this->json([
                        'return' => 'error',
                        'msg' => 'reCaptcha: ' . $arRet['error-codes'][0]
                    ]);
                }
            }*/
            // --------------------------------------------- //

            $objValidation = new Validation();

            $name = $request->request->get('name');
            $phone = $request->request->get('phone');
            $email = $request->request->get('email');

            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            $retValidation = [];
            if ($ret = $objValidation->email($email)) {
                $retValidation[] = $ret;
            }

            if ($ret = $objValidation->password($password, $passwordConfirm)) {
                $retValidation[] = $ret;
            }

            if ($retValidation) {
                return $this->json(['return' => 'error', 'msg' => $retValidation]);
            }

            $arContact = [];
            $arContact[] = ['type' => 'phone', 'value' => $phone];
            $arContact[] = ['type' => 'email', 'value' => $email];

            $arData = [
                'name' => $name,
                'colContactData' => $arContact,
                'email' => $email,
                'password' => $password,
                'customerType' => 'customer'
            ];

            if ($addCustomer = $this->setAPIData($this->apiUrl . '/api/addCustomer', $arData)) {
                $addCustomer = json_decode($addCustomer, JSON_UNESCAPED_UNICODE);

                if ($addCustomer['return'] === 'success') {
                    // WELCOME EMAIL MESSAGE --------------------------------------------------------- //
                    $arFields = [
                        'NAME' => $name,
                        'EMAIL' => $email,
                    ];

                    $arData = [
                        'language' => $defaultLanguage,
                        'referenceKey' => 'emailtemplate-customer-signup-email-message',
                        'fields' => $arFields,
                        'mailTo' => $email
                    ];
                    $this->setAPIData($this->apiUrl . '/api/sendEmailTemplate', $arData);
                    // ------------------------------------------------------------------------------- //

                    $id = $addCustomer['id'];
                    if (is_numeric($id) && $id > 0) {
                        $this->session->set('customerId', $id);
                        $this->session->set('customerName', $name);

                        return $this->json(['return' => 'success']);
                    } else {
                        return $this->json(['return' => 'error', 'msg' => 'id not found']);
                    }
                }
                return $this->json($addCustomer);
            }
        }

        $this->setCacheSaveBlock(); // Do not save cache for Customer

        return $this->renderSite('customer/signup.html.twig', [
            //
        ]);
    }

    /**
     * @Route("/customer/recover-password", name="frontoffice_customer_recover_password", methods={"GET", "POST"})
     * @Route("/recover-password", name="frontoffice_customer_recover_password_shortcut", methods={"GET", "POST"})
     */
    public function recoverPassword(Request $request)
    {
        $defaultLanguage = $request->getLocale();

        if ($this->isPost()) {
            $objValidation = new Validation();

            $email = $request->request->get('email');

            $retValidation = [];
            if ($ret = $objValidation->email($email)) {
                $retValidation[] = $ret;
            }

            if ($retValidation) {
                return $this->json(['return' => 'error', 'msg' => $retValidation]);
            }

            $arData = [
                'email' => $email,
                'language' => $defaultLanguage
            ];

            if ($data = $this->setAPIData($this->apiUrl . '/api/recoverPasswordCustomer', $arData)) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);

                if ($objData && array_key_exists('return', $objData)) {
                    if ($objData['return'] === 'success') {
                        return $this->redirect($this->baseUri . '/customer/recover-password?msg=sent');
                    } else if ($objData['return'] === 'error') {
                        return $this->redirect($this->baseUri . '/customer/recover-password?msg=error&t=' . $data['msg']);
                    }
                } else {
                    return $this->redirect($this->baseUri . '/customer/recover-password?msg=error&t=unknown');
                }
            }
        }

        $this->setCacheSaveBlock(); // Do not save cache for Customer

        $msg = $request->query->get('msg');
        return $this->renderSite('customer/recover-password.html.twig', [
            'msg' => $msg,
        ]);
    }

    /**
     * @Route("/customer/reset-password", name="frontoffice_customer_recover_password_reset", methods={"GET"})
     * @Route("/reset-password", name="frontoffice_customer_recover_password_reset_shortcut", methods={"GET"})
     */
    public function recoverPasswordReset(Request $request)
    {
        $password = $request->get('pwd');
        $email = $request->get('email');

        $arData = [
            'email' => $email,
            'password' => $password
        ];

        $arVars = [];
        if ($data = $this->setAPIData($this->apiUrl . '/api/resetPasswordValidationCustomer', $arData)) {
            if ($objData = json_decode($data, JSON_UNESCAPED_UNICODE)) {
                $this->setCacheSaveBlock(); // Do not save cache for Customer

                $arVars = $objData;
                $arVars['email'] = base64_decode($email);
                $arVars['passwordRecover'] = $password;
            }
        }

        return $this->renderSite('customer/recover-password-reset.html.twig', $arVars);
    }

    /**
     * @Route("/customer/reset-password", name="frontoffice_customer_reset_password", methods={"POST"})
     */
    public function resetPassword(Request $request)
    {
        $objValidation = new Validation();

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('password_confirm');
        $passwordRecover = $request->request->get('password_recover');

        $retValidation = [];
        if ($ret = $objValidation->password($password, $passwordConfirm)) {
            $retValidation[] = $ret;
        }

        if ($retValidation) {
            return $this->json(['return' => 'error', 'msg' => $retValidation]);
        }

        $arData = [
            'email' => $email,
            'password' => $password,
            'passwordRecover' => $passwordRecover
        ];

        $validation = $this->setAPIData($this->apiUrl . '/api/resetPasswordCustomer', $arData);
        if ($validation) {
            $validation = json_decode($validation, JSON_UNESCAPED_UNICODE);
        }

        return $this->json($validation);
    }

    /**
     * @Route("/customer/my-account", name="frontoffice_customer_my_account", methods={"GET", "POST"})
     */
    public function myAccount(Request $request)
    {
        $defaultLanguage = $request->getLocale();

        $customerId = $this->session->get('customerId');

        if ($customerId) {
            if ($this->isPost()) {
                $action = $request->request->get('action');

                $arData = [];

                if ($action === 'add_contact') {
                    $type = $request->request->get('type');
                    $contact = $request->request->get('contact');
                    $isMain = $request->request->get('isMain');

                    $arData = [
                        'action' => $action,
                        'customerId' => $customerId,
                        'settingsContactTypeId' => $type,
                        'value' => $contact,
                        'main' => $isMain ? true : false
                    ];

                    $arReturn = [];
                    if ($data = $this->setAPIData($this->apiUrl . '/api/addCustomerContact', $arData)) {
                        $arReturn = json_decode($data, JSON_UNESCAPED_UNICODE);
                    }

                    return $this->json($arReturn);

                } else if ($action === 'update_data') {
                    $name = $request->request->get('name');
                    $taxNumber = $request->request->get('taxNumber');
                    $email = $request->request->get('email');

                    $arData = [
                        'action' => $action,
                        'customerId' => $customerId,
                        'name' => $name,
                        'taxNumber' => $taxNumber,
                        'email' => $email
                    ];

                } else if ($action == 'update_address' || $action == 'add_address') {
                    $addressId = $request->request->get('addressId');
                    $line1 = $request->request->get('line1');
                    $line2 = $request->request->get('line2');
                    $city = $request->request->get('city');
                    $state = $request->request->get('state');
                    $country = $request->request->get('country');
                    $postalCode = $request->request->get('postalCode');
                    $addressType = $request->request->get('addressType');
                    $main = $request->request->get('main');

                    $arData = [
                        'action' => $action,
                        'customerId' => $customerId,
                        'addressId' => $addressId,
                        'line1' => $line1,
                        'line2' => $line2,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'postalCode' => $postalCode,
                        'settingsAddressTypeId' => $addressType,
                        'main' =>  $main ? true : false,
                    ];
                    if ($action === 'update_address'){
                        $data = $this->setAPIData($this->apiUrl . '/api/updateCustomerAddress', $arData);
                        $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                        return $this->json($data);
                    }
                    if ($action === 'add_address'){
                        $data = $this->setAPIData($this->apiUrl . '/api/addCustomerAddress', $arData);
                        $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                        return $this->json($data);
                    }
                }

                else if ($action === 'add_invoice' || $action === 'update_invoice') {
                    $invoiceId = $request->request->get('invoiceId');
                    $line1 = $request->request->get('line1');
                    $line2 = $request->request->get('line2');
                    $city = $request->request->get('city');
                    $state = $request->request->get('state');
                    $country = $request->request->get('country', 177);
                    $postalCode = $request->request->get('postalCode');
                    $taxNumber = $request->request->get('taxNumber');
                    $mainBilling = $request->request->get('mainBilling');
                    $nameToInvoice = $request->request->get('nameToInvoice');
                    $alias = $request->request->get('alias');

                    $arData = [
                        'invoiceId' => (int)$invoiceId,
                        'alias' => $alias,
                        'customerId' => (int)$customerId,
                        'line1' => $line1,
                        'line2' => $line2 ?? '',
                        'city' => $city,
                        'state' => $state,
                        'country' => (int)$country,
                        'countryId' => (int)$country,
                        'postalCode' => $postalCode,
                        'nameToInvoice' => $nameToInvoice,
                        'taxNumber' => $taxNumber,
                        'isMainBilling' => $mainBilling ? true : false,
                    ];

                    if ($action === 'add_invoice'){
                        $data = $this->setAPIData($this->apiUrl . '/api/addCustomerInvoice', $arData);
                        $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                        return $this->json($data);
                    }

                    $data = $this->setAPIData($this->apiUrl . '/api/updateCustomerInvoice/'.$invoiceId, $arData);
                    $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                    return $this->json($data);


                } else if (substr($action, 0, strlen('update_contact_')) === 'update_contact_') {
                    $typeId = $request->request->get('contact_settings_contact_type_id');

                    $type = substr($action, strrpos($action, '_') + 1);

                    $contactId = $request->request->get('contact_id');
                    $contactValue = $request->request->get('contact_value');
                    $contactIsMain = $request->request->get('contact_isMain');

                    $arContact = [];
                    foreach ($contactValue as $key => $val) {
                        $arContact[] = ['id' => $contactId[$key], 'type' => $type, 'value' => $val, 'main' => (($contactIsMain[0] ?? $contactId[0] ) == $contactId[$key] ? true : false), 'settings_contact_type_id' => $typeId];
                    }

                    $arData = [
                        'action' => $action,
                        'customerId' => $customerId,
                        'colContactData' => $arContact
                    ];

                } else if ($action == 'update_password') {
                    $objValidation = new Validation();

                    $passwordCurrent = $request->request->get('password_current');
                    $password = $request->request->get('password');
                    $passwordConfirm = $request->request->get('password_confirm');

                    $validatePassword = $objValidation->password($password, $passwordConfirm);
                    if (is_string($validatePassword)) {
                        return $this->json(['return' => 'error', 'msg' => $validatePassword]);
                    }

                    $arData = [
                        'action' => $action,
                        'customerId' => $customerId,
                        'passwordCurrent' => $passwordCurrent,
                        'password' => $password
                    ];

                } else if ($action === "delete_invoice"){
                    $data = $this->setAPIData($this->apiUrl . '/api/deleteCustomerInvoice/' . $request->request->get('invoiceCustomerId'), []);
                    $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                    return $this->json($data);

                } else if ($action === "delete_address") {
                    $data = $this->setAPIData($this->apiUrl . '/api/deleteCustomerAddress/' . $request->request->get('id'), []);
                    $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                    return $this->json($data);

                } else if ($action === "delete_contact") {
                    $data = $this->setAPIData($this->apiUrl . '/api/deleteCustomerContact/' . $request->request->get('id'), []);
                    $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                    return $this->json($data);

                } else if ($action === 'add_contact') {
                    $type = $request->request->get('type');
                    $contact = $request->request->get('contact');
                    $isMain = $request->request->get('isMain');

                    $arData = [
                        'action' => $action,
                        'customerId' => $customerId,
                        'settingsContactTypeId' => $type,
                        'value' => $contact,
                        'main' => $isMain ? true : false
                    ];

                    $data = $this->setAPIData($this->apiUrl . '/api/addCustomerContact', $arData);
                    $data = json_decode($data, JSON_UNESCAPED_UNICODE);
                    return $this->json($data);
                }

                if ($updateCustomer = $this->setAPIData($this->apiUrl . '/api/updateCustomer', $arData)) {
                    $updateCustomer = json_decode($updateCustomer, JSON_UNESCAPED_UNICODE);
                    if ($action === 'update_data' && array_key_exists('return', $updateCustomer) && $updateCustomer['return'] === 'success') {
                        $id = $updateCustomer['id'];
                        if (is_numeric($id) && $id > 0) {
                            $this->session->set('customerName', $name);
                        } else {
                            return $this->json(['return' => 'error', 'msg' => 'id not found']);
                        }
                    }

                    return $this->json($updateCustomer);
                }
            }

            $arCustomer = [];
            $colCustomerAddress = [];
            $colCustomerContact = [];
            $extraFields = '';
            $requestFields = 'id,name,email,taxNumber,address_id,address_line1,address_line2,address_city,address_state,address_country,address_main,address_postalCode,contact_email,contact_phone,address_settingsAddressTypeId,contact_settingsContactTypeId';
            $url = $this->apiUrl . '/api/getCustomer/' . $customerId . '?fields=' . $requestFields;
            if ($data = $this->setAPIData($url, [])) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
                if ($objData) {
                    if (array_key_exists('colAddress', $objData)) {
                        $colCustomerAddress = $objData['colAddress'];
                        unset($objData['colAddress']);
                    }

                    if (array_key_exists('colContact', $objData)) {
                        $colCustomerContact = $objData['colContact'];
                        unset($objData['colContact']);
                    }

                    if (array_key_exists('extraFields', $objData)) {
                        $extraFields = $objData['extraFields'];
                    }

                    $arCustomer = $objData;
                }
            }

            $colSettingsContactType = [];
            $url = $this->apiUrl . '/api/getSettingsContactType?language=' . $defaultLanguage;
            if ($data = $this->getAPIData($url)) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('colSettingsContactType', $objData)) {
                    $colSettingsContactType = $objData['colSettingsContactType'];
                }
            }

            $colSettingsAddressType = [];
            $url = $this->apiUrl . '/api/getSettingsAddressType?language=' . $defaultLanguage;
            if ($data = $this->getAPIData($url)) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('colSettingsAddressType', $objData)) {
                    $colSettingsAddressType = $objData['colSettingsAddressType'];

                    $newColSettingsAddressType = [];
                    foreach ($colSettingsAddressType AS $type) {
                        if ($type['referenceKey'] !== 'fiscal') {
                            $newColSettingsAddressType[] = $type;
                        }
                    }

                    $colSettingsAddressType = $newColSettingsAddressType;
                }
            }

            $colGeoCountry = [];
            $url = $this->apiUrl . '/api/getGeoCountry';
            if ($data = $this->getAPIData($url)) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('colGeoCountry', $objData)) {
                    $colGeoCountry = $objData['colGeoCountry'];
                }
            }

            $colGeoPtDistrict = [];
            $url = $this->apiUrl . '/api/getGeoPtDistrict';
            if ($data = $this->getAPIData($url)) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('colGeoPtDistrict', $objData)) {
                    $colGeoPtDistrict = $objData['colGeoPtDistrict'];
                }
            }

            $colGeoPtCouncil = [];
            $url = $this->apiUrl . '/api/getGeoPtCouncil';
            if ($data = $this->getAPIData($url)) {
                $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
                if (array_key_exists('colGeoPtCouncil', $objData)) {
                    $colGeoPtCouncil = $objData['colGeoPtCouncil'];
                }
            }

            $this->setCacheSaveBlock(); // Do not save cache for Customer
          
            return $this->renderSite('customer/my-account.html.twig', [
                'arCustomer' => $arCustomer,
                'colCustomerAddress' => $colCustomerAddress,
                'colCustomerContact' => $colCustomerContact,
                'extraFields' => $extraFields,
                'colSettingsContactType' => $colSettingsContactType,
                'colSettingsAddressType' => $colSettingsAddressType,
                'colGeoCountry' => $colGeoCountry,
                'colGeoPtDistrict' => $colGeoPtDistrict,
                'colGeoPtCouncil' => $colGeoPtCouncil,
            ]);

        } else {
            echo 'Customer Id Not Found';
            exit();
        }
    }

    /**
     * @Route("/customer/support/{status?}", name="frontoffice_customer_support", methods={"GET", "POST"})
     * @Route("/support", name="frontoffice_customer_support_redirect", methods={"GET"})
     */
    public function support(Request $request, $status)
    {
        $defaultLanguage = $request->getLocale();

        if (!$status) {
            $status = '';
        }

        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirect('/customer/login/customer/support');
        }

        $requestType = $_SERVER['REQUEST_METHOD'];
        if ($requestType == 'POST') {
            $language = $request->request->get('language');
            $title = $request->request->get('title');
            $category = $request->request->get('category');
            $order = $request->request->get('order');
            $text = $request->request->get('text');

            $arData = [
                'language' => $language,
                'title' => $title,
                'category' => $category,
                'order' => $order,
                'text' => $text,
                'customer' => $customerId
            ];

            $addSupport = $this->setAPIData($this->apiUrl . '/api/addSupport', $arData);

            $addSupport = json_decode($addSupport, JSON_UNESCAPED_UNICODE);
            return $this->json($addSupport);
        }

        $arData = [
            'status' => $status,
            'language' => $defaultLanguage,
            'customer' => $customerId
        ];

        $objDataSupportList = null;
        $objDataSupportError = null;
        $data = $this->setAPIData($this->apiUrl . '/api/getSupport', $arData);
        if ($data) {
            $objDataSupportList = json_decode($data);
            if ($objDataSupportList === null) {
                if (gettype($data) === 'string') {
                    if (is_integer(strpos($data, '{"return":"error","msg":"url not allowed"}'))) {
                        echo 'URL NOT ALLOWED';
                    }
                }
                dd('API Error');
                exit();
            } else if ($objDataSupportList->return == "success") {
                $objDataSupportList = $objDataSupportList->colSupportTickets;
            } else if ($objDataSupportList->return == "error") {
                $objDataSupportError = $objDataSupportList->msg;
            }
        }

        $data = $this->getAPIData($this->apiUrl . '/api/getSupportCategory/' . $defaultLanguage);
        $objDataSupportCategory = json_decode($data);

        $this->setCacheSaveBlock(); // Do not save cache for Customer

        return $this->renderSite('customer/support.html.twig', [
            'status' => $status,
            'supportCategory' => $objDataSupportCategory->colSupportCategories,
            'supportList' => $objDataSupportList,
            'supportListError' => $objDataSupportError,
        ]);
    }

    /**
     * @Route("/customer/getSupportHistory/{supportId}/{limit?}", name="frontoffice_customer_get_support_history", methods={"GET"})
     */
    public function getSupportHistory(Request $request, $supportId, $limit)
    {
        $defaultLanguage = $request->getLocale();

        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirect('/customer/login/customer/support');
        }

        if (!$limit) {
            $limit = 2;
        }

        $arData = [
            'support' => $supportId,
            'customer' => $customerId,
            'limit' => $limit,
            'language' => $defaultLanguage
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/getSupportHistory', $arData);

        if ($data) {
            $objData = json_decode($data, JSON_UNESCAPED_UNICODE);
            if ($objData['return'] == "success") {
                $return = ['return' => 'success', 'details' => $objData['supportDetails'], 'historyLength' => $objData['historyLength'], 'historyList' => $objData['colHistory']];
                return $this->json($return);
            } else if ($objData['return'] == "error") {
                return $this->json(['return' => 'error', 'msg' => $objData->msg]);
            }
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }

    /**
     * @Route("/customer/addSupportHistory", name="frontoffice_customer_add_support_history", methods={"POST"})
     */
    public function addSupportHistory(Request $request)
    {
        $defaultLanguage = $request->getLocale();

        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirect('/customer/login/customer/support');
        }

        $supportId = $request->request->get('supportId');
        $text = $request->request->get('text');

        $arData = [
            'language' => $defaultLanguage,
            'support' => $supportId,
            'customer' => $customerId,
            'text' => $text
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/addSupportHistory', $arData);
        if ($data) {
            $data = json_decode($data, JSON_UNESCAPED_UNICODE);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }

    /**
     * @Route("/customer/closeSupport", name="frontoffice_customer_close_support", methods={"POST"})
     */
    public function closeSupport(Request $request)
    {
        $defaultLanguage = $request->getLocale();

        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirect('/customer/login/customer/support');
        }

        $supportId = $request->request->get('supportId');

        $arData = [
            'support' => $supportId,
            'customer' => $customerId,
            'language' => $defaultLanguage
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/closeSupport', $arData);

        if ($data) {
            $data = json_decode($data, JSON_UNESCAPED_UNICODE);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }

    /**
     * @Route("/customer/drive", name="frontoffice_customer_drive", methods={"GET", "POST"})
     */
    public function drive(Request $request)
    {
        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirectToRoute('site_customer_login');
        }

        $this->setCacheSaveBlock(); // Do not save cache for Customer

        return $this->renderSite('customer/drive.html.twig', [
            'dir' => $customerId,
        ]);
    }

    /**
     * @Route("/customer/getDrive", name="frontoffice_customer_get_drive", methods={"GET"})
     */
    public function getDrive(Request $request)
    {
        $customerId = $this->session->get('customerId');
        if (!$customerId) {
            return $this->redirectToRoute('site_customer_login');
        }

        $arData = [
            'customer' => $customerId,
        ];

        $data = $this->setAPIData($this->apiUrl . '/api/getDriveCustomer', $arData);
        if ($data) {
            $data = json_decode($data, JSON_UNESCAPED_UNICODE);
            return $this->json($data);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }
    }

    /**
     * @Route("/customer/logout", name="frontoffice_customer_logout", methods={"GET", "POST"})
     * @Route("/logout", name="frontoffice_logout", methods={"GET", "POST"})
     */
    public function logout(Request $request)
    {
        $this->session->remove('customerId');
        $this->session->remove('customerName');
        return $this->redirectToRoute('frontoffice_customer_index');
    }

    /**
    * @Route("/customer/hash/{hash}", name="frontoffice_customer_hash", methods={"GET"})
    */
    public function customerHash($hash)
    {
        $data = $this->setAPIData($this->apiUrl . '/api/customer/hash/'. $hash, []);
        if ($data) {
            $data = json_decode($data, JSON_UNESCAPED_UNICODE);
        } else {
            return $this->json(['return' => 'error', 'no data available']);
        }

        if(array_key_exists('success', $data)){
            if($data['success'] === false) {
                echo $data['msg'];die;
            }
        }

        //login customer
        $customerId = $data['customer']['id'];
        if (is_numeric($customerId) && $customerId > 0) {
            $customerName = $data['customer']['name'];
            $this->session->set('customerId', $customerId);
            $this->session->set('customerName', $customerName);
        }

        //checkout stripe
        if($data['action'] == 'stripe-payment') {
            $this->session->set('col_products', $data['colProducts']);
            $this->session->set('col_discount', $data['colDiscount']);
            $this->session->set('order_info_id', $data['orderInfoId']);
            return $this->redirect('/checkout/stripe');
        }
    }
}
