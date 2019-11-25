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

class AdvertForm extends AbstractType
{
	public function buildForm(FormBuilderInterface $oBuilder, array $options)
	{
		$oBuilder->add('region', TextType::class, [
			'required' => false,
			'translation_domain' => 'Adform',
			'attr' => [
				'value' => 1
			]
		]);
		$oBuilder->add('city', TextType::class, [
			'required' => false,
			'translation_domain' => 'Adform',
			'attr' => [
				'value' => 0
			]
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
		$oBuilder->add('title', TextType::class);
		$oBuilder->add('image', TextType::class, [
			'required' => false
		]);
		$oBuilder->add('addtext', TextareaType::class);
		$oBuilder->add('price', IntegerType::class);
		$oBuilder->add('phone', TextType::class);
		
		$oBuilder->add('password', PasswordType::class, [
			'mapped' => false
		]);
		$oBuilder->add('email', EmailType::class, [
			'mapped' => false
		]);
	}
	
	public function getName() : string
	{
		return 'app.advertform';
	}
}
