<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 30/10/14
 * Time: 22:35
 */

namespace Meyfarth\CrontaskBundle\Form\Type;

use Meyfarth\CrontaskBundle\Service\CrontaskService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class CrontaskType
 * @package Meyfarth\CrontaskBundle\Form\Type
 */
class CrontaskType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name')
        ->add('isActive', 'checkbox', array(
                'required' => false,
            ))
        ->add('typeInterval', 'choice', array(
                'required' => true,
                'empty_value' => false,
                'choices' => array(
                    CrontaskService::TYPE_INTERVAL_SECONDS => CrontaskService::LABEL_INTERVAL_SECONDS,
                    CrontaskService::TYPE_INTERVAL_MINUTES => CrontaskService::LABEL_INTERVAL_MINUTES,
                    CrontaskService::TYPE_INTERVAL_HOURS => CrontaskService::LABEL_INTERVAL_HOURS,
                )
            ))
        ->add('commandInterval', 'number', array(
                'required' => true,
            ))
        ->add('commands', 'collection', array(
                'type' => 'text',
                'allow_add' => true,
                'allow_delete' => true,

            ))
        ->add('firstRun');
    }


    /**
     * @return string
     */
    public function getName(){
        return 'meyfarth_crontask';
    }

} 