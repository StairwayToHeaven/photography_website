<?php
/**
 * User type.
 * @copyright (c) 2017 Katarzyna Dam
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class UserType.
 *
 * @uses Symfony\Component\Form\AbstractType
 * @uses Symfony\Component\OptionsResolver\OptionsResolver
 * @uses Symfony\Component\Form\Extension\Core\Type\TextType
 * @uses Symfony\Component\Form\Extension\Core\Type\TextareaType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Symfony\Component\Form\Extension\Core\Type\RepeatedType
 * @uses Symfony\Component\Form\FormBuilderInterface
 * @uses Symfony\Component\Validator\Constraints as Assert
 * @uses Symfony\Component\Form\Extension\Core\Type\PasswordType
 * @uses Symfony\Component\Form\Extension\Core\Type\ChoiceType
 * @package Form
 */
class UserType extends AbstractType
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

        if (isset($options['validation_groups'])
                && count($options['validation_groups'])
                && !in_array('user-edit', $options['validation_groups'])
                && !in_array('user-view', $options['validation_groups'])
            ) {
            $builder->add(
                'login',
                TextType::class,
                [
                    'label' => 'label.login',
                    'required' => true,
                    'attr' => [
                        'max_length' => 128,
                    ],
                    'constraints' => array(
                            new Assert\NotBlank(
                                array(
                                    'groups' => array('user-default', 'user-edit', 'user-view', ),
                                )
                            ),
                            new Assert\Length(
                                array(
                                    'min' => 6,
                                    'max' => 100,
                                    'groups' => array('user-default', 'user-edit', 'user-view', ),
                                )
                            ),
                        ),
                ]
            );
        } else {
            $builder->add(
                'info_id',
                HiddenType::class,
                [
                'label' => 'label.info_id',
                'required' => true,
                'attr' => [
                'max_length' => 256,
                ],
                ]
            );
            if (isset($options['validation_groups'])
                && count($options['validation_groups'])
                && !in_array('user-view', $options['validation_groups'])
                && !in_array('user-default', $options['validation_groups'])
            ) {
                $builder->add(
                    'role_id',
                    ChoiceType::class,
                    array(
                    'label' => 'label.role',
                    'choices' => ['ADMIN' => '1', 'USER' => '2', ],
                    'required' => true,
                    )
                );
            }
            if (isset($options['validation_groups'])
                && count($options['validation_groups'])
                && in_array('user-view', $options['validation_groups'])
            ) {
                $builder->add(
                    'role_id',
                    HiddenType::class,
                    [
                        'label' => 'label.role',
                        'required' => true,
                        'attr' => [
                            'max_length' => 128,
                        ],
                    ]
                );
            }
        }
        $builder->add(
            'password',
            RepeatedType::class,
            [
                  'type' => PasswordType::class,
                  'label' => 'label.password',
                  'invalid_message' => 'not_same',
                  'options' => array('attr' => array('class' => 'password-field')),
                  'required' => true,
                  'first_options'  => array('label' => 'label.password'),
                  'second_options' => array('label' => 'label.second_password'),
                  'attr' => [
                      'maxlength' => 255,
                  ],
                  'constraints' => array(
                      new Assert\NotBlank(
                          array(
                                    'groups' => array('user-default', 'user-edit', 'user-view', ),
                                )
                      ),
                      new Assert\Length(array('min' => 8, )),
                  ),
                ]
        );
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => array(
                        new Assert\NotBlank(
                            array(
                                'groups' => array('user-default', 'user-edit', 'user-view', ),
                            )
                        ),
                        new Assert\Length(
                            array(
                                'min' => 2,
                                'max' => 100,
                                'groups' => array('user-default', 'user-edit', 'user-view', ),
                            )
                        ),
                    ),
            ]
        );
        $builder->add(
            'mail',
            TextType::class,
            [
                'label' => 'label.email',
                'required' => true,
                'attr' => [
                    'maxlength' => 100,

                ],
                'constraints' => array(
                    new Assert\NotBlank(
                        array(
                                    'groups' => array('user-default', 'user-edit', 'user-view', ),
                                )
                    ),
                    new Assert\Email(),
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
                'validation_groups' => 'user-default',
            ]
        );

        return 'user-default';
    }
    /**
     * Getter for form name.
     *
     * @return string Form name
     */
    public function getBlockPrefix()
    {
        return 'user_type';
    }
}
