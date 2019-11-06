<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PayTransaction
 *
 * @ORM\Table(name="pay_transaction")
 * @ORM\Entity
 * ORM\Cache(usage="READ_ONLY", region="global")
 */
class PayTransaction
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
     * @ORM\Column(name="user_id", type="integer", nullable=true, options={"comment"="Идентификатор пользователя"})
     */
    private $userId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cache", type="string", length=20, nullable=true, options={"comment"="Номер кошелька"})
     */
    private $cache;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sum", type="decimal", precision=10, scale=2, nullable=true, options={"comment"="Сумма в рублях которую пользователь собирается оплатить"})
     */
    private $sum;

    /**
     * @var string
     *
     * @ORM\Column(name="real_sum", type="decimal", precision=10, scale=2, nullable=false, options={"comment"="Сумма в рублях которую пользователь реально потратил"})
     */
    private $realSum;

    /**
     * @var string|null
     *
     * @ORM\Column(name="method", type="string", length=4, nullable=true, options={"comment"="ps - платеж с Якошелька, ms - платеж с мобильного номера, bs - платеж с помощью карты"})
     */
    private $method = '';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="created", type="datetime", nullable=true, options={"comment"="Время операции"})
     */
    private $created;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_confirmed", type="boolean", nullable=true, options={"comment"="1 когда пришел HTTP запрос из Яндекса по этой записи"})
     */
    private $isConfirmed = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="ya_http_notice_id", type="integer", nullable=true, options={"comment"="Если нотайс подтверждён http запросом из Яндекса, ya_http_notice содержит запись о входящих параметрах"})
     */
    private $yaHttpNoticeId = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="rk_http_notice_id", type="integer", nullable=true, options={"comment"="Если платеж подтвержден нотайсом от робокассы, содержит id записи в rk_http_notice"})
     */
    private $rkHttpNoticeId = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCache(): ?string
    {
        return $this->cache;
    }

    public function setCache(?string $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function getSum(): ?string
    {
        return $this->sum;
    }

    public function setSum(?string $sum): self
    {
        $this->sum = $sum;

        return $this;
    }

    public function getRealSum(): ?string
    {
        return $this->realSum;
    }

    public function setRealSum(string $realSum): self
    {
        $this->realSum = $realSum;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getIsConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(?bool $isConfirmed): self
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    public function getYaHttpNoticeId(): ?int
    {
        return $this->yaHttpNoticeId;
    }

    public function setYaHttpNoticeId(?int $yaHttpNoticeId): self
    {
        $this->yaHttpNoticeId = $yaHttpNoticeId;

        return $this;
    }

    public function getRkHttpNoticeId(): ?int
    {
        return $this->rkHttpNoticeId;
    }

    public function setRkHttpNoticeId(?int $rkHttpNoticeId): self
    {
        $this->rkHttpNoticeId = $rkHttpNoticeId;

        return $this;
    }


}
