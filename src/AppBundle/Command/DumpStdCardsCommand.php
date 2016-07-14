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
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$pack_code = $input->getArgument('pack_code');
		
		$pack = $this->getContainer()->get('doctrine')->getManager()->getRepository('AppBundle:Pack')->findOneBy(['code' => $pack_code]);
		
		if(!$pack) {
			throw new \Exception("Pack [$pack_code] cannot be found.");
		}
		
		/* @var $repository \AppBundle\Repository\CardRepository */
		$repository = $this->getContainer()->get('doctrine')->getManager()->getRepository('AppBundle:Card');
		
		$qb = $repository->createQueryBuilder('c')->where('c.pack = :pack')->setParameter('pack', $pack)->orderBy('c.code');
		
		$cards = $qb->getQuery()->getResult();
		
		$arr = [];
		
		foreach($cards as $card) {
			$arr[] = $card->serialize();
		}
		
		$output->write(json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ));
		$output->writeln("");
	}
}