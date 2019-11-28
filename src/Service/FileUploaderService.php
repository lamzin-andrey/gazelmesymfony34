<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

// TODO service -> MyToolBundle


/**
 * 
 * Usage 
 *
 * settings:
 * 
 *    controller:
 * 
 *		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'file_uploader' => $oFileUploaderService,
			'request' => $oRequest,
			'uploaddir' => $this->_subdir
		]);
 *
 *	  in buildForm:
 *		
 *		$this->_oFileUploader = $options['file_uploader'];
		$this->_oRequest = $options['request'];
		$this->_oFileUploader->addAllowMimetype('image/jpeg');
		$this->_oFileUploader->addAllowMimetype('image/png');
		$this->_oFileUploader->addAllowMimetype('image/gif');
		$this->_oFileUploader->setFileInputLabel('Append file!');
		$this->_oFileUploader->setMimeWarningMessage('Choose allowed file type');
		$this->_oFileUploader->setMaxImageHeight(480);
		$this->_oFileUploader->setMaxImageWidth(640);
		$subdir = $options['uploaddir'];
		$sTargetDirectory = $this->_oRequest->server->get('DOCUMENT_ROOT') . '/' . $subdir;
		$this->_oFileUploader->setTargetDirectory($sTargetDirectory);
		$aOptions = $this->_oFileUploader->getFileTypeOptions();
		$aOptions['translation_domain'] = 'Adform';
		$oBuilder->add('imagefile', FileType::class, $aOptions);
 *
 *
 * 
 * save:
 * 
 *     controller: 
 *
 *
 * if ($this->_oForm->isValid()) {
        //save file
		$oFile = $this->_oForm['imagefile']->getData();
        if ($oFile) {
            $sFileName = $this->_oFileUploaderService->upload($oFile);
            $this->_oAdvert->setImageLink('/' . $this->_subdir . '/' . $sFileName);
        }

        // ...
    }
***/

/**
 *  
*/
class FileUploaderService
{
	/** @property string $_sDefaultFileInputLabel */
	private $_sDefaultFileInputLabel = 'Add file';
	
	/** @property string $_sFileInputLabel */
	private $_sFileInputLabel;
	
	/** @property array $_aConstraints */
	private $_aConstraints = [];
	
	/** @property string $_sConstraintClassName */
	private $_sConstraintClassName = '\Symfony\Component\Validator\Constraints\File';
	
	/** @property string $_sError humanly error text */
	private  $_sError;
	
	/** @property string $_sErrorInfo extend info about error*/
	private $_sErrorInfo;
	
	
	public function __construct(ContainerInterface $container)
	{
		$this->oContainer = $container;
		$this->translator = $container->get('translator');
	}
	/**
	 * Helper for create argument $options FormBuilderInterface::add('..', FileType::class, $options)
	 * Пoмощник для формирования аргумента $options метода FormBuilderInterface::add('..', FileType::class, $options)
	 * use setMaxSize, addAllowMimetype, setMimetypeMessage, setMaxWidth, setMaxHeight before call this method
	*/
	public function getFileTypeOptions() : array
	{
		$t = $this->translator;
		$this->_sFileInputLabel = is_null($this->_sFileInputLabel) ? $this->_sDefaultFileInputLabel : $this->_sFileInputLabel;
		$a = [
			'mapped'   => false,
			'required' => false,
			'label'    => $t->trans($this->_sFileInputLabel)
		];
		
		if ($this->_aConstraints) {
			$sConstraintsClassName = $this->_sConstraintClassName;
			$a['constraints'] = [new $sConstraintsClassName($this->_aConstraints)];
		}
		return $a;
	}
	/**
	 * @param string mime, example 'application/pdf' or 'image/jpeg'
	**/
	public function addAllowMimetype(string $sMime)
	{
		if (strpos($sMime, 'image/') !== false) {
			$this->_setConstraintsTypeImage();
		}
		if (!isset($this->_aConstraints['mimeTypes'])) {
			$this->_aConstraints['mimeTypes'] = [];
		}
		$this->_aConstraints['mimeTypes'][] = $sMime;
	}
	/**
	 * @param int $nKBytes kilobytes
	**/
	public function setMaxFileSize(int $nKBytes)
	{
		$this->_aConstraints['maxSize'] = $nKBytes . 'k';
	}
	/**
	 * @param int $nWidth image width
	**/
	public function setMaxImageWidth(int $nWidth)
	{
		$this->_setConstraintsTypeImage();
		$this->_aConstraints['maxWidth'] = $nWidth;
	}
	/**
	 * @param int $nHeight image height
	**/
	public function setMaxImageHeight(int $nHeight)
	{
		$this->_setConstraintsTypeImage();
		$this->_aConstraints['maxHeight'] = $nHeight;
	}
	/**
	 * @param string sWarningMessage no translate message
	**/
	public function setMimeWarningMessage(string $s)
	{
		/** @var \Symfony\Component\Translation\DataCollectorTranslator $t */
		$t = $this->translator;
		$this->_aConstraints['mimeTypesMessage'] = $t->trans($s, [], 'Adform');
	}
	/**
	 * @param string label no translate message
	**/
	public function setFileInputLabel(string $s)
	{
		/** @var \Symfony\Component\Translation\DataCollectorTranslator $t */
		$t = $this->translator;
		$this->_sFileInputLabel = $t->trans($s, [], 'Adform');;
	}
	
	public function setTargetDirectory(string $sTargetDirectory)
	{
		$this->_sTargetDirectory = $sTargetDirectory;
	}
	/**
	 * Upload action
	**/
	public function upload(\Symfony\Component\HttpFoundation\File\UploadedFile $file) : string
	{
		$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$safeFilename = $this->oContainer->get('App\Service\GazelMeService')->translite_url($originalFilename);
		$fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

		try {
			$file->move($this->getTargetDirectory(), $fileName);
		} catch (FileException $e) {
			$t = $this->translator;
			$this->_sError = $t->trans('Unable upload file', [], 'Adform');
			$this->_sErrorInfo = $e->getMessage();
			$fileName = '';
		}
		return $fileName;
    }
	/**
	 * 
	**/
    public function getTargetDirectory()
    {
        return $this->_sTargetDirectory;
    }
	/**
	 * Set Constraints type Image
	**/
	private function _setConstraintsTypeImage()
	{
		$this->_sConstraintClassName = str_replace('\File', '\Image', $this->_sConstraintClassName);
	}
	
	 /**
     * Write a thumbnail image using the LiipImagineBundle
     * 
     * @param Document $document an Entity that represents an image in the database
     * @param string $filter the Imagine filter to use
     */
    private function writeThumbnail($path, $filter) {
        //$path = $document->getWebPath();                                // domain relative path to full sized image
        $tpath = $path;//$document->getRootDir().$document->getThumbPath();     // absolute path of saved thumbnail

        $container = $this->oContainer;                                  // the DI container
        $dataManager = $container->get('liip_imagine.data.manager');    // the data manager service
        $filterManager = $container->get('liip_imagine.filter.manager');// the filter manager service

        $image = $dataManager->find($filter, $path);                    // find the image and determine its type
        $response = $filterManager->get($this->getRequest(), $filter, $image, $path); // run the filter 
        $thumb = $response->getContent();                               // get the image from the response

        $f = fopen($tpath, 'w');                                        // create thumbnail file
        fwrite($f, $thumb);                                             // write the thumbnail
        fclose($f);                                                     // close the file
    }
}
