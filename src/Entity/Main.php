<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints AS Assert;
/**
 * Main
 *
 * @ORM\Table(name="main", indexes={@ORM\Index(name="city", columns={"city"}), @ORM\Index(name="box", columns={"box"}), @ORM\Index(name="far", columns={"far"}), @ORM\Index(name="piknik", columns={"piknik"}), @ORM\Index(name="region", columns={"region"}), @ORM\Index(name="people", columns={"people"}), @ORM\Index(name="term", columns={"term"}), @ORM\Index(name="near", columns={"near"}), @ORM\Index(name="phone", columns={"phone"})})
 * @ORM\Entity
 * @ORM\Cache(usage="READ_ONLY", region="global")
 */
class Main
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
     * @var int|null
     *
     * @ORM\Column(name="region", type="integer", nullable=true, options={"comment"="Номер региона"})
     */
	private $region = '1';
	
	/**
	 * @var Regions
	 * @ORM\ManyToOne(targetEntity="Regions", inversedBy="regions")
	 * @ORM\JoinColumn(name="region", referencedColumnName="id")
	*/
	private $regionObject;

    /**
     * @var int|null
     *
     * @ORM\Column(name="city", type="integer", nullable=true, options={"comment"="Номер города"})
     */
	private $city;
	
	/**
	 * @var Cities
	 * @ORM\ManyToOne(targetEntity="Cities", inversedBy="advertsByCity")
	 * @ORM\JoinColumn(name="city", referencedColumnName="id")
	*/
	private $cityObject;
	
	
	/**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true, options={"comment"="users.id"})
     */
	private $userId;
	
	/**
	 * @var Users
	 * @ORM\ManyToOne(targetEntity="Users", inversedBy="advertsByUsername")
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
	*/
	private $userObject;

    /**
     * @var int|null
     *
     * @ORM\Column(name="people", type="smallint", nullable=true, options={"comment"="1 - если газель пассажирская"})
     */
    private $people;

    /**
     * @var string|null
     *
     * @ORM\Column(name="price", type="decimal", precision=12, scale=2, nullable=true, options={"comment"="Стоимость"})
     */
    private $price = 1.00;

    /**
     * @var int|null
     *
     * @ORM\Column(name="box", type="smallint", nullable=true, options={"comment"="1 - если грузовая"})
     */
    private $box;

    /**
     * @var int|null
     *
     * @ORM\Column(name="term", type="smallint", nullable=true, options={"comment"="1 - если термобудка"})
     */
    private $term;

    /**
     * @var int|null
     *
     * @ORM\Column(name="far", type="smallint", nullable=true, options={"comment"="1 - если межгород"})
     */
    private $far;

    /**
     * @var int|null
     *
     * @ORM\Column(name="near", type="smallint", nullable=true, options={"comment"="1 - если по городу"})
     */
    private $near;

    /**
     * @var int|null
     *
     * @ORM\Column(name="piknik", type="smallint", nullable=true, options={"comment"="1 - если по пикник"})
     */
    private $piknik;

    /**
     * @var string|null
     * @Assert\Length(min=1, max=255)
     * @ORM\Column(name="title", type="string", length=255, nullable=true, options={"comment"="Заголовок объявления"})
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=512, nullable=true, options={"comment"="Путь к файлу изображений от корня сервера"})
     */
    private $image;

    
    /**
     * @var string|null
     *
     * @ORM\Column(name="addtext", type="string", length=1000, nullable=true, options={"comment"="Текст объявления"})
     */
    private $addtext;

    /**
     * @var string|null
     * @Assert\Length(min=11, max=17)
     * @ORM\Column(name="phone", type="string", length=15, nullable=true, options={"comment"="Номер телефона"})
     */
    private $phone;

    /**
     * @var int|null
     *
     * @ORM\Column(name="pinned", type="smallint", nullable=true, options={"comment"="Закреплен наверху ленты"})
     */
    private $pinned = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP","comment"="Время публикации проекта"})
     */
    private $created;

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_moderate", type="integer", nullable=true, options={"comment"="Промодерирован ли конкурс"})
     */
    private $isModerate = '0';

    /**
     * @var int|null
     *
     * @ORM\Column(name="is_hide", type="integer", nullable=true, options={"comment"="Скрыто ли"})
     */
    private $isHide = '0';

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
     * @var string|null
     *
     * @ORM\Column(name="codename", type="string", length=255, nullable=true)
     */
    private $codename;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="automoderate", type="boolean", nullable=true)
     */
    private $automoderate = '0';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_update", type="datetime", nullable=true)
     */
    private $dateUpdate;

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

    public function getPeople(): ?int
    {
        return $this->people;
    }

    public function setPeople(?int $people): self
    {
        $this->people = $people;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getBox(): ?int
    {
        return $this->box;
    }

    public function setBox(?int $box): self
    {
        $this->box = $box;

        return $this;
    }

    public function getTerm(): ?int
    {
        return $this->term;
    }

    public function setTerm(?int $term): self
    {
        $this->term = $term;

        return $this;
    }

    public function getFar(): ?int
    {
        return $this->far;
    }

    public function setFar(?int $far): self
    {
        $this->far = $far;

        return $this;
    }

    public function getNear(): ?int
    {
        return $this->near;
    }

    public function setNear(?int $near): self
    {
        $this->near = $near;

        return $this;
    }

    public function getPiknik(): ?int
    {
        return $this->piknik;
    }

    public function setPiknik(?int $piknik): self
    {
        $this->piknik = $piknik;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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


    public function getAddtext(): ?string
    {
        return $this->addtext;
    }

    public function setAddtext(?string $addtext): self
    {
        $this->addtext = $addtext;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPinned(): ?int
    {
        return $this->pinned;
    }

    public function setPinned(?int $pinned): self
    {
        $this->pinned = $pinned;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

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

    public function getIsHide(): ?int
    {
        return $this->isHide;
    }

    public function setIsHide(?int $isHide): self
    {
        $this->isHide = $isHide;

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

    public function getCodename(): ?string
    {
        return $this->codename;
    }

    public function setCodename(?string $codename): self
    {
        $this->codename = $codename;

        return $this;
    }

    public function getAutomoderate(): ?bool
    {
        return $this->automoderate;
    }

    public function setAutomoderate(?bool $automoderate): self
    {
        $this->automoderate = $automoderate;

        return $this;
    }

    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    public function setDateUpdate(?\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

	public function getRegionObject(): Regions
	{
		return $this->regionObject;
	}

	public function getCityObject(): Cities
	{
		return $this->cityObject;
	}
	
	public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $nUserId): self
    {
        $this->userId = $nUserId;

        return $this;
    }
			
	public function getUserObject(): ?Users
    {
        return $this->userObject;
    }

    
}
