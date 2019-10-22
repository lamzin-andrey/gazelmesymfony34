<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Regions
 *
 * @ORM\Table(name="regions")
 * @ORM\Entity
 */
class Regions
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
     * @ORM\Column(name="region_name", type="string", length=64, nullable=true, options={"comment"="Название региона"})
     */
    private $regionName;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_city", type="integer", nullable=true, options={"comment"="Признак того, что это не регион а крупный город"})
     */
    private $isCity = '0';

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
	
	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="Main", mappedBy="regionObject")
	*/
	private $regions;
	
	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="Cities", mappedBy="regionObject")
	*/
	private $cities;

    /**
     * @var int|null
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true, options={"comment"="для крупных городов - номер региона"})
     */
    private $parentId = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegionName(): ?string
    {
        return $this->regionName;
    }
    
    public function getRegions()
    {
        return $this->regions;
    }

    public function setRegionName(?string $regionName): self
    {
        $this->regionName = $regionName;

        return $this;
    }

    public function getIsCity(): ?int
    {
        return $this->isCity;
    }

    public function setIsCity(?int $isCity): self
    {
        $this->isCity = $isCity;

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

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }
    
	public function getCities()
    {
        return $this->cities;
    }

}
