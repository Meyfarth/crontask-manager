<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 30/10/14
 * Time: 22:36
 * Inspired from http://inuits.eu/blog/creating-automated-interval-based-cron-tasks-symfony2
 */

namespace Meyfarth\CrontaskBundle\Command;


use Meyfarth\CrontaskBundle\Entity\Crontask;
use Meyfarth\CrontaskBundle\Service\CrontaskService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class CrontaskRunCommand
 * @package Meyfarth\CrontaskBundle\Command
 */
class CrontaskUpdateCommand extends ContainerAwareCommand {

    private $dialog;
    private $output;

    protected function configure(){
        $this->setName('mey:crontask:update')
            ->setDescription('Creates a new crontask')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the existing crontask')
            ->addOption('is-active', null, InputOption::VALUE_REQUIRED, 'Set to 1 to activate the crontask, 0 to deactivate')
            ->addOption('interval-type', null, InputOption::VALUE_REQUIRED, 'Set the interval type to (h)ours, (m)inutes or (s)econds')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Set the time between two runs (depending on the interval type)')
            ->addOption('first-run', null, InputOption::VALUE_REQUIRED, 'Set the first run. If the crontask has already run, it will set the next run. Format "Y-m-d H:i"')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Set the name of the crontask')
        ->setHelp(<<<EOT
The <info>%command.name%</info> command updates a crontask by its name.

<info>php %command.full_name% name</info>

The following options are available :
<info>--is-active=[0|1]</info> - 0 to deactivate the crontask, 1 to activate it

<info>--interval-type</info> - to set the interval type. Allowed values are :
h, hour, hours, m, min, minute, minutes, s, sec, second, seconds

<info>--interval</info> - interval between two runs (depending on interval type)

<info>--first-run</info> - set the time of the first run. If the crontask has
already been executed, it will set the time of the next run.

<info>--name</info> - set the new name of the crontask


<info> %command.name% "crontask name" --is-active=1 --interval-type="h" --interval="24"</info>
will activate the crontask "crontask name" and configure it to be executed every 24 hours.

EOT
)
            ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output){

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $name = $input->getArgument('name');
        $nextName = $input->getOption('name');

        $isActive = $input->getOption('is-active');
        $intervalType = $input->getOption('interval-type');
        $interval = $input->getOption('interval');
        $firstRun = $input->getOption('first-run');

        // Check if the name doesn't already exists
        $crontask = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findOneBy(array('name' => $name));
        if(!$crontask){
            $output->writeln('<error>No crontask found</error>');
            exit();
        }

        // Check data types
        if($isActive != null){
            if(!in_array($isActive, array(0,1))){
                $output->writeln('<error>is-active must be either 0 (inactive) or 1 (active)');
                exit();
            }
        }

        if($intervalType != null){
            $allowedIntervalType = array('h','hour', 'hours', 'm', 'min', 'minute', 'minutes', 's', 'sec', 'second', 'seconds');
            if(!in_array($intervalType, $allowedIntervalType)){
                $output->writeln(sprintf('<error>The interval type must be one of the following values : "%s"</error>', implode('", "', $allowedIntervalType)));
                exit();
            }
        }


        if($interval != null){
            if(!ctype_digit($interval)){
                $output->writeln('<error>The interval must be an integer</error>');
                exit();
            }
        }


        if($firstRun != null){
            if(!CrontaskService::ValidateDate($firstRun, CrontaskService::DATE_FORMAT)){
                $output->writeln('<error>The first-run must use the "Y-m-d H:i" format</error>');
                exit();
            }
        }

        $confirm = '<question>Confirm update ... </question>';

        $outputConfirmation = array();

        if($nextName != null){
            $outputConfirmation[] = sprintf('Name: %s => %s', $crontask->getName(), $nextName);
        }

        if($isActive != null){
            $outputConfirmation[] = sprintf('Active: %s => %s', $crontask->getIsActive() ? 'active' : 'inactive', $isActive ? 'active' : 'inactive');
        }

        if($intervalType != null){
            $outputConfirmation[] = sprintf('Interval type: %s => %s', CrontaskService::convertFromTypeInterval($crontask->getIntervalType()), CrontaskService::convertFromTypeInterval(CrontaskService::convertToIntervalType($intervalType)));
        }

        if($interval != null){
            $outputConfirmation[] = sprintf('Interval: %s => %s', $crontask->getCommandInterval(), $interval);
        }
        if($firstRun != null){
            $outputConfirmation[] = sprintf('First run: %s => %s', $crontask->getFirstRun()->format(CrontaskService::DATE_FORMAT), $firstRun);
        }

        if(count($outputConfirmation)== 0){
            $output->writeln('<error>Nothing to update</error>');
        }

        $confirm .= implode(', ', $outputConfirmation).' ? ';

        $this->dialog = $this->getHelperSet()->get('dialog');

        // Confirm generation
        $output->writeln('');
        if($this->dialog->askConfirmation(
            $output,
            $confirm,
            false
        )){
            $crontask
                ->setName($nextName != null ? $nextName : $crontask->getName())
                ->setIsActive($isActive != null ? $isActive : $crontask->getIsActive())
                ->setIntervalType($intervalType != null ? CrontaskService::convertToIntervalType($intervalType): $crontask->getIntervalType())
                ->setCommandInterval($interval != null ? $interval : $crontask->getCommandInterval())
                ->setFirstRun($firstRun != null ? new \DateTime($firstRun) : $crontask->getFirstRun())
                ->setLastRun($firstRun != null ? null : $crontask->getLastRun());
            $em->persist($crontask);
            $em->flush();
        }else{
            $output->writeln('<fg=red>Crontask update aborted</fg=red>');
        }
    }
}