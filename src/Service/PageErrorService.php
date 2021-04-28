<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class PageErrorService extends AbstractController
{
    public function maintenance(): Response
    {
        $html = file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/templates/_includes/maintenance.html.twig');
        echo $html;
        die;
    }
}
