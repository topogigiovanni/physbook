<?php

namespace PJM\AppBundle\Form\Type\Event;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PJM\AppBundle\Form\Type\ImageType;
use PJM\AppBundle\Form\Type\Boquette\BoquetteByResponsableType;

class EvenementType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('description', null, array(
                'required' => false,
            ))
            ->add('dateDebut', 'datetimePicker', array(
                'label' => 'Date de début',
                'required' => false,
            ))
            ->add('dateFin', 'datetimePicker', array(
                'label' => 'Date de fin',
                'required' => false,
                'linkedTo' => 'dateDebut',
            ))
            ->add('day', null, array(
                'label' => 'Journée(s) entière(s) ?',
                'required' => false,
            ))
            ->add('image', new ImageType(), array(
                'label' => 'Image',
                'required' => false,
            ))
            ->add('lieu', null, array(
                'help_label' => 'Si tu renseignes "Pian\'s", "C\'vis", ou encore "Gymnase", ton évènement apparaîtra sur la page de la boquette correspondante.',
            ))
            ->add('public', null, array(
                'label' => 'Evènement public',
                'required' => false,
            ))
            ->add('majeur', null, array(
                'label' => 'Evènement majeur',
                'help_label' => 'Un évènement majeur concerne beaucoup de personnes et est important (ex. grosses manips) : le débit sera effectué par un Harpag\'s après vérification des factures.
                Un évènement mineur concerne la plupart du temps un petit groupe de personnes et est souvent un évènement privé (ex. fin\'s entre chicop\'s) : le débit pourra être effectué par l\'organisateur et celui-ci sera crédité directement.
                Les Harpag\'s peuvent rendre un évènement mineur majeur, et vice-versa s\'il a été défini majeur par inadvertance.
                Un évènement mineur deviendra majeur automatiquement lorsqu\'il y a plus de 10 participants.',
                'required' => false,
            ))
            ->add('prix', 'money', array(
                'label' => 'Prix par personne',
                'divisor' => 100,
            ))
            ->add('maxParticipants', null, array(
                'label' => 'Nombre de participants maximum',
                'help_label' => 'Toi y compris',
                'required' => false,
            ))
            ->add('dateDeadline', 'datetimePicker', array(
                'label' => 'Deadline',
                'help_label' => 'Personne ne pourra s\'inscrire ou se désinscrire après cette date.',
                'required' => false,
            ))
            ->add('boquette', new BoquetteByResponsableType(array('user' => $options['user'])))
            ->add('save', 'submit', array(
                'label' => $options['label_submit'],
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PJM\AppBundle\Entity\Event\Evenement',
            'user' => null,
            'label_submit' => 'Créer',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'pjm_appbundle_event_evenement';
    }
}
