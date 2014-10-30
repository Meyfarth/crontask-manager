<?php

namespace Meyfarth\CrontaskBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('MeyfarthCrontaskBundle:Default:index.html.twig', array('name' => $name));
    }
}
