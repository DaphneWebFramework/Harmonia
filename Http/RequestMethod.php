<?php declare(strict_types=1);
/**
 * RequestMethod.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Http;

/**
 * Enumeration of HTTP request methods (RFC 9110).
 */
enum RequestMethod: string
{
    case GET     = 'GET';
    case POST    = 'POST';
    case PUT     = 'PUT';
    case DELETE  = 'DELETE';
    case PATCH   = 'PATCH';
    case OPTIONS = 'OPTIONS';
    case HEAD    = 'HEAD';
    case CONNECT = 'CONNECT';
    case TRACE   = 'TRACE';
}
