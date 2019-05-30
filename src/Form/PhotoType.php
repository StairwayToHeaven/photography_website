<?php
/**
 * Photo type.
 * @copyright (c) 2017 Katarzyna Dam
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PhotoType.
 *
 * @uses Symfony\Component\Form\AbstractType
 * @uses Symfony\Component\OptionsResolver\OptionsResolver
 * @uses Symfony\Component\Form\Extension\Core\Type\TextType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Symfony\Component\Form\Extension\Core\Type\FileType;
 * @uses Symfony\Component\Form\FormBuilderInterface
 * @uses Symfony\Component\Validator\Constraints as Assert
 * @package Form
 */
class PhotoType extends AbstractType
{
    /**
     * Form builder.
     *
     * @param FormBuilderInterface $builder Form builder
     * @param array                $options Form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'id',
            HiddenType::class,
            [
                'label' => 'label.id',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
            ]
        );
        $builder->add(
            'title',
            TextType::class,
            [
                'label' => 'label.title',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => array(
                        new Assert\NotBlank(
                            array(
                                'groups' => array('photo-default'),
                            )
                        ),
                        new Assert\Length(
                            array(
                                'min' => 6,
                                'max' => 100,
                                'groups' => array('photo-default'),
                            )
                        ),
                    ),
            ]
        );
        $builder->add(
            'photo',
            FileType::class,
            [
                'label' => 'label.photo',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        array(
                                'groups' => array('photo-default'),
                            )
                    ),
                    new Assert\Image(
                        [
                            'groups' => array('photo-default'),
                            'maxSize' => '2048k',
                            'mimeTypes' => [
                                'image/png',
                                'image/jpeg',
                                'image/pjpeg',
                                'image/jpeg',
                                'image/pjpeg',
                            ],
                        ]
                    ),
                ],
            ]
        );
    }
    /**
     * Configure options
     *
     * @param OptionsResolver $resolver Form name
     * @return string Validation name
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'photo-default',
            ]
        );

        return 'photo-default';
    }
    /**
     * Getter for form name.
     *
     * @return string Form name
     */
    public function getBlockPrefix()
    {
        return 'photo_type';
    }
}
