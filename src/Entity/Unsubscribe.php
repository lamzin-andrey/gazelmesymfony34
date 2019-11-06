<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Unsubscribe
 *
 * @ORM\Table(name="unsubscribe", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})})
 * @ORM\Entity
 * ORM\Cache(usage="READ_ONLY", region="global")
 */
class Unsubscribe
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
     * @ORM\Column(name="email", type="string", length=64, nullable=true, options={"comment"="Email"})
     */
    private $email;

    /**
     * @var int|null
     *
     * @ORM\Column(name="n", type="integer", nullable=true, options={"comment"="Просто чтобы было что обновлять"})
     */
    private $n;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getN(): ?int
    {
        return $this->n;
    }

    public function setN(?int $n): self
    {
        $this->n = $n;

        return $this;
    }


}
