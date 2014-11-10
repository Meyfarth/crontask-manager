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
class CrontaskCreateCommand extends ContainerAwareCommand {

    const TYPE_ARGUMENT_INTEGER = 1;
    const TYPE_ARGUMENT_DATETIME = 2;
    const TYPE_ARGUMENT_STRING = 3;

    private $dialog;
    private $output;

    protected function configure(){
        $this->setName('meyfarth:crontask:create')
            ->setDescription('Creates a new crontask')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the crontask (must be unique)')
            ->addArgument('interval', InputArgument::OPTIONAL, 'interval between two crontasks. You can set it to hours, minutes or seconds using typeInterval argument')
            ->addArgument('typeInterval', InputArgument::OPTIONAL, 'hours (h), minutes (min) or seconds (sec)')
            ->addArgument('firstRun', InputArgument::OPTIONAL, 'first run, format Y-m-d H:i:s')
            ->addOption('inactive', 'i', InputOption::VALUE_NONE, 'if set, the crontask will be created as inactive')
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
        $interval = $input->getArgument('interval');
        $typeInterval = $input->getArgument('typeInterval');
        $firstRun = $input->getArgument('firstRun');

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

        $typeInterval = $this->checkArgument($typeInterval, self::TYPE_ARGUMENT_STRING, 'Insert the type interval. Allowed values : [(h)ours, (min)utes or (sec)onds] :', false, array('h', 'hour', 'hours', 'm', 'min', 'minute', 'minutes', 's', 'sec', 'second', 'seconds'));

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
            $name, $inactive ? 'inactive' : 'active', '"'.implode('", "', $commands).'"', $interval, $typeInterval),
            false
        )){
            $crontask = new Crontask();
            $crontask->setCommandInterval($interval)
                ->setFirstRun(new \DateTime($firstRun))
                ->setIsActive(!$inactive)
                ->setName($name)
                ->setTypeInterval(CrontaskService::convertToTypeInterval($typeInterval));
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
            if(!$this->validateDate($argument, 'Y-m-d H:i')){
                $argument = null;
            }
        }

        if($allowedValues != null && !in_array($argument, $allowedValues)){
            $argument = null;
        }

        return $argument;
    }

    /**
     * Check if the given string is a valid date format
     * @param string $date
     * @param string $format
     * @return bool
     */
    private function ValidateDate($date, $format = 'Y-m-d H:i:s') {
        $version = explode('.', phpversion());
        if (((int) $version[0] >= 5 && (int) $version[1] >= 2 && (int) $version[2] > 17)) {
            $d = \DateTime::createFromFormat($format, $date);
        } else {
            $d = new \DateTime(date($format, strtotime($date)));
        }

        return $d && $d->format($format) == $date;
    }
}