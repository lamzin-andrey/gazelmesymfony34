<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="phone", columns={"phone"})})
 * @ORM\Entity
 * ORM\Cache(usage="NONSTRICT_READ_WRITE", region="global")
 */
class Users extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"comment"="Первичный ключ."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pwd", type="string", length=64, nullable=true, options={"comment"="пароль"})
     */
    protected $pwd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rawpass", type="string", length=32, nullable=true, options={"comment"="пароль как он есть"})
     */
    protected $rawpass;

    /**
     * @var string|null
	 *
     * @ORM\Column(name="phone", type="string", length=15, nullable=true, options={"comment"="Номер телефона"})
     */
    protected $phone;
	
	/**
     * @var array
	 * 
     * @ORM\OneToMany(targetEntity="Main", mappedBy="userObject")
     */
    protected $advertsByUsername;

    /**
	 * @Assert\Length(min=11, max=17)
	*/
    protected $username;

	/**
	 * @Assert\Regex(pattern = "/(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])/s", message="Password must containts symbols in upper and lower case and numbers")
	*/
    protected $password;

    /**
	 * @Assert\Email(message="The email {{ value }} is not valid message")
     * @var string|null
     * @ORM\Column(name="email", type="string", length=64, nullable=true, options={"comment"="email"})
     */

    /**
     * @var bool|null
     *
     * @ORM\Column(name="sms", type="boolean", nullable=true, options={"comment"="Признак того, что я его сам добавил, предложим восстановление пароля на email"})
     */
    protected $sms;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_deleted", type="integer", nullable=true, options={"comment"="Удален или нет. Может называться по другому, но тогда в cdbfrselectmodel надо указать, как именно"})
     */
    protected $isDeleted = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="delta", type="integer", nullable=true, options={"comment"="Позиция.  Может называться по другому, но тогда в cdbfrselectmodel надо указать, как именно"})
     */
    protected $delta;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_sms_verify", type="boolean", nullable=true, options={"comment"="Верифицирован ли пользователь по смс"})
     */
    protected $isSmsVerify = '0';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="last_sms_send_time", type="datetime", nullable=true, options={"comment"="Время последней отправки"})
     */
    protected $lastSmsSendTime;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verify_code", type="string", length=8, nullable=true, options={"comment"="Последний запрошенный код верификации"})
     */
    protected $verifyCode = '';

    /**
     * @var int|null
     *
     * @ORM\Column(name="upcount", type="integer", nullable=true, options={"default"="10"})
     */
    protected $upcount = '10';

	/**
	 * @var bool|null
	 *
	 * @ORM\Column(name="is_anonymous", type="boolean", nullable=true, options={"comment"="1 - когда пользователь подал объявление не вводя пароль и email"})
	 */
	protected $is_anonymous = false;
	
	/**
	 * @Assert\NotBlank(message="Display-name-required")
     * @var string|null
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true, options={"comment"="Отображамое имя или название организации"})
     */
    protected $displayName = '';
	
	public function __construct()
	{
		parent::__construct();
	}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPwd(): ?string
    {
        return $this->pwd;
    }

    public function setPwd(?string $pwd): self
    {
        $this->pwd = $pwd;

        return $this;
    }

    public function getRawpass(): ?string
    {
        return $this->rawpass;
    }

    public function setRawpass(?string $rawpass): self
    {
        $this->rawpass = $rawpass;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSms(): ?bool
    {
        return $this->sms;
    }

    public function setSms(?bool $sms): self
    {
        $this->sms = $sms;

        return $this;
    }

    public function getIsDeleted(): ?int
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?int $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDelta(): ?int
    {
        return $this->delta;
    }

    public function setDelta(?int $delta): self
    {
        $this->delta = $delta;

        return $this;
    }

    public function getIsSmsVerify(): ?bool
    {
        return $this->isSmsVerify;
    }

    public function setIsSmsVerify(?bool $isSmsVerify): self
    {
        $this->isSmsVerify = $isSmsVerify;

        return $this;
    }

    public function getLastSmsSendTime(): ?\DateTimeInterface
    {
        return $this->lastSmsSendTime;
    }

	public function setIsAnonymous($isAnonymous): self
	{
		$this->is_anonymous = (intval($isAnonymous) == 0 ? false : true);
		return $this;
	}

	public function getIsAnonymous(): ?bool
	{
		return (intval($this->is_anonymous) == 0 ? false : true);
	}

    public function setLastSmsSendTime(?\DateTimeInterface $lastSmsSendTime): self
    {
        $this->lastSmsSendTime = $lastSmsSendTime;

        return $this;
    }

    public function getVerifyCode(): ?string
    {
        return $this->verifyCode;
    }

    public function setVerifyCode(?string $verifyCode): self
    {
        $this->verifyCode = $verifyCode;

        return $this;
    }

    public function getUpcount(): ?int
    {
        return $this->upcount;
    }

    public function setUpcount(?int $upcount): self
    {
        $this->upcount = $upcount;

        return $this;
    }
	
    public function getDisplayName()
    {
        return $this->displayName;
    }
	
    public function setDisplayName($sDisplayName)
    {
        $this->displayName = $sDisplayName;

        return $this;
    }
}
