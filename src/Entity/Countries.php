<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Countries
 *
 * @ORM\Table(name="countries")
 * @ORM\Entity
 * ORM\Cache(usage="READ_ONLY", region="global")
 */
class Countries
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
     * @ORM\Column(name="country_name", type="string", length=64, nullable=true, options={"comment"="Название страны"})
     */
    private $countryName;

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
     * @var int|null
     *
     * @ORM\Column(name="is_moderate", type="integer", nullable=true, options={"comment"="Промодерирован или нет."})
     */
    private $isModerate = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="codename", type="string", length=128, nullable=true)
     */
    private $codename;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setCountryName(?string $countryName): self
    {
        $this->countryName = $countryName;

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

    public function getIsModerate(): ?int
    {
        return $this->isModerate;
    }

    public function setIsModerate(?int $isModerate): self
    {
        $this->isModerate = $isModerate;

        return $this;
    }

    public function getCodename(): ?string
    {
        return $this->codename;
    }

    public function setCodename(?string $codename): self
    {
        $this->codename = $codename;

        return $this;
    }


}
