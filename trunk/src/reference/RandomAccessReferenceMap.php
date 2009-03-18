<?php
/**
 * OWASP Enterprise Security API (ESAPI)
 * 
 * This file is part of the Open Web Application Security Project (OWASP)
 * Enterprise Security API (ESAPI) project. For details, please see
 * <a href="http://www.owasp.org/index.php/ESAPI">http://www.owasp.org/index.php/ESAPI</a>.
 *
 * Copyright (c) 2007 - 2008 The OWASP Foundation
 * 
 * The ESAPI is published by OWASP under the BSD license. You should read and accept the
 * LICENSE before you use, modify, and/or redistribute this software.
 * 
 * @author 
 * @created 2008
 * @since 1.4
 * @package org.owasp.esapi.reference
 */

require_once dirname(__FILE__).'/../AccessReferenceMap.php';
require_once dirname(__FILE__).'/../StringUtilities.php';

class RandomAccessReferenceMap implements AccessReferenceMap {
	private $dtoi = null;
	private $itod = null;
	private $random = 0;
	
	function __construct($directReferences = null)
	{
		$this->random = mt_rand();
		
		$this->dtoi = new ArrayObject();
		$this->itod = new ArrayObject();
		
		if ( !empty($directReferences) ) 
		{
			$this->update($directReferences);
		}
	}

	/**
	 * Get an iterator through the direct object references. No guarantee is made as 
	 * to the order of items returned.
	 * 
	 * @return the iterator
	 */
	function iterator()
	{
		return $this->dtoi->getIterator();
	}

	/**
	 * Get a safe indirect reference to use in place of a potentially sensitive
	 * direct object reference. Developers should use this call when building
	 * URL's, form fields, hidden fields, etc... to help protect their private
	 * implementation information.
	 * 
	 * @param directReference
	 * 		the direct reference
	 * 
	 * @return 
	 * 		the indirect reference
	 */
	function getIndirectReference($direct)
	{
		// echo "<h3>RARM :: getIndirectReference()</h3>";
		// echo "<p>Direct = [".$direct."]";
		
		if ( empty($direct) )
		{
			return null;
		}
		
		$hash = $this->getHash($direct);
		
		// echo "<p>Hash = [".$hash."]";
		
		if ( !($this->dtoi->offsetExists($hash)) )
		{
			// echo "<p>No such hash.";
			return null;
		}
		
//		echo "<p>Returning [".$this->dtoi->offsetGet($hash)."]. ";
//		echo "The direct value for this hash is [".$this->itod->offsetGet($this->dtoi->offsetGet($hash))."]<p>";
		return $this->dtoi->offsetGet($hash);
	}

	/**
	 * Get the original direct object reference from an indirect reference.
	 * Developers should use this when they get an indirect reference from a
	 * request to translate it back into the real direct reference. If an
	 * invalid indirect reference is requested, then an AccessControlException is
	 * thrown.
	 * 
	 * @param indirectReference
	 * 		the indirect reference
	 * 
	 * @return 
	 * 		the direct reference
	 * 
	 * @throws AccessControlException 
	 * 		if no direct reference exists for the specified indirect reference
	 */
	function getDirectReference($indirectReference)
	{
		if (!empty($indirectReference) && $this->itod->offsetExists($indirectReference) )
		{
			return $this->itod->offsetGet($indirectReference);
		}
		
		throw new AccessControlException("Access denied", "Request for invalid indirect reference: " + $indirectReference);
		return null;
	}

	/**
	 * Adds a direct reference to the AccessReferenceMap, then generates and returns 
	 * an associated indirect reference.
	 *  
	 * @param direct 
	 * 		the direct reference
	 * 
	 * @return 
	 * 		the corresponding indirect reference
	 */
	function addDirectReference($direct)
	{
//		echo "<h3>RARM :: addDirectReference()</h3>";
//		echo "<p>DirectReferences = [";
//		print_r($direct);
//		echo "]";
		if ( empty($direct) )
		{
			// echo "Direct is empty, returning null.";
			return null;
		}
		
		$hash = $this->getHash($direct);
		
		// echo "<p>hash = [".$hash."]";
		
		if ( $this->dtoi->offsetExists($hash) )
		{
			// echo "<p>Object exists already, returning [".$this->dtoi->offsetGet($hash)."]";
			return $this->dtoi->offsetGet($hash);
		}
		
		$indirect = $this->getUniqueRandomReference();
		
		// echo "<p>Returning indirect ($indirect), setting hash maps itod($indirect, $direct) dtoi($hash, $indirect)";
		
		$this->itod->offsetSet($indirect, $direct);
		$this->dtoi->offsetSet($hash, $indirect);
		
		return $indirect;
	}
	
	/**
	 * Create a new random reference that is guaranteed to be unique.
	 * 
	 *  @return 
	 *  	a random reference that is guaranteed to be unique
	 */
	function getUniqueRandomReference() {
		$candidate = null;
		
		do {
			$candidate = StringUtilities::getRandomString(6	, "123456789");
		} while ($this->itod->offsetExists($candidate));
		
		// print "<p>random candidate [".$candidate."] is unique";		
		return $candidate;
	}
	
	function getHash($direct) 
	{
		if ( empty($direct) )
		{
			return null;
		}
		
		$hash = hexdec(substr(md5(serialize($direct)), -8));
		// print "<p>hash of [".$direct."] is [".$hash."]";
		return $hash;
	}
	
	/**
	 * Removes a direct reference and its associated indirect reference from the AccessReferenceMap.
	 * 
	 * @param direct 
	 * 		the direct reference to remove
	 * 
	 * @return 
	 * 		the corresponding indirect reference
	 * 
	 * @throws AccessControlException
	 */
	function removeDirectReference($direct)
	{
		if ( empty($direct) ) {
			return null;
		}
		
		$hash = $this->getHash($direct);
		
		if ( $this->dtoi->offsetExists($hash) ) {
			$indirect = $this->dtoi->offsetGet($hash);
			$this->itod->offsetUnset($indirect);
			$this->dtoi->offsetUnset($hash);
			return $indirect;
		} 
		
		return null;
	}



	/**
	 * Updates the access reference map with a new set of direct references, maintaining
	 * any existing indirect references associated with items that are in the new list.
	 * New indirect references could be generated every time, but that
	 * might mess up anything that previously used an indirect reference, such
	 * as a URL parameter. 
	 * 
	 * @param directReferences
	 * 		a Set of direct references to add
	 */
	function update($directReferences)
	{
//		echo "<h3>RARM :: update()</h3>";
//		echo "<p>DirectReferences = [";
//		print_r($directReferences);
//		echo "]";
		$dtoi_old = clone $this->dtoi;
		
		unset($this->dtoi);
		unset($this->itod);
				
		$this->dtoi = new ArrayObject();
		$this->itod = new ArrayObject();

		$dir = new ArrayObject($directReferences);
		$directIterator = $dir->getIterator();				

		while ($directIterator->valid())
		{
			$indirect = null;
			$direct = $directIterator->current();
			$hash = $this->getHash($direct);
			
//			echo "<p>Direct = [".$direct."]";
//			echo "<p>Hash = [".$hash."]";
			
			// Try to get the old direct object reference (if it exists)
			// otherwise, create a new entry
			if (!empty($direct) && $dtoi_old->offsetExists($hash) )
			{
				$indirect = $dtoi_old->offsetGet($hash);
				// echo "Taking old indirect reference [".$indirect."] for hash";
			}
			
			if ( empty($indirect) )
			{
				$indirect = $this->getUniqueRandomReference();
				// echo "Creating new indirect reference [".$indirect."] for hash";
			}
			$this->itod->offsetSet($indirect, $direct);
			$this->dtoi->offsetSet($hash, $indirect);
			$directIterator->next();
		}
		
//		echo "<p>ItoD = [";
//		print_r($this->itod);
//		echo "]";
//		echo "<p>DtoI = [";
//		print_r($this->dtoi);
//		echo "]";
//		echo "<h3>RARM :: update()</h3>";
	}
}
?>