<?php

namespace TGN\CoreBundle\Component\User;

/**
 * Interface to implement a email verification behavior
 */
interface EmailVerifiableInterface
{
	/**
	 * Return TRUE if email already verified, FALSE otherwise
	 * 
	 * @return boolean
	 */
	public function isEmailVerified();
	
	/**
	 * Set the emailVerified flag to TRUE or FALSE
	 * 
	 * @param bool $isVerified
	 * @return $this
	 */
	public function setEmailVerified($isVerified);
	
	/**
	 * Generate a new token and set it
	 * 
	 * @return string The new token
	 */
	public function regenerateEmailVerificationToken();
	
	/**
	 * Clear the existing token
	 * 
	 * @return $this
	 */
	public function clearEmailVerificationToken();
	
	/**
	 * Get the token
	 * 
	 * @return string
	 */
	public function getEmailVerificationToken();
	
	/**
	 * @return \DateTime
	 */
	public function getEmailVerificationTokenGeneratedAt();
}