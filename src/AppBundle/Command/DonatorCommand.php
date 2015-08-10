<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Entity\Review;
use AppBundle\Entity\Reviewcomment;

class DonatorCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:donator')
        ->setDescription('Add a donation to a user by email address or username')
        ->addArgument(
            'email',
            InputArgument::REQUIRED,
            'Email address or username of user'
        )
        ->addArgument(
                'donation',
                InputArgument::REQUIRED,
                'Amount of donation'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');
        $donation = $input->getArgument('donation');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();
        /* @var $repo \AppBundle\Entity\ReviewRepository */
        $repo = $em->getRepository('AppBundle:User');
        /* @var $user \AppBundle\Entity\User */
        $user = $repo->findOneBy(array('email' => $email));
        if(!$user) {
        	$user = $repo->findOneBy(array('username' => $email));
        }

        if($user) {
            $user->setDonation($donation + $user->getDonation());
            $em->flush();
            $output->writeln(date('c') . " " . "Success");
        } else {
            $output->writeln(date('c') . " " . "Cannot find user [$email]");
        }
    }
}
