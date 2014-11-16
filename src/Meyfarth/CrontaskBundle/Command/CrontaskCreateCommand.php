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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CrontaskCreateCommand
 * @package Meyfarth\CrontaskBundle\Command
 */
class CrontaskCreateCommand extends ContainerAwareCommand {

    const TYPE_ARGUMENT_INTEGER = 1;
    const TYPE_ARGUMENT_DATETIME = 2;
    const TYPE_ARGUMENT_STRING = 3;

    private $dialog;
    private $output;

    protected function configure(){
        $this->setName('mey:crontask:create')
            ->setDescription('Creates a new crontask')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the crontask (must be unique)')
            ->addArgument('interval', InputArgument::OPTIONAL, 'interval between two crontasks. You can set it to hours, minutes or seconds using interval-type argument')
            ->addArgument('interval-type', InputArgument::OPTIONAL, 'hours (h), minutes (min) or seconds (sec)')
            ->addArgument('first-run', InputArgument::OPTIONAL, 'first run, format Y-m-d H:i:s')
            ->addOption('inactive', 'i', InputOption::VALUE_NONE, 'if set, the crontask will be created as inactive')
            ->addOption('command', 'c', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'if set, add the command to the command list')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command creates a new crontask. This crontask allows you to
run several Symfony2 commands at once, automatically.

<info>php %command.full_name% name [interval [interval-type [first-run] ] ]
--inactive --command="command1" --command="command2"</info>

A crontask is a Symfony2 command executed periodically. You can create as
many crontask as you want, which will be checked and executed using the
<comment>mey:crontask:run</comment> command.

If you want to run the crontasks automatically, please configure your server's crontable.

To create a new crontask, you must provide 4 arguments. If not provided, you will
be asked to give them later.

Arguments required :
<info>name</info> - The name of the crontask. It must be unique and serve as an identifier.

<info>interval</info> - the interval between two runs. See the interval-type argument for more details.

<info>interval-type</info> - The interval type (hours, minutes or seconds). If you set the crontask's
interval as 1 hour (interval = 1, interval-type = h), the crontask will be run at most once every hour.

<info>first-run</info> - Format: Y-m-d H:i. If not set, the crontask will run the next time
<comment>mey:crontask:run</comment> command is executed. If set, the crontask will run at
the specified time.

<info>--inactive</info> - If set, the crontask will be created as inactive. An inactive crontask will not
be executed. See the <comment>mey:crontask:run</comment> help for more details

<info>--command="command name"</info> - you can add several commands to your crontask.
You can add as many commands as you want.
During the creation of your crontask, you will be prompted for more crontasks.
<fg=red>Please note that the commands will be executed in the given order</fg=red>.

For example, this command will run "assets:install web" and "assetic:dump" every 60 minutes:
<info>php %command.name% "asset and assetic" --interval="60" --interval-type="min" --command="assets:install web"
-command="assetic-dump"</info>

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
        $interval = $input->getArgument('interval');
        $intervalType = $input->getArgument('interval-type');
        $firstRun = $input->getArgument('first-run');

        $inactive = $input->getOption('inactive');

        $commands = $input->getOption('command');

        // Check if the name doesn't already exists
        $crontasks = $em->getRepository('MeyfarthCrontaskBundle:Crontask')->findBy(array('name' => $name));
        if(count($crontasks) > 0){
            $output->writeln('<error>There already is a crontask with this name</error>');
            exit();
        }

        $this->dialog = $this->getHelperSet()->get('dialog');
        $this->output = $output;

        $interval = $this->checkArgument($interval, self::TYPE_ARGUMENT_INTEGER, 'Insert interval between two runs (integer only) :');

        $intervalType = $this->checkArgument($intervalType, self::TYPE_ARGUMENT_STRING, 'Insert the type interval. Allowed values : [(h)ours, (min)utes or (sec)onds] :', false, array('h', 'hour', 'hours', 'm', 'min', 'minute', 'minutes', 's', 'sec', 'second', 'seconds'));

        $firstRun = $this->checkArgument($firstRun, self::TYPE_ARGUMENT_DATETIME, 'Insert the datetime of the first run (or \'null\' if you want it to be executed the nextime your meyfarth:crontask:run command is executed) :', true);

        // Add comands to the ones given with --command="" options
        do{
            $command = $this->dialog->ask(
                $this->output,
                'Enter a command to add or press Enter when finished : '
            );
            if($command != ''){
                $commands[] = $command;
            }
        }while($command != '');


        // Confirm generation
        $output->writeln('');
        if($this->dialog->askConfirmation(
            $this->output,
            sprintf('<question>Confirm generation of the "%s" crontask (%s) which will run %s commands every %d %s ? </question>',
            $name, $inactive ? 'inactive' : 'active', '"'.implode('", "', $commands).'"', $interval, $intervalType),
            false
        )){
            $crontask = new Crontask();
            $crontask->setCommandInterval($interval)
                ->setFirstRun(new \DateTime($firstRun))
                ->setIsActive(!$inactive)
                ->setName($name)
                ->setCommands($commands)
                ->setIntervalType(CrontaskService::convertToIntervalType($intervalType));
            $output->writeln('<comment>Crontask "'.$crontask.'" successfully created</comment>');
            $em->persist($crontask);
            $em->flush();
        }else{
            $output->writeln('<fg=red>Crontask creation aborted</fg=red>');
        }
    }

    /**
     * Check the argument and force the user to put a valid value
     * @param mixed $argument
     * @param integer $type
     * @param string $question
     * @param boolean $nullable
     * @param array|null $allowedValues
     * @return mixed|null
     */
    private function checkArgument($argument, $type, $question, $nullable = false, $allowedValues = null){
        // Check arguments types

        if($nullable === true && $argument == 'null'){
            return null;
        }

        $argument = $this->checkTypeOrNull($argument, $type, $allowedValues);

        // Check if arguments are set
        while($argument == null){
            $this->output->writeln('');
            $argument = $this->dialog->ask(
                $this->output,
                $question
            );
            if($nullable === true && $argument == 'null'){
                return null;
            }

            $argument = $this->checkTypeOrNull($argument, $type, $allowedValues);
        }


        return $argument;
    }


    /**
     * check the argument type and format, if not correct, send null, else send back the argument
     * @param mixed $argument
     * @param string $type
     * @param array|null $allowedValues
     * @return mixed|null
     */
    private function checkTypeOrNull($argument, $type, array $allowedValues = null){
        if($type == self::TYPE_ARGUMENT_INTEGER){
            if(!ctype_digit($argument)){
                $argument = null;
            }
        }elseif($type == self::TYPE_ARGUMENT_DATETIME){
            if(CrontaskService::ValidateDate($argument, CrontaskService::DATE_FORMAT)){
                $argument = null;
            }
        }

        if($allowedValues != null && !in_array($argument, $allowedValues)){
            $argument = null;
        }

        return $argument;
    }
}