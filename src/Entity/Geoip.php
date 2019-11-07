<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Geoip
 *
 * @ORM\Table(name="geoip")
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY", region="global")
 */
class Geoip
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
     * @var \DateTime|null
     *
     * @ORM\Column(name="_time", type="datetime", nullable=true, options={"comment"="Время последнего обращения к сайту с данного ip  и с данным ua"})
     */
    private $time;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hash", type="string", length=32, nullable=true, options={"comment"="Хэш ip+ua"})
     */
    private $hash;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(?\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }


}
