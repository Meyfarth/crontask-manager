<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 11/11/2014
 * Time: 16:43
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
class CrontaskAddCommandsCommand extends ContainerAwareCommand {

    private $dialog;
    private $output;

    protected function configure(){
        $this->setName('mey:crontask:add-command')
            ->setDescription('Add one or more commands to the crontask\'s command list')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the crontask')
            ->addOption('command', 'c', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'if set, add the command to the command list');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output){

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $name = $input->getArgument('name');

        $commands = $input->getOption('command');

        $output->writeln('<fg=yellow>Getting crontask "'.$name.'"</fg=yellow>');

        // Get the crontask
        $crontask = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findOneBy(array('name' => $name));
        if(!$crontask){
            $output->writeln('No crontask with this name found');
            exit();
        }


        // Merge the commands with the ones passed as option
        $commands = array_merge($crontask->getCommands(), $commands);

        $this->dialog = $this->getHelperSet()->get('dialog');

        $output->writeln('<fg=red>Please note that commands will be executed in the given order</fg=red>');
        $output->writeln('');

        $output->writeln(sprintf('Current commands are (in order) "%s"', implode('", "', $commands)));
        $output->writeln('');

        // Add comands to the ones given with --command="" options
        do{
            $command = $this->dialog->ask(
                $output,
                'Enter a command to add or press Enter when finished : '
            );
            if($command != ''){
                $commands[] = $command;
            }
        }while($command != '');

        // Confirm
        if($this->dialog->askConfirmation(
            $output,
            sprintf('<question>Confirm updating to "%s" commands ? </question>',
                implode('", "', $commands)),
            false
        )){
            $output->writeln('<info>Updating the crontask</info>');
            $crontask->setCommands($commands);
            $em->persist($crontask);
            $em->flush();
            $output->writeln('<success>Crontask updated successfully</success>');
        }else{
            $output->writeln('<fg=red>Update aborted</fg>');
        }
    }
}