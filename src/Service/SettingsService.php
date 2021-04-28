<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SettingsService {
    public $requestStack;
    public $params;

    // ---- //
    // These parameter need to be NULL because the file /public/site.php use this class
    // ---- //
    public function __construct(ContainerBagInterface $params = null, RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
        $this->params = $params;
    }

    /**
     * Gets the environment variables.
     * @param      string  $name   ENV variable
     * @return     mixed   The environment variables.
     */
    public function getEnvVars(string $name){

        $var = $this->requestStack->getCurrentRequest()->server->get($name);

        if (is_integer(strpos($var, '|'))) {
            $var = explode('|', $var);
        }

        return $var;
    }

    /**
     * Gets the environment variables.
     * @param      string  $name   ENV variable
     * @return     mixed   The environment variables.
     */
    public function getLayoutVars($name = '') {
        $arQueryBizLayout = $this->params->get('QUERYBIZ_LAYOUT');

        if (!$name) {
            return $arQueryBizLayout;

        } else if (array_key_exists($name, $arQueryBizLayout)) {
            $var = $arQueryBizLayout[$name];

            if (substr($var, 0, 1) === '"' &&  substr($var, -1) === '"') {
                $var = substr($var, 1, -1);

                $var = str_replace('" | "', '"|"', $var);
                $var = str_replace('" |"', '"|"', $var);
                $var = str_replace('"| "', '"|"', $var);

                if (is_integer(strpos($var, '"|"'))) {
                    $var = explode('"|"', $var);
                }

                return $var;

            } else if (is_integer(strpos($var, '|'))) {
                $var = explode('|', $var);
            }

            return $var;
        }

        return false;
    }

    public function getSettingsVars($name = '', $arQueryBizSettings = null) {
        if (!$arQueryBizSettings) {
            $arQueryBizSettings = $this->params->get('QUERYBIZ_SETTINGS');
        }

        if (!$name) {
            return $arQueryBizSettings;

        } else if (array_key_exists($name, $arQueryBizSettings)) {
            $var = $arQueryBizSettings[$name];

            if (substr($var, 0, 1) === '"' &&  substr($var, -1) === '"') {
                $var = substr($var, 1, -1);

                $var = str_replace('" | "', '"|"', $var);
                $var = str_replace('" |"', '"|"', $var);
                $var = str_replace('"| "', '"|"', $var);

                if (is_integer(strpos($var, '"|"'))) {
                    $var = explode('"|"', $var);
                }

                return $var;

            } else if (is_integer(strpos($var, '|'))) {
                $var = explode('|', $var);
            }

            return $var;
        }

        return false;
    }

    /**
     * Check if the User has the Option available in Env File
     * @param      string  $name   ENV variable
     * @return     boolean
     */
    public function hasEnvVarSetting(string $name){
        return $this->requestStack->getCurrentRequest()->server->has($name);
    }
}