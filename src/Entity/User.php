<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", name="id")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min = 2,
     *     max = 32
     * )
     * @Assert\Regex(
     *     pattern="/^[\w.\-]+$/"
     * )
     * @ORM\Column(type="string", length=180, unique=true, name="username")
     */
    private $username;

    /**
     * @ORM\Column(type="json", name="roles", options={"jsonb": true})
     */
    private $roles = [];

    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min = 1,
     *     max = 4096
     * )
     * @Assert\Regex(
     *     pattern="/^^[\w!@#$%^&*()<\-=+.,.?]+$/"
     * )
     */
    private $plainPassword;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", name="password", nullable=true)
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Token", mappedBy="user", orphanRemoval=true)
     */
    private $tokens;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="permanent")
     */
    private $permanent;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $registratedAt;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->tokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): self
    {
        if (!\in_array($role, $this->roles)) {
            $this->roles[] = $role;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function toArray()
    {
        return [
            'username' => $this->getUsername(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
            'isPermanent' => $this->getPermanent(),
            'roles' => $this->getRoles()
        ];
    }

    public function getRegistratedAt(): ?\DateTimeInterface
    {
        return $this->registratedAt;
    }

    public function setRegistratedAt(?\DateTimeInterface $registratedAt): self
    {
        $this->registratedAt = $registratedAt;

        return $this;
    }
}
