<?php
/**
 * OWASP Enterprise Security API (ESAPI)
 *
 * This file is part of the Open Web Application Security Project (OWASP)
 * Enterprise Security API (ESAPI) project. For details, please see
 * http://www.owasp.org/esapi.
 *
 * Copyright (c) 2007 - The OWASP Foundation
 *
 * The ESAPI is published by OWASP under the LGPL. You should read and accept the
 * LICENSE before you use, modify, and/or redistribute this software.
 *
 * @author Jeff Williams <a href="http://www.aspectsecurity.com">Aspect Security</a>
 * @author Andrew van der Stock <a href="http://www.aspectsecurity.com">Aspect Security</a>
 * @package org.owasp.esapi.errors;
 * @since 2008
 */


/**
 * FIXME: DOC.
 * @author Jeff Williams (jeff.williams@aspectsecurity.com)
 */
class ValidationAvailabilityException extends ValidationException {

	/** The Constant serialVersionUID. */
	private static $serialVersionUID = 1;

	/**
	 * Instantiates a new validation exception.
	 */
	protected function ValidationAvailabilityException() {
		// hidden
	}

    /**
     * Create a new ValidationException
     * @param userMessage
     * @param logMessage
     * @param cause
     */
	public function ValidationAvailabilityException($userMessage, $logMessage, $cause = null) {
		super($userMessage, $logMessage, $cause);
	}
}
?>