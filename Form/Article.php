<?php 
namespace Webkul\SupportCenterBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Article extends AbstractType
{
    public function __construct($container, $request)
    {
        $this->container = $container;
        $this->request = $request;
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array(
                                            'required' => true,
                                            'label' => 'article.name',
                                            'attr' => array(
                                                        'class' => 'form-control',
                                                        )
                                        )
                    )
                ->add('slug', null, array(
                                        'required' => true,
                                        'label' => 'article.slug',
                                        'attr' => array(
                                                    'class' => 'form-control',
                                                    )
                                        )
                    );
                
        if($this->request->attributes->get('solution'))        
            $builder->add('category', 'entity', array(
                        'class' => 'WebkulSupportCenterBundle:SolutionCategory',
                        'required' => false,
                        'property' => 'name',
                        'multiple' => true,
                        'attr' => array(
                                    'class' => 'selectpicker'
                                ),
                        'query_builder' => function (EntityRepository $er) {
                                return $er->createQueryBuilder('t')
                                            ->andwhere('t.solution = :solution')
                                            ->setParameter(
                                                'solution', $this->request->attributes->get('solution')
                                            );    
                        },
                        'empty_data'  => [],
                        'empty_value'  => 'No Category Added',
                    )
                );

            $builder->add('contentFile', 'file', array(
                        'required' => false,
                        'label' => 'article.content.file')
                );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Webkul\SupportCenterBundle\Entity\Article',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'article';
    }
}