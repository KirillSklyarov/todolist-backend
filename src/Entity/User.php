<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_REGISTRED_USER = 'ROLE_REGISTRED_USER';
    const ROLE_UNREGISTRED_USER = 'ROLE_UNREGISTRED_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", name="id")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=36)
     */
    private $uuid;

    /**
     * @var string
     * @ORM\Column(type="string", length=180, unique=true, name="username")
     */
    private $username;

    /**
     * @ORM\Column(type="json", name="roles", options={"jsonb": true})
     */
    private $roles = [];

    /**
     * @var string|null
     */
    private $plainPassword;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", name="password", nullable=true)
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Token",
     *     mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    private $tokens;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="permanent")
     */
    private $permanent;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", name="registred_at", nullable=true)
     */
    private $registredAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="last_enter_at")
     */
    private $lastEnterAt;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Item", mappedBy="user",
     *     orphanRemoval=true)
     */
    private $items;

    /**
     * @var Token|null
     */
    private $currentToken;

    /**
     * User constructor.
     * @throws UnsatisfiedDependencyException
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->username = $this->getUuid();
        $this->tokens = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return \array_unique($roles);
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): self
    {
        $this->roles = \array_unique($roles);

        return $this;
    }

    /**
     * @param string $role
     * @return User
     */
    public function addRole(string $role): self
    {
        if (!\in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * @param string $role
     * @return User
     */
    public function removeRole(string $role): self
    {
        $index = \array_search($role, $this->roles);
        if (false !== $index) {
            \array_splice($this->roles, $index, 1);
        }

        return $this;
    }
    /**
     * @return null|string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param null|string $plainPassword
     * @return User
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
         $this->plainPassword = null;
    }

    /**
     * @return Collection|Token[]
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(Token $token): self
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens[] = $token;
            $token->setUser($this);
        }

        return $this;
    }

    public function removeToken(Token $token): self
    {
        if ($this->tokens->contains($token)) {
            $this->tokens->removeElement($token);
            // set the owning side to null (unless already changed)
            if ($token->getUser() === $this) {
                $token->setUser(null);
            }
        }

        return $this;
    }

    public function clearTokens()
    {
        $this->tokens->clear();

        return $this;

    }

    public function getPermanent(): ?bool
    {
        return $this->permanent;
    }

    public function setPermanent(bool $permanent): self
    {
        $this->permanent = $permanent;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRegistredAt(): ?\DateTime
    {
        return $this->registredAt;
    }

    public function setRegistredAt(\DateTime $registredAt): self
    {
        $this->registredAt = $registredAt;

        return $this;
    }

    public function getLastEnterAt(): \DateTime
    {
        return $this->lastEnterAt;
    }

    public function setLastEnterAt(\DateTime $lastEnterAt): self
    {
        $this->lastEnterAt = $lastEnterAt;

        return $this;
    }

    public function toArray()
    {
        return [
            'uuid' => $this->getUuid(),
            'username' => $this->getUsername(),
//            'createdAt' => $this->getCreatedAt()->format('c'),
//            'updatedAt' => $this->getUpdatedAt()->format('c'),
//            'registredAt' => $this->getRegistredAt() ?
//                $this->getRegistredAt()->format('c') : null,
            'isPermanent' => $this->getPermanent(),
            'roles' => $this->getRoles()
        ];
    }

    /**
     * @return Token|null
     */
    public function getCurrentToken(): ?Token
    {
        return $this->currentToken;
    }

    /**
     * @param Token $currentToken
     * @return User
     */
    public function setCurrentToken(Token $currentToken): self
    {
        $this->currentToken = $currentToken;
        return $this;
    }

    /**
     * @return Collection|Item[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setUser($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
            // set the owning side to null (unless already changed)
            if ($item->getUser() === $this) {
                $item->setUser(null);
            }
        }

        return $this;
    }
}
