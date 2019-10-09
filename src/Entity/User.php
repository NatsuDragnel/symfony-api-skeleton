<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\Collection;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;
                                                
/**
* User
*
* @ORM\Table(name="papi_user")
* @ORM\Entity(repositoryClass="App\Repository\UserRepository")
* 
*/
  class User implements AdvancedUserInterface, \Serializable
  {
    use Timestampable, Blameable;

    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

     /**
    * @var integer
    *
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
    * @ORM\Column(name="first_name", type="string", length=25)
    */
    private $firstName;

    /**
    * @ORM\Column(name="last_name", type="string", length=25)
    */
    private $lastName;

    /**
    * @ORM\Column(name="gender", type="string", length=25, nullable=true)
    */
    private $gender;

    /**
    * @ORM\Column(name="phoneNumber", type="string", length=255, nullable=true)
    */
    private $phoneNumber;

    /**
    * @ORM\Column(type="string", length=255, unique=true)
    */
    private $username;

    /**
    * @ORM\Column(type="string", length=255, unique=true)
    * 
    */
    private $email;

    /**
    * @ORM\Column(type="string", length=255, nullable=true)
    */
    private $salt;

    /**
    * @ORM\Column(type="string", length=255, nullable=true)
    */
    private $password;

    /**
    * @ORM\Column(type="string", length=255, nullable=true)
    */
    private $picture;

    /**
    * @Assert\Length(min=8, max=4096, minMessage="user.password.short", maxMessage="user.password.long", groups={"Create", "Update", "ChangePassword", "ResetPassword"})
    * @var string $plainPassword
    */
    protected $plainPassword;

    /**
    * @ORM\Column(type="boolean")
    * @var boolean $enabled
    */
    protected $enabled;

    /**
    * @ORM\Column(type="boolean")
    * @var boolean $locked
    */
    protected $locked;

    /**
    * @ORM\Column(type="boolean")
    * @var boolean $visible
    */
    protected $visible;
    
    /**
     * @ORM\Column(type="boolean")
     * @var boolean $super_admin
     */
    protected $superAdmin;

    /**
    * @ORM\Column(name="account_expires_at", type="datetime", nullable=true)
    * @var \DateTime $accountExpiresAt
    */
    protected $accountExpiresAt;

    /**
    * @ORM\Column(name="credentials_expires_at", type="datetime", nullable=true)
    * @var \DateTime $credentialsExpiresAt
    */
    protected $credentialsExpiresAt;

    /**
    * @ORM\Column(name="confirmation_token", type="string", nullable=true)
    * @var string $confirmationToken
    */
    protected $confirmationToken;

    /**
    * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
    * @var \DateTime $passwordRequestedAt
    */
    protected $passwordRequestedAt;

    /**
    * @ORM\Column(name="password_changed", type="boolean")
    * @var boolean $passwordChanged
    */
    protected $passwordChanged;

    /**
    * @var array
    * @ORM\Column(name="roles", type="array")
    */
    private $roles = array();

    public function __construct() {
        $this->roles = [];
        $this->enabled = false;
        $this->locked = false;
        $this->visible = true;
        $this->passwordChanged = false;
        $this->superAdmin = false;
    }

    public function getId() :?int {
        return $this->id;
    }

    /**
    * @inheritDoc
    */
    public function getUsername() {
        return $this->username;
    }

    /**
    * @inheritDoc
    */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    /**
    * @inheritDoc
    */
    public function getSalt() {
        return $this->salt;
    }

    public function setSalt($salt) {
        $this->salt = $salt;
        return $this;
    }


    public function hasRole(string $role) :bool {
        return in_array(strtoupper($role), $this->roles, true);
    }

    public function addRole(string $role) :self {
        $role = strtoupper($role);
      
    //  if ($role !== static::ROLE_DEFAULT) {
        if (false === in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    //  }
      
        return $this;
    }

    public function setRoles(array $roles) :self {
        foreach ($roles as $role) {
            $this->addRole($role);
        }
        return $this;
    }

    public function removeRole(string $role) :self {
        $role = strtoupper($role);
      
        if ($role !== static::ROLE_DEFAULT) {
            if (false !== ($key = array_search($role, $this->roles, true))) {
                unset($this->roles[$key]);
                $this->roles = array_values($this->roles);
            }
        }
      
        return $this;
    }

    /**
    * @inheritDoc
    */
    public function getRoles() {
        return $this->roles;
    }

    /**
    * @inheritDoc
    */
    public function eraseCredentials() {}

    /**
    * @see \Serializable::serialize()
    */
    public function serialize() {
        return serialize([$this->id,]);
    }

    /**
    * @see \Serializable::unserialize()
    */
    public function unserialize($serialized) {
        list ($this->id,) = unserialize($serialized);
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setFirstName($firstName) {
        $this->firstName = $firstName;
        return $this;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function setLastName($lastName) {
        $this->lastName = $lastName;
        return $this;
    }

    public function getLastName() {
        return $this->lastName;
    }

    public function setGender($gender) {
        $this->gender = $gender;
        return $this;
    }

    public function getGender() {
        return $this->gender;
    }

    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getPhoneNumber() {
        return $this->phoneNumber;
    }
    
    public function getPlainPassword() {
        return $this->plainPassword;
    }
    
    public function setPlainPassword(string $plainPassword) :self {
        $this->plainPassword = $plainPassword;
        return $this;
    }
    
    public function setPicture($picture) :self {
        $this->picture = $picture;
        return $this;
    }
    
    public function getPicture() :?string {
        return $this->picture;
    }
    
    /**
     * @inheritDoc
     */
    public function getPassword() {
        return $this->password;
    }
    
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }
    
    public function getAccountExpiresAt() :?\DateTime {
        return $this->accountExpiresAt;
    }
    
    public function setAccountExpiresAt(\DateTime $expiresAt = null) :self {
        $this->accountExpiresAt = $expiresAt;
        return $this;
    }
    
    public function isAccountNonExpired() {
        return $this->accountExpiresAt instanceof \DateTime ?
        $this->accountExpiresAt->getTimestamp() >= time () : true;
    }
    
    public function getCredentialsExpiresAt() :?\DateTime {
        return $this->credentialsExpiresAt;
    }
    
    public function setCredentialsExpiresAt(\DateTime $expiresAt = null) :self {
        $this->credentialsExpiresAt = $expiresAt;
        return $this;
    }
    
    public function isCredentialsNonExpired() {
        return $this->credentialsExpiresAt instanceof \DateTime ?
        $this->credentialsExpiresAt->getTimestamp() >= time () : true;
    }
    
    public function setEnabled(bool $enabled) :self {
        $this->enabled = $enabled;
        return $this;
    }
    
    public function isEnabled() {
        return $this->enabled;
    }
    
    public function setLocked(bool $locked) :self {
        $this->locked = $locked;
        return $this;
    }
    
    public function isLocked() {
        return $this->locked;
    }
    
    public function isAccountNonLocked() {
        return !$this->locked;
    }
    
    public function getConfirmationToken() :?string {
        return $this->confirmationToken;
    }
    
    public function setConfirmationToken(string $confirmationToken = null) :?self {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }
    
    public function getPasswordRequestedAt() :?\DateTime {
        return $this->passwordRequestedAt;
    }
    
    public function setPasswordRequestedAt(\DateTime $passwordRequestedAt = null) :self {
        $this->passwordRequestedAt = $passwordRequestedAt;
        return $this;
    }
    
    public function isPasswordRequestNonExpired(int $ttl) :bool {
        return $this->passwordRequestedAt instanceof \DateTime &&
        $this->passwordRequestedAt->getTimestamp() + $ttl > time();
    }
    
    public function setPasswordChanged(bool $passwordChanged) :self {
        $this->passwordChanged = $passwordChanged;
        return $this;
    }
    
    public function isPasswordChanged() {
        return $this->passwordChanged;
    }
    
    /**
     * @Assert\IsTrue(message="user.password.equal_username", groups={"Create", "Update", "ChangePassword", "ResetPassword"})
     * @return boolean
     */
    public function isPasswordEqualUsername() {
        if ($this->username === null) {
            return true;
        }
        
        return strtolower($this->username) !== strtolower($this->plainPassword);
    }
    
    /**
     * @Assert\IsTrue(message="user.password.equal_email", groups={"Create", "Update", "ChangePassword", "ResetPassword"})
     * @return boolean
     */
    public function isPasswordEqualEmail() {
        return strtolower($this->email) !== strtolower($this->plainPassword);
    }

    public function getFullName(int $width = null) :?string {
        $fullName = $this->firstName ?: '';
        $fullName .= $this->lastName && $this->firstName ? ' '.$this->lastName : ($this->lastName ?: '');
        
        return $width && $fullName ? mb_strimwidth($fullName, 0, $width, '...') : $fullName;
    }
    
    public function __toString() {
        return $this->getFullName() ?: $this->username;
    }
    
    public function setVisible($visible) :self {
        $this->visible = $visible;
        return $this;
    }

    public function isVisible() :?bool {
        return $this->visible;
    }
    
    public function setSuperAdmin($superAdmin) :self {
        $this->superAdmin = $superAdmin;
        return $this;
    }
    
    public function isSuperAdmin() :? bool {
        return $this->superAdmin;
    }
}
