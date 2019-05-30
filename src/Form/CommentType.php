<?php
/**
 * Comment type.
 * @copyright (c) 2017 Katarzyna Dam
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CommentType.
 *
 * @uses Symfony\Component\Form\AbstractType
 * @uses Symfony\Component\OptionsResolver\OptionsResolver
 * @uses Symfony\Component\Form\Extension\Core\Type\TextType
 * @uses Symfony\Component\Form\Extension\Core\Type\TextareaType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Symfony\Component\Form\FormBuilderInterface
 * @uses Symfony\Component\Validator\Constraints as Assert
 * @package Form
 */
class CommentType extends AbstractType
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
            'content',
            TextareaType::class,
            [
                'label' => 'label.comment',
                'required' => true,
                'attr' => [
                    'maxlength' => 400,

                ],
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
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
                'validation_groups' => 'comment-default',
            ]
        );

        return 'comment-default';
    }
    /**
     * Getter for form name.
     *
     * @return string Form name
     */
    public function getBlockPrefix()
    {
        return 'comment_type';
    }
}
