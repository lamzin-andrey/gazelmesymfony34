<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAdverts
 *
 * @ORM\Table(name="main")
 * @ORM\Entity
 */
class UserAdverts
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
     * @var string|null
     *
     * @ORM\Column(name="phone", type="string", length=15, nullable=true, options={"comment"="Номер телефона"})
    */
	private $phone;
	/**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=15, nullable=true, options={"comment"="Номер телефона"})
    */
	private $titles;
	/**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=15, nullable=true, options={"comment"="Номер телефона"})
    */
	private $idlist;
	
	public function getId()
	{
		return $this->id;
	}
	public function getPhone()
	{
		return $this->phone;
	}
	public function getTitles()
	{
		return $this->titles;
	}
	public function getIdlist()
	{
		return $this->idlist;
	}
	/*public function setId($id)
	{
		$this->id = $id;
	}
	public function setPhone($phone)
	{
		$this->phone = $phone;
	}
	public function setTitles($s)
	{
		$this->titles = $s;
	}
	public function setIdlist($idlist)
	{
		$this->idlist = $idlist;
	}*/
}
