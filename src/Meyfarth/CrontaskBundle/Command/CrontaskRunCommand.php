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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CrontaskRunCommand
 * @package Meyfarth\CrontaskBundle\Command
 */
class CrontaskRunCommand extends ContainerAwareCommand {
    private $output;

    protected function configure(){
        $this->setName('meyfarth:crontask:run')
            ->setDescription('Runs Crontasks')
            ->addOption('force', null, InputOption::VALUE_REQUIRED |InputOption::VALUE_IS_ARRAY, 'Forces crontasks even if inactive or if the lastrun is outdated')
            ->addOption('ignore', null, InputOption::VALUE_REQUIRED |InputOption::VALUE_IS_ARRAY, 'Ignores crontask even if it should be run');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output){
        $output->writeln('<comment>Running crontasks</comment>');

        $this->output = $output;

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $crontasks = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findAll();

        $forces = $input->getOption('force');
        $ignores = $input->getOption('ignore');

        $isForceOption = (is_array($forces) && count($forces) > 0);

        // Lower force names
        foreach($forces as &$force){
            $force = strtolower($force);
        }

        $isIgnoreOption = (is_array($ignores) && count($ignores) > 0);

        // lower ignore names
        foreach($ignores as &$ignore){
            $ignore = strtolower($ignore);

        }

        // If ignore AND force for the same crontask, it'll be ignored
        foreach($forces as $key => $force){
            if(in_array($force, $ignores)){
                unset($forces[$key]);
            }
        }

        // Parse each crontask
        foreach($crontasks as $crontask){

            if($isIgnoreOption && in_array(strtolower($crontask->getName()), $ignores)){
                sprintf('Ignoring crontas <info>%s</info>', $crontask);
            }

            if($isForceOption && in_array(strtolower($crontask->getName()), $forces)){
                $this->executeCrontask($crontask, true);
            }

            // Check if we launch this one
            if(!$crontask->getIsActive()){
                $output->writeln(sprintf('Skipping crontask <info>%s</info> (<error>%s</error>)', $crontask, 'inactive'));
            }

            // Check date
            $lastrun = $crontask->getLastRun() ? $crontask->getLastRun()->format('U') : 0;

            // Interval in minutes
            $nextrun = $lastrun + $crontask->getCommandInterval() * $crontask->getTypeInterval();



            if(!(time() >= $nextrun) && $error != ""){
                $output->writeln(sprintf('Skipping crontask <info>%s</info> (<error>%s</error>)', $crontask, 'interval'));
            }
            $this->executeCrontask($crontask);

        }

        $em->flush();

        $output->writeln('<comment>No more crontasks, job done!</comment>');
    }

    /**
     * Executes all tasks in the crontask, updates lastRun and persist
     * @param Crontask $crontask
     * @param boolean $isForced if true, show 'forced' on the message
     */
    private function executeCrontask(Crontask $crontask, $isForced){
        $sprintfStr = 'Running crontask <info>%s</info>'.($isForced ? ' (forced)' : '');
        $output->writeln(sprintf($sprintfStr), $crontask);

        // Updating last run
        $crontask->setLastRun(new \DateTime());

        try{
            $commands = $crontask->getCommands();
            foreach($commands as $command){
                $output->writeln(sprintf('Executing command <comment>%s</comment>', $command));
                // Running the command
                $this->runCommand($command);
            }

            $output->writeln('<info>Success</info>');
        }catch(\Exception $e){
            $output->writeln('<error>Error</error>');
        }

        // Update last run
        $em->persist($crontask);
    }

    /**
     * Run the command given as string (my:namespace [arg1[ arg2[ arg3[ ...]]]])
     * @param $string
     * @return bool
     */
    private function runCommand($string){

        // Get the namespace
        $namespace = explode(' ', $string)[0];

        // Set input
        $command = $this->getApplication()->find($namespace);
        $input = new StringInput($string);

        // Run commands
        $returnCode = $command->run($input, $this->output);

        return $returnCode != 0;
    }
} 