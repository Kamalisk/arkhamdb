<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DumpStdCardsCommand extends ContainerAwareCommand
{

	protected function configure()
	{
		$this
		->setName('app:dump:std:cards')
		->setDescription('Dump JSON Data of Cards from a Pack')
		->addArgument(
				'pack_code',				
				InputArgument::REQUIRED,
				"Pack Code"
		)
		->addArgument(
				'deck_type',				
				InputArgument::OPTIONAL,
				"Deck Type"
		)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$pack_code = $input->getArgument('pack_code');
		$deck_type = $input->getArgument('deck_type');
		
		$pack = $this->getContainer()->get('doctrine')->getManager()->getRepository('AppBundle:Pack')->findOneBy(['code' => $pack_code]);
		
		if(!$pack) {
			throw new \Exception("Pack [$pack_code] cannot be found.");
		}
		
		/* @var $repository \AppBundle\Repository\CardRepository */
		$repository = $this->getContainer()->get('doctrine')->getManager()->getRepository('AppBundle:Card');
		
		if ($deck_type == "encounter"){
			$qb = $repository->createQueryBuilder('c')->where('c.pack = :pack and c.encounter is not null')->setParameter('pack', $pack)->orderBy('c.position,c.code');
		}else {
			$qb = $repository->createQueryBuilder('c')->where('c.pack = :pack and c.encounter is null')->setParameter('pack', $pack)->orderBy('c.position,c.code');
		}
		
		$cards = $qb->getQuery()->getResult();
		
		$arr = [];
		
		foreach($cards as $card) {
			$arr[] = $card->serialize();
		}
		
		$output->write(json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ));
		$output->writeln("");
	}
}