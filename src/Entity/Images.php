<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Images
 *
 * @ORM\Table(name="images")
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY")
 */
class Images
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
     * @ORM\Column(name="main_id", type="integer", nullable=true, options={"comment"="Идентификатор объявления из main"})
     */
    private $mainId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=512, nullable=true, options={"comment"="Изображение связанное с объявлением"})
     */
    private $image;

    /**
     * @var string|null
     *
     * @ORM\Column(name="big", type="string", length=32, nullable=true, options={"comment"="Имя большого файла изображения, только короткое имя без расширения"})
     */
    private $big;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMainId(): ?int
    {
        return $this->mainId;
    }

    public function setMainId(?int $mainId): self
    {
        $this->mainId = $mainId;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getBig(): ?string
    {
        return $this->big;
    }

    public function setBig(?string $big): self
    {
        $this->big = $big;

        return $this;
    }


}
