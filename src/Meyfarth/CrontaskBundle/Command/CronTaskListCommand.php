<?php
/**
 * User: Meyfarth
 * Date: 02/11/14
 * Time: 15:00
 */
namespace Meyfarth\CrontaskBundle\Command;

use Meyfarth\CrontaskBundle\Entity\Crontask;
use Meyfarth\CrontaskBundle\Service\CrontaskService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all the crontasks
 *
 * @author sebastien
 * @package Meyfarth\CrontaskBundle\Command
 */
class CronTaskListCommand extends ContainerAwareCommand{

    protected function configure(){

        $this
            ->setName('mey:crontask:list')
            ->setDescription('List all crontasks')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'If defined, show inactive crontasks')
            ->addOption('details', 'd', InputOption::VALUE_NONE, 'If defined, show inactive crontasks')
        ;
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @todo si force défini sans valeur, prompt un dialog pour demander quelles crontask sont à forcer
     */
    protected function execute(InputInterface $input, OutputInterface $output){
        
        $output->writeln('<comment>List of crontasks</comment>');

        $this->output = $output;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        if($input->getOption('all')){
            $crontasks = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findAll();
        }else{
            $crontasks = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findAllActives();
        }

        foreach($crontasks as $crontask){
            $color = $crontask->getIsActive() ? 'green' : 'red';
            if($input->getOption('details')){
                $table = $this->getHelper('table');
                $table->setHeaders(array('Name', 'Active', 'last run', 'command interval', 'command list'))
                    ->setRows(array(
                            $crontask->getName(), $crontask->getIsActive() ? 'yes' : 'no', $crontask->getLastRun()->format('Y-m-d H:i:s'), $crontask->getCommandInterval().' '.($crontask->getTypeInterval() == CrontaskService::TYPE_INTERVAL_SECONDS ? 's' : ( $crontask->getTypeInterval() == CrontaskService::TYPE_INTERVAL_MINUTES ? 'm' : 'h')), implode(', ', $crontask->getCommands())
                        ));
                $table->render($output);
            }else{
                $output->writeln($crontask->getName()." (<fg=$color>".($crontask->getIsActive() ? 'active' : 'inactive')."</fg=$color>)");
            }
        }
    }
}
