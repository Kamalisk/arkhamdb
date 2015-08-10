<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class UpdateOctgnCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:octgn')
        ->setDescription('Download OCTGN IDs from CGDB')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $repo \AppBundle\Entity\ReviewRepository */
        $repo = $em->getRepository('AppBundle:Card');

        $file = exec('curl http://www.cardgamedb.com/deckbuilders/gameofthrones2ndedition/database/agotoctgnguids-1.jgz -H "Accept-Encoding: gzip, deflate, sdch" | gunzip -c');

        if(!preg_match('/^.*?(\{.*\});$/', $file, $matches)) {
          $output->writeln("<error>Error while parsing js file</error>");
        }

        $json = $matches[1];
        $lookup = json_decode($json, TRUE);

        foreach($lookup as $key => $value) {
          $newkey = str_replace(['“', '”', '’'], ['"', '"', '\''], $key);
          if($newkey !=$key) {
            $lookup[$newkey] = $value;
            unset($lookup[$key]);
          }
        }

        $cards = $repo->findBy([], ['code' => 'ASC']);

        foreach($cards as $card) {

          if($card->getOctgnid()) continue;

          $key = sprintf("%s (%s)", $card->getName(), $card->getPack()->getName());

          if(isset($lookup[$key])) {
            $card->setOctgnid($lookup[$key]);
            $output->writeln("<info>Updating octgn id for $key</info>");
          }
          else {
            $output->writeln("<error>Cannot find $key in lookup</error>");
          }

        }

        $factions = $em->getRepository('AppBundle:Faction')->findBy(['is_primary' => TRUE], ['code' => 'ASC']);

        foreach($factions as $faction) {

          if($faction->getOctgnid()) continue;

          $key = sprintf("%s (Core Set)", $faction->getName());

          if(isset($lookup[$key])) {
            $faction->setOctgnid($lookup[$key]);
            $output->writeln("<info>Updating octgn id for $key</info>");
          }
          else {
            $output->writeln("<error>Cannot find $key in lookup</error>");
          }

        }

        $em->flush();
    }
}
