<?php

namespace Meyfarth\CrontaskBundle\Controller;

use Meyfarth\CrontaskBundle\Entity\Crontask;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CrontaskController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $crontasks = $this->getDoctrine()->getRepository('MeyfarthCrontaskBundle:Crontask')->findAll();

        return $this->render('MeyfarthCrontaskBundle:Crontask:index.html.twig', array(
                'crontasks' => $crontasks,
            ));
    }

    /**
     * Create or update a crontask
     * @ParamConverter("crontask")
     * @param Crontask $crontask
     *
     */
    public function newAction(Crontask $crontask = null){
        $isNew = ($crontask == null);

        //
        if($isNew){
            $crontask = new Crontask();
        }

        $formCrontask = $this->createForm('meyfarth_crontask', $crontask);

        return $this->render('@MeyfarthCrontask/Crontask/new.html.twig', array(
                'formCrontask' => $formCrontask->createView(),
            ));

    }

    /**
     * Delete a crontask
     * @param Crontask $crontask
     * @ParamConverter("crontask")
     */
    public function deleteAction(Crontask $crontask){

    }
}
