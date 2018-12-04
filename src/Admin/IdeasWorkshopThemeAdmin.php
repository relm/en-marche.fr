<?php

namespace AppBundle\Admin;

use AppBundle\IdeasWorkshop\ThemeManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class IdeasWorkshopThemeAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_per_page' => 32,
        '_sort_order' => 'ASC',
        '_sort_by' => 'name',
    ];

    private $themeManager;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        ThemeManager $themeManager
    ) {
        parent::__construct($code, $class, $baseControllerName);

        $this->themeManager = $themeManager;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', null, [
                'label' => 'Nom',
                'format_title_case' => true,
            ])
            ->add('enabled', null, [
                'label' => 'Visibilité',
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'label' => 'Ajoutez une image',
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, [
                'label' => 'Nom',
                'show_filter' => true,
            ])
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('enabled', null, [
                'label' => 'Visibilité',
            ])
            ->add('image', null, [
                'label' => 'Image',
                'virtual_field' => true,
                'template' => 'admin/list/list_image_miniature.html.twig',
            ])
            ->add('_action', null, [
                'virtual_field' => true,
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    public function postRemove($theme)
    {
        parent::postRemove($theme);

        $this->themeManager->removeImage($theme);
    }

    public function prePersist($theme)
    {
        parent::prePersist($theme);

        if ($theme->getImage()) {
            $this->themeManager->saveImage($theme);
        }
    }

    public function preUpdate($theme)
    {
        parent::preUpdate($theme);

        if ($theme->getImage()) {
            $this->themeManager->saveImage($theme);
        }
    }
}
