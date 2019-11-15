<?php

namespace App\Form;
//use \FOS\UserBundle\Form\Type\ProfileFormType;

use Symfony\Component\Form\AbstractType;

//use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class ProfileFormType extends AbstractType{
	
	/**
     * Return the class of the type being extended.
    */
    /*public function getExtendedType()
    {
        // return FormType::class to modify (nearly) every field in the system
        return [FileType::class];
    }*/
	
	public function buildForm(FormBuilderInterface $oBuilder, array $options)
	{
		$oBuilder->remove('username');
		$oBuilder->add('display_name');
		$oBuilder->remove('current_password');
		
		$oBuilder->add('current_password', PasswordType::class, array(
            'label' => 'form.current_password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,							//когда установлено в false, поле в сущности не будет обновляться при отправке формы
			'required' => false,						//когда установлено в false у инпута не будет атрибута required
            'attr' => array(							//установка атрибута тэга
                'autocomplete' => 'current-password',
            ),
        ));
		
		$oBuilder->add('plainPassword', RepeatedType::class, array(
            'type' => PasswordType::class,
			'mapped' => false,
			'required' => false,
            'options' => array(				//видимо потому что RepeatedType сложный пользовательский тип
											//эти два атрибута вынесены в отдельный массив options
                'translation_domain' => 'FOSUserBundle',
                'attr' => array(
                    'autocomplete' => 'new-password',
                ),
            ),
            'first_options' => array('label' => 'form.new_password'),
            'second_options' => array('label' => 'form.new_password_confirmation'),
            'invalid_message' => 'fos_user.password.mismatch',
        ));
	}
	
	public function getParent()
	{
		return 'FOS\UserBundle\Form\Type\ProfileFormType';
	}
	
	public function getBlockPrefix() : string
	{
		return 'app_user_profile';
	}
	
	public function getName() : string
	{
		return $this->getBlockPrefix();		
	}
	
}