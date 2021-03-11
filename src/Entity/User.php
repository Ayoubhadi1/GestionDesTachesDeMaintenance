<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity(repositoryClass=App\Repository\UserRepository::class)
 * @UniqueEntity(
 * fields = {"email"},
 * message="L'email que vous avez indiqué est dèjà utilisé !"
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $nom_complet;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $image;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $telephone;

    

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min="8" , minMessage="Votre mot de passe doit faire minimum 8 caractères")
     */
    private $password;

    public $confirm_password;


    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\OneToMany(targetEntity=Materiel::class, mappedBy="Creator")
     */
    private $materiels;

    /**
     * @ORM\ManyToMany(targetEntity=Tache::class, mappedBy="technicien")
     */
    private $taches;

    public function __construct()
    {
        $this->materiels = new ArrayCollection();
        $this->taches = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nom_complet;
    }

    public function setNomComplet(string $nom_complet): self
    {
        $this->nom_complet = $nom_complet;

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage(string $image)
    {
        $this->image = $image;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(){}
    public function getSalt(){}
    public function getRoles() : array
    {
        $roles = $this->roles ;
        //$roles[] = 'ROLE_TECH';
        return array_unique($roles);
    }
    public function setRoles(array $roles) : self
    {
        $this->roles = $roles ;

        return $this;
    }
    public function getUsername(){}

    /**
     * @return Collection|Materiel[]
     */
    public function getMateriels(): Collection
    {
        return $this->materiels;
    }

    public function addMateriel(Materiel $materiel): self
    {
        if (!$this->materiels->contains($materiel)) {
            $this->materiels[] = $materiel;
            $materiel->setCreator($this);
        }

        return $this;
    }

    public function removeMateriel(Materiel $materiel): self
    {
        if ($this->materiels->removeElement($materiel)) {
            // set the owning side to null (unless already changed)
            if ($materiel->getCreator() === $this) {
                $materiel->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Tache[]
     */
    public function getTaches(): Collection
    {
        return $this->taches;
    }

    public function addTach(Tache $tach): self
    {
        if (!$this->taches->contains($tach)) {
            $this->taches[] = $tach;
            $tach->addTechnicien($this);
        }

        return $this;
    }

    public function removeTach(Tache $tach): self
    {
        if ($this->taches->removeElement($tach)) {
            $tach->removeTechnicien($this);
        }

        return $this;
    }

    public function estDisponible(): ?bool
    {
        foreach($this->taches as $tache){
            if($tache->getEtat() != "Finie"){
                return false;
            }
        }
        return true;
    }

    
}
