<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pack', 'entity', array('class' => 'AppBundle:Pack', 'property' => 'name'))
            ->add('position')
            ->add('quantity')
            ->add('code')
            ->add('type', 'entity', array('class' => 'AppBundle:Type', 'property' => 'name'))
            ->add('faction', 'entity', array('class' => 'AppBundle:Faction', 'property' => 'name'))
            ->add('name')
            ->add('text', 'textarea', array('required' => false))
            ->add('cost', 'number', array('required' => false))
            ->add('gold')
            ->add('claim')
            ->add('initiative')
            ->add('reserve')
            ->add('strength')
            ->add('keywords')
            ->add('flavor', 'textarea', array('required' => false))
            ->add('illustrator')
            ->add('is_unique', 'checkbox', array('required' => false))
            ->add('is_limited', 'checkbox', array('required' => false))
            ->add('is_loyal', 'checkbox', array('required' => false))
            ->add('is_military', 'checkbox', array('required' => false))
            ->add('is_intrigue', 'checkbox', array('required' => false))
            ->add('is_power', 'checkbox', array('required' => false))
            ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Card'
        ));
    }

    public function getName()
    {
        return 'netrunnerdb_cardsbundle_cardtype';
    }
}
