<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ymup
 *
 * @ORM\Table(name="ymup", indexes={@ORM\Index(name="c", columns={"c"})})
 * @ORM\Entity
 */
class Ymup
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
     * @ORM\Column(name="c", type="integer", nullable=true)
     */
    private $c;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getC(): ?int
    {
        return $this->c;
    }

    public function setC(?int $c): self
    {
        $this->c = $c;

        return $this;
    }


}
