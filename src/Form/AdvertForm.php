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
			'translation_domain' => 'Adform'
		]);
		$oBuilder->add('email', EmailType::class, [
			'mapped' => false,
			'required' => false,
			'translation_domain' => 'Adform'
		]);
		$oBuilder->add('agreement', CheckboxType::class, [
			'mapped' => false
		]);
		
		$this->_oFileUploader = $options['file_uploader'];
		$this->_oFileUploader->setTranslationDomain('Adform');
		$this->_oRequest = $options['request'];
		$this->_oFileUploader->addAllowMimetype('image/jpeg');
		$this->_oFileUploader->addAllowMimetype('image/png');
		$this->_oFileUploader->addAllowMimetype('image/gif');
		$this->_oFileUploader->setFileInputLabel('Append file!');
		$this->_oFileUploader->setMimeWarningMessage('Choose allowed file type');
		$this->_oFileUploader->addLiipBundleFilter('max_width');

		//$oConf = $options['container'];
		//$this->_oFileUploader->setMaxImageHeight(480);
		//$this->_oFileUploader->setMaxImageWidth(640);//640 - ok, 320 - у менея есть изображения меньше
		$subdir = $options['uploaddir'];
		$sTargetDirectory = $this->_oRequest->server->get('DOCUMENT_ROOT') . '/' . $subdir;
		
		$this->_oFileUploader->setTargetDirectory($sTargetDirectory);
		
		$aOptions = $this->_oFileUploader->getFileTypeOptions();
		$aOptions['attr'] = [
			'style' => 'width:173px;'
		];
		$aOptions['translation_domain'] = 'Adform';
		$oBuilder->add('imagefile', \Symfony\Component\Form\Extension\Core\Type\FileType::class, $aOptions);
	}
	
	public function getName() : string
	{
		return 'app.advertform';
	}
	public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver)
    {
        $resolver->setRequired('file_uploader');
        $resolver->setRequired('request');
        $resolver->setRequired('uploaddir');
    }
}
