<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 16/11/2014
 * Time: 20:44
 */

namespace Meyfarth\CrontaskBundle\Command;


use Doctrine\ORM\NoResultException;
use Meyfarth\CrontaskBundle\Entity\Crontask;
use Meyfarth\CrontaskBundle\Service\CrontaskService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CrontaskDeleteCommand
 * @package Meyfarth\CrontaskBundle\Command
 */
class CrontaskDeleteCommand extends ContainerAwareCommand {

    protected function configure(){
        $this->setName('mey:crontask:delete')
            ->setDescription('Deletes a crontask')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the crontask you want to delete');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output){



        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $name = $input->getArgument('name');

        // Check if the name doesn't already exists
        try{
            $crontask = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findOneBy(array('name' => $name));
        }catch(NoResultException $e){
            $output->writeln('<error>There already is a crontask with this name</error>');
            exit();
        }
        if(!$crontask){
        }

        $dialog = $this->getHelperSet()->get('dialog');

        // Confirm generation
        $output->writeln('');
        if($dialog->askConfirmation(
            $output,
            sprintf('<question>Confirm deletion of the "%s" crontask ? This action is definitive.</question>', $name),
            false
        )){
            $em->remove($crontask);
            $em->flush();
            $output->writeln('<fg=green>Crontask deleted</fg=green>');
        }else{
            $output->writeln('<fg=red>Crontask deletion aborted</fg=red>');
        }
    }
}