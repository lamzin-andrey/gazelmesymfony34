<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AjaxFileUploadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->setMethod('POST');

		/*$builder->add('autophotoFileImmediately', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
			'mapped' => false,

		]);*/
		$options['app_service']->addAdvertPhotoField($options['uploaddir'], $builder, 'autophotoFileImmediately');
    }
	public function getName() : string
	{
		return 'app.ajax_file_upload_form';
	}
    public function configureOptions(OptionsResolver $resolver)
    {
		$resolver->setRequired('app_service');
		$resolver->setRequired('request');
		$resolver->setRequired('uploaddir');
		$resolver->setDefaults(array(
			'csrf_protection' => false,
		));
    }
}
