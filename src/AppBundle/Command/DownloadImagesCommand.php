<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DownloadImagesCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:download-images')
        ->setDescription('Download missing card images from FFG websites')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $assets_helper = $this->getContainer()->get('templating.helper.assets');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /* @var $repo \AppBundle\Entity\ReviewRepository */
        $repo = $em->getRepository('AppBundle:Card');

        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $output->writeln($rootDir);

        $cards = $repo->findBy([], ['code' => 'ASC']);
        foreach($cards as $card) {
          $card_code = $card->getCode();
          $imageurl = $assets_helper->getUrl('bundles/cards/'.$card_code.'.png');
          $imagepath= $rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
          if(file_exists($imagepath)) {
            $output->writeln("Skip ".$card_code);
          }
          else {
            $matches = [];
            if(preg_match('/^(..)(...)/', $card_code, $matches)) {
              $pack_code = $matches[1];
              $card_position = intval($matches[2]);
              $cgdbfile = sprintf('GT%s_%d.jpg', $pack_code, $card_position);
              $cgdburl = "http://lcg-cdn.fantasyflightgames.com/got2nd/" . $cgdbfile;

              $dirname = dirname($imagepath);
              $outputfile = $dirname . DIRECTORY_SEPARATOR . $card_code . ".jpg";

              $image = file_get_contents($cgdburl);
              if($image !== FALSE) {
                file_put_contents($outputfile, $image);
                $output->writeln("New file at $outputfile");
              }
              else {
                $output->writeln("Failed at downloading $cgdburl");
              }
            }

          }

        }

    }
}
