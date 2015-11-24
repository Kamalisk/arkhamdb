<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code')
            ->add('name')
            ->add('dateRelease')
            ->add('size')
            ->add('cycle', 'entity', array('class' => 'AppBundle:Cycle', 'property' => 'name'))
            ->add('position')
        ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Pack'
        ));
    }
    
    public function getName()
    {
    	return 'appbundle_packtype';
    }
}
