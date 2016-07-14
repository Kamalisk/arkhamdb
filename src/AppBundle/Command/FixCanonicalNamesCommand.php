<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class FixCanonicalNamesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:fix-canonical-names')
        ->setDescription('Fix canonical names for decklists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $texts = $this->getContainer()->get('texts');
        $count = 0;
        
        $decklists = $em->getRepository('AppBundle:Decklist')->findAll();
        foreach($decklists as $decklist) {
            /* @var $decklist \AppBundle\Entity\Decklist */
        	$nameCanonical = $texts->slugify($decklist->getName()) . '-' . $decklist->getVersion();
        	if($nameCanonical !== $decklist->getNameCanonical()) {
        		$decklist->setNameCanonical($nameCanonical);
        		$count++;
        	}
        }
        $em->flush();
        $output->writeln(date('c') . " Fixed $count decklist canonical names.");
    }
}
