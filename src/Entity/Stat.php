<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stat
 *
 * @ORM\Table(name="stat", uniqueConstraints={@ORM\UniqueConstraint(name="location", columns={"region", "country", "city"})}, indexes={@ORM\Index(name="region", columns={"region"}), @ORM\Index(name="city", columns={"city"})})
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY")
 */
class Stat
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
     * @var int|null
     *
     * @ORM\Column(name="region", type="integer", nullable=true, options={"comment"="Номер региона"})
     */
    private $region;

    /**
     * @var int|null
     *
     * @ORM\Column(name="city", type="integer", nullable=true, options={"comment"="Номер города"})
     */
    private $city;

    /**
     * @var int|null
     *
     * @ORM\Column(name="country", type="integer", nullable=true, options={"comment"="Номер страны"})
     */
    private $country;

    /**
     * @var int|null
     *
     * @ORM\Column(name="cnt", type="integer", nullable=true, options={"comment"="Счетчик обращений к страницам, на которых пока нет объявлений"})
     */
    private $cnt;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCity(): ?int
    {
        return $this->city;
    }

    public function setCity(?int $city): self
    {
        $this->city = $city;

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

    public function getCnt(): ?int
    {
        return $this->cnt;
    }

    public function setCnt(?int $cnt): self
    {
        $this->cnt = $cnt;

        return $this;
    }


}
