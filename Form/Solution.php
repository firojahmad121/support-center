<?php
namespace Webkul\UVDesk\SupportCenterBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
class Solution extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array(
                                            'label' => 'solution.name',
                                            'attr' => array(
                                                        'parent-div-class' => 'false',
                                                        )
                                        )
                                )
                                            
                            ->add('description', TextareaType::class, array(
                                                        'required' => false,
                                                        'label' => 'solution.description',
                                                        'attr' => array(
                                                                    'placeholder' => 'solution.description.placeholder',
                                                                    'parent-div-class' => 'false',
                                                                    )
                                                    )
                                )
                            ->add('visibility', ChoiceType::class, array(
                                        'required' => false,
                                        'label' => 'Status',
                                        'choices'  => array(
                                                        'public' => 'public',
                                                        'private' => 'private',
                                                    )
                                    )
                                )
                            ->add('solutionImage', FileType::class, array(
                                    'label' => 'Folder Image',
                                    'required' => false,
                                    'data_class' => null
                                )
                            );
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Webkul\UVDesk\SupportCenterBundle\Entity\Solutions',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '';
    }
}