<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command; 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class TwigCacheCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this->setName('app:twig')
        ->setDescription('selectively manage the twig cache')

        ->addArgument(
            'names',
            InputArgument::IS_ARRAY,
               'Example AppBundle:Default:footer.html.twig',
                null
        )->addOption('clear','c', InputOption::VALUE_NONE, 'delete cache files' );
    }

    public function write($output, $text) {
        $output->writeln($text);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $environment = $this->getContainer()->get('twig');
        $names = $input->getArgument('names');


        $actionName = null;
        if ($input->getOption('clear')) {
            $actionName = 'deleting';
            $action =  function ($fileName) {
                unlink($fileName);
            };
        } else {
            $actionName="path:";
            $action = function ($filename) {

            };
        }

        foreach ($names as $name) {

            $fileName = $environment->getCacheFilename($name);

            if (file_exists($fileName)) {
                $action($fileName);
            } else {
                $fileName = 'not found.';
            }
            $this->write($output, $actionName.' '.$name."\ncacheFile: ".$fileName);
        }
        $this->write($output, 'Done');
    }
}