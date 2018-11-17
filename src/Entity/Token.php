<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenRepository")
 * @ORM\Table(name="tokens")
 */
class Token
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", name="id")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=36, unique=true, name="uuid")
     */
    private $uuid;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="last_usage_at")
     */
    private $lastUsageAt;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Entity\User",
     *     inversedBy="tokens", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id",
     *     nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * Token constructor.
     * @throws UnsatisfiedDependencyException if `Moontoast\Math\BigNumber` is not present
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

//    public function setUuid(string $uuid): self
//    {
//        $this->uuid = $uuid;
//
//        return $this;
//    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastUsageAt(): \DateTime
    {
        return $this->lastUsageAt;
    }

    public function setLastUsageAt(\DateTime $lastUsageAt): self
    {
        $this->lastUsageAt = $lastUsageAt;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function toArray() {
        return [
            'uuid' => $this->getUuid(),
            'createdAt' => $this->getCreatedAt()->format('c'),
            'lastUsageAt' => $this->getLastUsageAt()->format('c'),
            'user' => $this->getUser()->toArray()
        ];
    }
}
