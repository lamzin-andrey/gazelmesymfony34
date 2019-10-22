<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cities
 *
 * @ORM\Table(name="cities")
 * @ORM\Entity
 */
class Cities
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
     * @ORM\Column(name="city_name", type="string", length=64, nullable=true, options={"comment"="Название города"})
     */
    private $cityName;

    /**
     * @var int|null
     *
     * @ORM\Column(name="region", type="integer", nullable=true, options={"comment"="Id региона, если 0, значит город вне региона, например Москва или Питер"})
     */
    private $region;
    
    /**
	 * @var Regions
	 * @ORM\ManyToOne(targetEntity="Regions", inversedBy="citiesByRegion")
     * @ORM\JoinColumn(name="region", referencedColumnName="id")
	*/
	private $regionObject;

    /**
     * @var int|null
     *
     * @ORM\Column(name="country", type="integer", nullable=true, options={"comment"="Id страны"})
     */
    private $country;

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

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(?string $cityName): self
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getRegion(): ?int
    {
        return $this->region;
    }

    public function setRegion(?int $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getCountry(): ?int
    {
        return $this->country;
    }

    public function setCountry(?int $country): self
    {
        $this->country = $country;

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
