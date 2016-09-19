<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use FOS\UserBundle\Mailer\MailerInterface;

class ResendCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
        ->setName('app:resend')
        ->setDescription('Resend confirmation e-mails')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;

        $users = $em->getRepository('AppBundle:User')->findAll();
        foreach($users as $user) {        		
	        	if ($user->getConfirmationToken() !== null){	        		
	        		$output->writeln($user->getEmail() . "  ". $user->getConfirmationToken());
	        		$this->getContainer()->get('fos_user.mailer')->sendConfirmationEmailMessage($user);
	        		sleep(1);
	        	}        		
            //$this->mailer->sendConfirmationEmailMessage($user);
        }
        
        $output->writeln(" Possibly Sent Some E-mails yay.");
    }
}
