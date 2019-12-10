<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;


class AdvertForm extends AbstractType
{
	
	/** @property \Landlib\SymfonyToolsBundle\Service\FileUploaderService $_oFileUploader */
	private $_oFileUploader;
	
	/** @property  \Symfony\Component\HttpFoundation\Request $_oRequest */
	private $_oRequest;
	
	
	public function buildForm(FormBuilderInterface $oBuilder, array $options)
	{
		$oBuilder->add('region', HiddenType::class, [
			'required' => false,
			'translation_domain' => 'Adform'
		]);
		$oBuilder->add('city', HiddenType::class, [
			'required' => false,
			'translation_domain' => 'Adform'
		]);
		$oBuilder->add('people', CheckboxType::class, [
			'required' => false
		]);
		$oBuilder->add('box', CheckboxType::class, [
			'required' => false
		]);
		$oBuilder->add('term', CheckboxType::class, [
			'required' => false
		]);
		$oBuilder->add('far', CheckboxType::class, [
			'required' => false
		]);
		$oBuilder->add('near', CheckboxType::class, [
			'required' => false
		]);
		$oBuilder->add('piknik', CheckboxType::class, [
			'required' => false
		]);
		$oBuilder->add('title', TextType::class, [
			'translation_domain' => 'Adform',
		]);
		/*$oBuilder->add('image', TextType::class, [
			'required' => false
		]);*/
		$oBuilder->add('addtext', TextareaType::class, [
			'attr' => [
				'rows' => 16,
				'style' => 'width:100%',
				'rel' => 'afctrl'
			]
		]);
		$oBuilder->add('price', MoneyType::class, [
			'currency' => '',
			'required' => false
		]);
		$oBuilder->add('phone', TextType::class, [
			'translation_domain' => 'Adform'
		]);
		
		$oBuilder->add('company_name', TextType::class, [
			'mapped' => false
		]);
		
		$oBuilder->add('password', PasswordType::class, [
			'mapped' => false,
			'required' => false,
			'translation_domain' => 'Adform',
			'constraints' => [
				new Regex([
					'pattern' => '/(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])/s',
					'htmlPattern' => '.*[A-Z]',
					'message' => 'Password must containts symbols in upper and lower case and numbers'
				]),
			]
		]);
		$oBuilder->add('email', EmailType::class, [
			'mapped' => false,
			'required' => false,
			'translation_domain' => 'Adform',
			'constraints' => [
				new Email(['message' => 'The email {{ value }} is not valid message'])
			]
		]);
		$oBuilder->add('agreement', CheckboxType::class, [
			'mapped' => false
		]);
		$options['app_service']->addAdvertPhotoField($options['uploaddir'], $oBuilder);
	}
	
	public function getName() : string
	{
		return 'app.advertform';
	}
	public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setRequired('app_service');
        $resolver->setRequired('request');
        $resolver->setRequired('uploaddir');
    }
}
