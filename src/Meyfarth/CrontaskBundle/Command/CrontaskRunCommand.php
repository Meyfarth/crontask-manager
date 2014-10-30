<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 30/10/14
 * Time: 22:36
 * Inspired from http://inuits.eu/blog/creating-automated-interval-based-cron-tasks-symfony2
 */

namespace Meyfarth\CrontaskBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class CrontaskRunCommand extends ContainerAwareCommand {
    private $output;

    protected function configure(){
        $this->setName('meyfarth:crontask:run')
            ->setDescription('Runs Crontasks')
            ->addOption('force', null, InputOption::VALUE_OPTIONAL |InputOption::VALUE_IS_ARRAY, 'Forces crontasks even if inactive or if the lastrun is outdated')
            ->addOption('ignore', null, InputOption::VALUE_OPTIONAL |InputOption::VALUE_IS_ARRAY, 'Ignores crontask even if it should be run');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @todo implement force and ignore options
     */
    protected function execute(InputInterface $input, OutputInterface $output){
        $output->writeln('<comment>Running crontasks</comment>');

        $this->output = $output;

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $crontasks = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findAll();

        foreach($crontasks as $crontask){
            // Check if we launch this one
            if(!$crontask->getIsActive()){
                $output->writeln(sprintf('Skipping crontask <info>%s</info> (<error>%s</error>', $crontask, 'inactive'));
                continue;
            }

            // Check date
            $lastrun = $crontask->getLastRun() ? $crontask->getLastRun()->format('U') : 0;

            // Interval in minutes
            $nextrun = $lastrun + $crontask->getCommandInterval() * $crontask->getTypeInterval();

            if(!(time() >= $nextrun)){
                $output->writeln(sprintf('Skipping crontask <info>%s</info> (<error>%s</error>', $crontask, 'interval'));
                continue;
            }

            $output->writeln(sprintf('Running crontask <info>%s</info>', $crontask));

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

        $em->flush();

        $output->writeln('<comment>No more crontasks, job done!</comment>');
    }


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