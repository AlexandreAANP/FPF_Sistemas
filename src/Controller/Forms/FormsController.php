<?php

namespace App\Controller\Forms;

class FormsController
{
    public function getFields($objAdditionalFields, $options = null) {
        $colAdditionalFields = ['detail' => [], 'checkout' => [], 'payment' => []];

        if (array_key_exists('product_detail', $objAdditionalFields)) {
            $colAdditionalFields['detail'][] = $objAdditionalFields['product_detail'];
            unset($objAdditionalFields['product_detail']);
        }
        if (array_key_exists('product_type_detail', $objAdditionalFields)) {
            $colAdditionalFields['detail'][] = $objAdditionalFields['product_type_detail'];
            unset($objAdditionalFields['product_type_detail']);
        }
        if (array_key_exists('product_type_payment', $objAdditionalFields)) {
            $colAdditionalFields['payment'][] = $objAdditionalFields['product_type_payment'];
            unset($objAdditionalFields['product_type_payment']);
        }

        if (array_key_exists('product_checkout', $objAdditionalFields)) {
            $colAdditionalFields['checkout'][] = $objAdditionalFields['product_checkout'];
            unset($objAdditionalFields['product_checkout']);
        }
        if (array_key_exists('product_type_checkout', $objAdditionalFields)) {
            $colAdditionalFields['checkout'][] = $objAdditionalFields['product_type_checkout'];
            unset($objAdditionalFields['product_type_checkout']);
        }
        if (array_key_exists('product_type_payment', $objAdditionalFields)) {
            $colAdditionalFields['payment'][] = $objAdditionalFields['product_type_payment'];
            unset($objAdditionalFields['product_type_payment']);
        }

        if ($options && array_key_exists('colProductCategory', $options)) {
            $colProductCategory = $options['colProductCategory'];
            foreach ($colProductCategory AS $category) {
                if (array_key_exists('product_category_' . $category['id'] . '_detail', $objAdditionalFields)) {
                    $colAdditionalFields['detail'][] = $objAdditionalFields['product_category_' . $category['id'] . '_detail'];
                    unset($objAdditionalFields['product_category_' . $category['id'] . '_detail']);
                }
                if (array_key_exists('product_category_' . $category['id'] . '_checkout', $objAdditionalFields)) {
                    $colAdditionalFields['checkout'][] = $objAdditionalFields['product_category_' . $category['id'] . '_checkout'];
                    unset($objAdditionalFields['product_category_' . $category['id'] . '_checkout']);
                }
                if (array_key_exists('product_category_' . $category['id'] . '_payment', $objAdditionalFields)) {
                    $colAdditionalFields['payment'][] = $objAdditionalFields['product_category_' . $category['id'] . '_payment'];
                    unset($objAdditionalFields['product_category_' . $category['id'] . '_payment']);
                }
            }
        }

        $colAdditionalFields = array_merge($colAdditionalFields, $objAdditionalFields);

        if (!count($colAdditionalFields['detail'])) {
            unset($colAdditionalFields['detail']);
        }
        if (!count($colAdditionalFields['checkout'])) {
            unset($colAdditionalFields['checkout']);
        }
        if (!count($colAdditionalFields['payment'])) {
            unset($colAdditionalFields['payment']);
        }

        return $colAdditionalFields;
    }

}
