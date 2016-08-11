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
            ->add('deck_limit')
            ->add('code')
            ->add('type', 'entity', array('class' => 'AppBundle:Type', 'property' => 'name'))
            ->add('subtype', 'entity', array('class' => 'AppBundle:Subtype', 'property' => 'name', 'required' => false))
            ->add('faction', 'entity', array('class' => 'AppBundle:Faction', 'property' => 'name'))
            ->add('name')
            ->add('subname')            
            ->add('text', 'textarea', array('required' => false))
            ->add('cost', 'number', array('required' => false))
            ->add('will')
            ->add('lore')
            ->add('strength')
            ->add('agility')
            ->add('wild')
            ->add('xp')
            ->add('health')
            ->add('sanity')
            ->add('traits')
            ->add('slot')
            ->add('restrictions')
            ->add('flavor', 'textarea', array('required' => false))
            ->add('illustrator')
            ->add('deckRequirements')
            ->add('deckOptions')
            ->add('octgn_id')
            ->add('is_unique', 'checkbox', array('required' => false))
            ->add('file', 'file', array('label' => 'Image File', 'mapped' => false, 'required' => false))
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
    	return 'appbundle_cardtype';
    }
}
