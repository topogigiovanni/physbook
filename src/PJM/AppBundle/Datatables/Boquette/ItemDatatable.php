<?php

namespace PJM\AppBundle\Datatables\Boquette;

use Sg\DatatablesBundle\Datatable\View\AbstractDatatableView;
use PJM\AppBundle\Twig\IntranetExtension;
use PJM\AppBundle\Services\Image as ImageService;

/**
 * Class ItemDatatable.
 */
class ItemDatatable extends AbstractDatatableView
{
    protected $boquetteSlug;
    protected $twigExt;
    protected $extImage;
    protected $admin;

    public function setBoquetteSlug($boquetteSlug)
    {
        $this->boquetteSlug = $boquetteSlug;
    }

    public function setTwigExt(IntranetExtension $twigExt)
    {
        $this->twigExt = $twigExt;
    }

    public function setExtImage(ImageService $extImage)
    {
        $this->extImage = $extImage;
    }

    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDatatableView()
    {
        $this->getFeatures()
            ->setServerSide(true)
            ->setProcessing(true)
        ;

        $this->getOptions()
            ->setOrder(array('column' => 0, 'direction' => 'desc'))
        ;

        $this->getAjax()->setUrl(
            $this->getRouter()->generate('pjm_app_boquette_itemResults', array(
                'boquette_slug' => $this->boquetteSlug,
            ))
        );

        $this->setStyle(self::BOOTSTRAP_3_STYLE);

        $this->getColumnBuilder()
            ->add('date', 'datetime', array(
                'title' => 'Date ISO',
                'format' => '',
                'visible' => false,
            ))
            ->add('boquette.slug', 'column', array('visible' => false))
            ->add('image.id', 'column', array('visible' => false))
            ->add('image.ext', 'column', array('visible' => false))
            ->add('image.alt', 'column', array(
                'title' => 'Image',
            ))
            ->add('libelle', 'column', array(
                'title' => 'Nom',
            ))
            ->add('prix', 'column', array(
                'title' => 'Prix',
            ))
            ->add('date', 'datetime', array(
                'title' => 'Date',
                'format' => 'll',
            ))
            ->add('valid', 'boolean', array(
                'title' => 'Actif',
                'true_icon' => 'glyphicon glyphicon-ok',
                'false_icon' => 'glyphicon glyphicon-remove',
                'true_label' => 'Oui',
                'false_label' => 'Non',
            ))
        ;

        if ($this->admin) {
            $this->getColumnBuilder()
                ->add(null, 'action', array(
                    'title' => 'Actions',
                    'actions' => array(
                        array(
                            'route' => 'pjm_app_admin_boquette_modifierItem',
                            'route_parameters' => array(
                                'boquette' => 'boquette.slug',
                                'item' => 'id',
                            ),
                            'label' => 'Modifier',
                            'icon' => 'glyphicon glyphicon-edit',
                            'attributes' => array(
                                'rel' => 'tooltip',
                                'title' => 'Modifier',
                                'class' => 'btn btn-default btn-xs',
                                'role' => 'button',
                            ),
                        ),
                    ),
                ))
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLineFormatter()
    {
        $ext = $this->twigExt;
        $extImage = $this->extImage;

        $formatter = function ($line) use ($ext, $extImage) {
            $line['prix'] = $ext->prixFilter($line['prix']);
            $line['image']['alt'] = !empty($line['image']['id']) ?
                $extImage->html($line['image']['id'], $line['image']['ext'], $line['image']['alt']) :
                "Pas d'image";

            return $line;
        };

        return $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return 'PJM\AppBundle\Entity\Item';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pjm_app_boquette_item_datatable';
    }
}
