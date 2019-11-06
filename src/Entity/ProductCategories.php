<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCategories
 *
 * @ORM\Table(name="product_categories")
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY", region="global")
 */
class ProductCategories
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
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parentId = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_accepted", type="boolean", nullable=true)
     */
    private $isAccepted = '0';

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=true)
     */
    private $isDeleted = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="delta", type="integer", nullable=true)
     */
    private $delta = '0';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIsAccepted(): ?bool
    {
        return $this->isAccepted;
    }

    public function setIsAccepted(?bool $isAccepted): self
    {
        $this->isAccepted = $isAccepted;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): self
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


}
