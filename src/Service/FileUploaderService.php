<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

// TODO service -> MyToolBundle
// Что касается самой загрузки тут всё есть https://symfony.com/doc/3.4/controller/upload_file.html

/**
 * Возможно, в FormType это окажется обяхательным
 * 
 * public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    } 
 * 
 * *
 */

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
			$this->_setConstraintsTypeImage();//TODO
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
	 * @param string sWarningMessage
	**/
	public function setMimeWarningMessage(string $s)
	{
		$this->_aConstraints['mimeTypesMessage'] = $s;
	}
	/**
	 * @param string label
	**/
	public function setFileInputLabel(string $s)
	{
		$this->_sFileInputLabel = $s;
	}
	
	public function setTargetDirectory(string $sTargetDirectory)
	{
		$this->_sTargetDirectory = $sTargetDirectory;
	}
/**
 * 
 * if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile $brochureFile *
        $brochureFile = $form['brochure']->getData();
        if ($brochureFile) {
            $brochureFileName = $fileUploader->upload($brochureFile);
            $product->setBrochureFilename($brochureFileName);
        }

        // ...
    }
	***/
	public function upload(UploadedFile $file)
	{
		$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
		$fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

		try {
			$file->move($this->getTargetDirectory(), $fileName);
		} catch (FileException $e) {
			//TODO
			// ... handle exception if something happens during file upload
		}
		return $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
	/**
	 * Set Constraints type Image
	**/
	private function _setConstraintsTypeImage()
	{
		$this->_sConstraintClassName = str_replace('\File', '\Image', $this->_sConstraintClassName);
	}
}
