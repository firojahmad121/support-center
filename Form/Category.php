<?php 
namespace Webkul\UVDesk\SupportCenterBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
class Category extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, array('required' => true))
                ->add('description', TextareaType::class, array('required' => false,))
                ->add('sorting', ChoiceType::class, array(
                            'choices'  => array(
                                            'ascending' => 'category.ascending',
                                            'descending' => 'category.descending',
                                            'popularity' => 'category.popularity'
                                        )
                        )
                )
                ->add('sortOrder', TextType::class, array(
                            'required' => false
                        )
                    )
                ->add('status', ChoiceType::class, array(
                        'choices'  => array(
                                        '1' => 'Publish',
                                        '0' => 'Draft',
                                    ),
                        'required' => false,
                    )
                )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategory',
            'csrf_protection' => false,
            'allow_extra_fields' => true
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