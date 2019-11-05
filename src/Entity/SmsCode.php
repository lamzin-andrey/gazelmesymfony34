<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SmsCode
 *
 * @ORM\Table(name="sms_code", uniqueConstraints={@ORM\UniqueConstraint(name="phone", columns={"phone"})})
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY")
 */
class SmsCode
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone", type="string", length=15, nullable=true, options={"comment"="Телефон"})
     */
    private $phone;

    /**
     * @var int|null
     *
     * @ORM\Column(name="code", type="integer", nullable=true, options={"comment"="Актуальный код для данного телефона"})
     */
    private $code;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;

        return $this;
    }


}
