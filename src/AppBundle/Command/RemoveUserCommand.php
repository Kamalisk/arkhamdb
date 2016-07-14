<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class RemoveUserCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('app:user:remove')
            ->setDescription('Lock one user and delete all its content')
            ->addArgument(
                'user_id',
                InputArgument::REQUIRED,
                'Id of the user'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $user_id = $input->getArgument('user_id');
        $user = $em->getRepository('AppBundle:User')->find($user_id);

        if(!$user) {
            $output->writeln("User not found");
            return;
        }

        $output->writeln("User ".$user->getUsername());

        $decks = $em->getRepository('AppBundle:Deck')->findBy(array(
            'user' => $user
        ));

        $output->writeln(count($decks)." decks");

        foreach($decks as $deck)
        {
            $children = $em->getRepository('AppBundle:Decklist')->findBy(array(
                'parent' => $deck
            ));
            foreach($children as $child) {
                $child->setParent(null);
            }
            $em->remove($deck);
        }

        $output->writeln("Decks deleted");

        $decklists = $em->getRepository('AppBundle:Decklist')->findBy(array(
            'user' => $user
        ));

        $output->writeln(count($decklists)." decklists");

        foreach($decklists as $decklist)
        {
            $successors = $em->getRepository('AppBundle:Decklist')->findBy(array(
                'precedent' => $decklist
            ));
            foreach($successors as $successor) {
                /* @var $successor \AppBundle\Entity\Decklist */
                $successor->setPrecedent(null);
            }

            $children = $em->getRepository('AppBundle:Deck')->findBy(array(
                'parent' => $decklist
            ));
            foreach($children as $child) {
                /* @var $child \AppBundle\Entity\Deck */
                $child->setParent(null);
            }

            $em->remove($decklist);
        }

        $output->writeln("Decklists deleted");

        $user->setLocked(TRUE);

        $output->writeln("User locked");

        $em->flush();
    }
}
