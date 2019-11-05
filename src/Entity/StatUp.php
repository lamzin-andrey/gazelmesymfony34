<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatUp
 *
 * @ORM\Table(name="stat_up", uniqueConstraints={@ORM\UniqueConstraint(name="_date", columns={"_date"})})
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY")
 */
class StatUp
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
     * @ORM\Column(name="_date", type="date", nullable=true, options={"comment"="День добавления"})
     */
    private $date;

    /**
     * @var int|null
     *
     * @ORM\Column(name="_count", type="integer", nullable=true, options={"comment"="Счетчик обращений к страницам, на которых пока нет объявлений"})
     */
    private $count;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }


}
