<?php

namespace PJM\AppBundle\Form\Type\Inbox;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use PJM\AppBundle\Form\Type\Boquette\BoquetteByResponsableType;

class MessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('destinations', 'genemu_jqueryselect2_entity', array(
                'label' => 'Destinataires',
                'class' => 'PJMAppBundle:Inbox\Inbox',
                'error_bubbling' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('i')
                        ->join('i.user', 'u')
                        ->orderBy('u.fams', 'ASC')
                        ->addOrderBy('u.proms', 'DESC')
                    ;
                },
                'multiple' => true,
                'choice_label' => 'user',
            ))
            ->add('contenu', 'textarea', array(
                'attr' => array('style' => 'min-height: 20em;'),
            ))
            ->add('boquette', new BoquetteByResponsableType(array(
                'user' => $options['user'],
                'required' => $options['annonce'],
            )), array(
                'error_bubbling' => true,
            ))
            ->add('save', 'submit', array(
                'label' => 'Envoi',
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PJM\AppBundle\Entity\Inbox\Message',
            'user' => null,
            'annonce' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'form_pjm_appbundle_inbox_messagetype';
    }
}
