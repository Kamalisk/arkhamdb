<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportGuidsCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:import:guids')
        ->setDescription('Download OCTGN IDs from CGDB')
        ->addArgument(
        		'setid',
        		InputArgument::REQUIRED,
        		'GUID of the set for OCTGN'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $repo \AppBundle\Entity\ReviewRepository */
        $repo = $em->getRepository('AppBundle:Card');

        $setid = $input->getArgument('setid');
        
        $xmlstr = file_get_contents("https://raw.githubusercontent.com/TassLehoff/AGoTv2-OCTGN/master/GameDatabase/30c200c9-6c98-49a4-a293-106c06295c05/sets/$setid/set.xml");
        
        $set = new \SimpleXMLElement($xmlstr);
        $cards = $set->cards[0];
        
        $guids = [];
        foreach($cards->children() as $card) {
        	$attributes = $card->attributes();
        	$name = (string)$attributes->name;
        	$guid = (string)$attributes->id;
        	$name  = str_replace('’', '\'', $name);
        	$guids[$name] = $guid;
        }
        
        $cards = $repo->findBy(['octgnId' => null], ['code' => 'ASC']);

        foreach($cards as $card) {
        	$name = $card->getName();
        	if(key_exists($name, $guids)) {
            	$card->setOctgnId($guids[$name]);
            	unset($guids[$name]);
            	$output->writeln("<info>Updating octgn id for $name</info>");
        	} else {
        		$output->writeln("<error>Cannot find $name</error>");
        	}
        }
        
        $em->flush();
    }
}
