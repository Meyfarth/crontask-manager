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
class CrontaskRemoveCommandsCommand extends ContainerAwareCommand {

    private $dialog;
    private $output;

    protected function configure(){
        $this->setName('mey:crontask:remove-command')
            ->setDescription('Remove one or more command of the crontask\'s command list')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the crontask')
            ->addOption('command', 'c', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'if set, remove the command of the command list')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command removes one or more command to the [name] crontask.

<info>php %command.full_name% name --command="command1" --command="command2"</info>

To remove a command, you must use its full name. If your command is "assets:install web", you
must use "mey:crontask:remove-command [name] --command="assets:install web"

You can add multiple commands by using multiple times the <info>--command</info> option.


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

        $commands = $input->getOption('command');

        $output->writeln('<fg=yellow>Getting crontask "'.$name.'"</fg=yellow>');

        // Get the crontask
        $crontask = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findOneBy(array('name' => $name));
        if(!$crontask){
            $output->writeln('No crontask with this name found');
            exit();
        }


        $this->dialog = $this->getHelperSet()->get('dialog');

        $output->writeln(sprintf('Current commands are (in order) "%s"', implode('", "', $crontask->getCommands())));
        $output->writeln('');

        // Add comands to the ones given with --command="" options
        do{
            $command = $this->dialog->ask(
                $output,
                'Enter the full command you want to remove or press Enter when finished : '
            );
            if($command != ''){
                $commands[] = $command;
            }
        }while($command != '');

        $crontask->setCommands(array_diff($crontask->getCommands(), $commands));

        // Confirm
        if($this->dialog->askConfirmation(
            $output,
            sprintf('<question>Confirm updating to "%s" commands ? </question>',
                implode('", "', $crontask->getCommands())),
            false
        )){
            $output->writeln('<info>Updating the crontask</info>');
            $crontask->setCommands($commands);
            $em->persist($crontask);
            $em->flush();
            $output->writeln('<fg=green>Crontask updated successfully</fg=green>');
        }else{
            $output->writeln('<fg=red>Update aborted</fg=red>');
        }
    }
}