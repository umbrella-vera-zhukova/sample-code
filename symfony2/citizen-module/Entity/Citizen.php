<?php

namespace TGN\CoreBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use TGN\CoreBundle\Component\User\EmailVerifiableInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
/**
 * TGN\CoreBundle\Entity\Citizen
 *
 * @ORM\Table(name="citizens")
 * @ORM\Entity(repositoryClass="TGN\CoreBundle\Entity\Repository\Citizen")
 * @ORM\HasLifecycleCallbacks()
 * 
 * @UniqueEntity("email")
 */
class Citizen implements AdvancedUserInterface, EmailVerifiableInterface
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * 
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string $firstName
     *
     * @ORM\Column(name="first_name", type="string", length=255)
     * 
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     */
    private $firstName;

    /**
     * @var string $lastName
     *
     * @ORM\Column(name="last_name", type="string", length=255)
     * 
     * @Assert\NotBlank()
     * @Assert\Length(max="255")
     */
    private $lastName;

    /**
     * @var string $middleName
     *
     * @ORM\Column(name="middle_name", type="string", length=255, nullable=true)
     * 
     * @Assert\Length(max="255")
     */
    private $middleName;

    /**
     * @var date $dateOfBirth
     * @ORM\Column(name="date_of_birth", type="date", nullable=true)
     * 
     */
    private $dateOfBirth;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=128)
     * 
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @var string $salt
     *
     * @ORM\Column(type="string", length=32)
     * 
     * @Assert\NotBlank()
     */
    protected $salt;

    /**
     * Not persisted password. Used for registration & changing password
     */
    protected $plainPassword;

    /**
     * @var datetime $created
     * 
     * @ORM\Column(type="datetime", name="created_at")
     * 
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var datetime $updated
     * 
     * @ORM\Column(type="datetime", name="updated_at")
     * 
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @var string $phoneNumber
     * 
     * @ORM\Column(name="phone_number", type="string", length=32)
     * 
     * @Assert\Length(max="32")
     */
    protected $phoneNumber;

    /**
     *
     * @ORM\ManyToOne(targetEntity="TGN\CoreBundle\Entity\Dictionary\CitizenStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     */
    protected $status;

    /**
     * If FALSE then citizen must verify his email
     * 
     * @var boolean $emailVerified
     *
     * @ORM\Column(name="is_email_verified", type="boolean")
     */
    private $emailVerified;

    /**
     * Token to verify the email
     * Will be set to NULL after verification
     * 
     * @var boolean $emailVerificationToken
     *
     * @ORM\Column(name="email_verification_token", type="string", length=255, nullable=true, unique=true)
     * 
     * @Assert\Length(max="255")
     */
    private $emailVerificationToken;

    /**
     * Timestamp of regeneration token
     * 
     * @var \DateTime $emailVerificationTokenGeneratedAt
     *
     * @ORM\Column(name="email_verification_token_generated_at", type="datetime", nullable=true)
     * 
     * @Assert\Type("\DateTime")
     */
    private $emailVerificationTokenGeneratedAt;

    public function __construct()
    {
        //autofilling the salt
        $this->setSalt(md5(uniqid(null, true)));

        //need to verify email by default
        $this->setEmailVerified(FALSE);
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the username used to authenticate the user - email is this case
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Admin
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set first name
     *
     * @param string $firstName
     * @return Admin
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get first name
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return Citizen
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set middle name
     *
     * @param string $middleName
     * @return Citizen
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;
        return $this;
    }

    /**
     * Get middle name
     *
     * @return string 
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Admin
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @param \Datetime $date
     * @return \TGN\CoreBundle\Entity\Citizen
     */
    public function setDateOfBirth($date)
    {
        $this->dateOfBirth = $date;

        return $this;
    }

    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * 
     */
    public function setPlainPassword($str)
    {
        $this->plainPassword = $str;
        return $this;
    }

    /**
     * 
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return array('ROLE_CITIZEN');
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        
    }

    /**
     * Get full name (first + last names)
     * 
     * @return string
     */
    public function getFullName()
    {
        return $this->lastName. ' ' . $this->firstName . ' ' . $this->middleName;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Citizen
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Citizen
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set phoneNumber
     *
     * @param string $phoneNumber
     * @return Citizen
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return string 
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Set status
     *
     * @param \TGN\CoreBundle\Entity\Dictionary\CitizenStatus $status
     * @return Citizen
     */
    public function setStatus(\TGN\CoreBundle\Entity\Dictionary\CitizenStatus $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \TGN\CoreBundle\Entity\Dictionary\CitizenStatus 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * (non-PHPdoc)
     * @see FME\Bundle\EntityIdType\Interfaces.EmailVerificatableInterface::isEmailVerified()
     */
    public function isEmailVerified()
    {
        return $this->emailVerified;
    }

    /**
     * @see EmailVerificatableInterface::setEmailVerified()
     */
    public function setEmailVerified($isVerified)
    {
        $isVerified = (bool) $isVerified;

        switch ($isVerified)
        {
            //clear the token if email is verified
            case TRUE:

                $this->clearEmailVerificationToken();
                break;

            //create the new token or leave as is
            case FALSE:

                if (!$this->getEmailVerificationToken())
                {
                    $this->regenerateEmailVerificationToken();
                }
                break;
        }

        $this->emailVerified = (bool) $isVerified;

        return $this;
    }

    /**
     * @see EmailVerificatableInterface::regenerateEmailVerificationToken()
     */
    public function regenerateEmailVerificationToken()
    {
        $this->emailVerificationToken = md5(uniqid(mt_rand(), true));
        $this->emailVerificationTokenGeneratedAt = new \DateTime();
        return $this;
    }

    /**
     * @see EmailVerificatableInterface::clearEmailVerificationToken()
     */
    public function clearEmailVerificationToken()
    {
        $this->emailVerificationToken = NULL;
        return $this;
    }

    /**
     * @see EmailVerificatableInterface::getEmailVerificationToken()
     */
    public function getEmailVerificationToken()
    {
        return $this->emailVerificationToken;
    }

    /**
     * @see EmailVerificatableInterface::getEmailVerificationTokenGeneratedAt()
     */
    public function getEmailVerificationTokenGeneratedAt()
    {
        return $this->emailVerificationTokenGeneratedAt;
    }

    /**
     * Random password generator
     * @return string
     */
    public function generatePassword()
    {
        return substr(md5(mt_rand()), 1, 12);
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountNonLocked()
    {
        return $this->isEmailVerified();
    }

    /**
     * {@inheritDoc}
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Here is a magic for security provider to get only active users.
     */
    public function isEnabled()
    {
        return $this->getStatus()->getSlug() == 'active';
    }

}
