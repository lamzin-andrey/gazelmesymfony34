<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Googlends
 *
 * @ORM\Table(name="googlends", indexes={@ORM\Index(name="payment_date", columns={"payment_date", "payment_sum", "is_public"})})
 * @ORM\Entity
 */
class Googlends
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"comment"="Данные о  загруженных в Эльбу данных"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payment_date", type="date", nullable=false, options={"comment"="Время платежа фирмы в google adwords"})
     */
    private $paymentDate;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_sum", type="decimal", precision=10, scale=2, nullable=false, options={"comment"="Сумма  платежа фирмы в google adwords"})
     */
    private $paymentSum;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=false, options={"comment"="1 когда данные о платеже уже загруженны в Эльбу"})
     */
    private $isPublic = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_create", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP","comment"="Время создания записи"})
     */
    private $dateCreate = 'CURRENT_TIMESTAMP';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getPaymentSum(): ?string
    {
        return $this->paymentSum;
    }

    public function setPaymentSum(string $paymentSum): self
    {
        $this->paymentSum = $paymentSum;

        return $this;
    }

    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): self
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }


}
