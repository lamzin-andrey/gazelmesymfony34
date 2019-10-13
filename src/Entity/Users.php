<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="phone", columns={"phone"})})
 * @ORM\Entity
 */
class Users
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"comment"="Первичный ключ."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pwd", type="string", length=32, nullable=true, options={"comment"="пароль"})
     */
    private $pwd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rawpass", type="string", length=32, nullable=true, options={"comment"="пароль как он есть"})
     */
    private $rawpass;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone", type="string", length=15, nullable=true, options={"comment"="Номер телефона"})
     */
    private $phone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=64, nullable=true, options={"comment"="email"})
     */
    private $email;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="sms", type="boolean", nullable=true, options={"comment"="Признак того, что я его сам добавил, предложим восстановление пароля на email"})
     */
    private $sms;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_deleted", type="integer", nullable=true, options={"comment"="Удален или нет. Может называться по другому, но тогда в cdbfrselectmodel надо указать, как именно"})
     */
    private $isDeleted = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="delta", type="integer", nullable=true, options={"comment"="Позиция.  Может называться по другому, но тогда в cdbfrselectmodel надо указать, как именно"})
     */
    private $delta;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_sms_verify", type="boolean", nullable=true, options={"comment"="Верифицирован ли пользователь по смс"})
     */
    private $isSmsVerify = '0';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="last_sms_send_time", type="datetime", nullable=true, options={"comment"="Время последней отправки"})
     */
    private $lastSmsSendTime;

    /**
     * @var string|null
     *
     * @ORM\Column(name="verify_code", type="string", length=8, nullable=true, options={"comment"="Последний запрошенный код верификации"})
     */
    private $verifyCode = '';

    /**
     * @var int|null
     *
     * @ORM\Column(name="upcount", type="integer", nullable=true, options={"default"="10"})
     */
    private $upcount = '10';

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

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


}
